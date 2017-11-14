<?php

namespace AppBundle\Repository;

/**
 * ReportRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReportRepository extends BaseRepository
{
    /**
     * Get List of Reports
     * @param array $fields
     * @param null $reportId
     * @param null $offset
     * @param null $limit
     * @param string $sortingColumn
     * @param string $sorting
     * @param null $userId
     * @param bool $active
     * @return array
     */
    public function getList($fields = array(), $reportId = NULL, $offset = NULL, $limit = NULL, $sortingColumn = 'id', $sorting = 'ASC', $userId = null, $active = true)
    {
        $fieldsString = $this->convertFieldsToQuery($fields, 'r');

        $query = $this->createQueryBuilder('r')
                      ->innerJoin('r.category', 'cat')
                      ->select($fieldsString . ',cat.title as category')
                      ->where('r.report is null');

        //transform to query for returning resolved reports of the user
        if ($userId) {
            $query->join('r.owner', 'owner')
                  ->andWhere("r.owner=$userId");
            if (!$active) {
                $query->andWhere('r.active = 0');
            }
        } else {
            $query->andWhere('r.active = 1');
        }

        $images = $this->getReportImages();

        if ($reportId) {
            $query->andWhere('r . report = :reportId')
                  ->setParameter('reportId', $reportId);
        }

        $reportList = $this->wrapResult($query, 'r', $offset, $limit, $sortingColumn, $sorting);
        foreach ($reportList['list'] as &$report) {
            $report['fullUrl'] = isset($images[$report['id']]['fullUrl']) ? $images[$report['id']]['fullUrl'] : null;
        }
        return $reportList;
    }

    /**
     * Get list of Reports by Area
     * @param array $fields
     * @param $nwLat
     * @param $nwLng
     * @param $seLat
     * @param $seLng
     * @param null $offset
     * @param null $limit
     * @param string $sortingColumn
     * @param string $sorting
     * @return array
     */
    public function getListByArea($fields = array(), $reportId = null, $nwLat, $nwLng, $seLat, $seLng, $offset = NULL, $limit = NULL, $sortingColumn = 'id', $sorting = 'ASC')
    {
        $fieldsString = $this->convertFieldsToQuery($fields, 'r');

        $query = $this->createQueryBuilder('r')
                      ->select($fieldsString)
                      ->where('r . active = 1')
                      ->where('r . report is null')
                      ->andWhere('r . lat < :nwLat')
                      ->andWhere('r . lat > :seLat')
                      ->andWhere('r . lng > :nwLng')
                      ->andWhere('r . lng < :seLng')
                      ->setParameter('nwLat', $nwLat)
                      ->setParameter('nwLng', $nwLng)
                      ->setParameter('seLat', $seLat)
                      ->setParameter('seLng', $seLng);

        if ($reportId) {
            $query->andWhere('r . report = :reportId')
                  ->setParameter('reportId', $reportId);
        }

        return $this->wrapResult($query, 'r', $offset, $limit, $sortingColumn, $sorting);
    }

    public function getReport($fields = array(), $id)
    {
        $fieldsString = $this->convertFieldsToQuery($fields, 'r');

        $query = $this->createQueryBuilder('r')
                      ->select($fieldsString)
                      ->innerJoin('r . category', 'category')
                      ->where('r . id = :id')
                      ->andWhere('r . active = 1')
                      ->andWhere('r . report is null')
                      ->setParameter('id', $id);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param array $criteriaArray
     * @param array $criteriaArray
     * @return array
     */
    public function getByCriteria($criteriaArray, $fields)
    {
        $query = $this->createQueryBuilder('r');
        if (isset($criteriaArray['date_after'])) {
            $lowerBound = $criteriaArray['date_after'];
            $query->andWhere("r.creationTime > '$lowerBound'");
        }
        if (isset($criteriaArray['date_before'])) {
            $upperBound = $criteriaArray['date_before'];
            $query->andWhere("r.creationTime < '$upperBound'");
        }
        $query->innerJoin('r . category', 'cat');
        if (isset($criteriaArray['category'])) {
            $query->andWhere('cat.id = ' . $criteriaArray['category']);
        }
        if (isset($criteriaArray['active'])) {
            $query->andWhere('r.active = ' . $criteriaArray['active']);
        }
        if (isset($criteriaArray['title'])) {
            $query->andWhere(
                $query->expr()->like('r.title', ':title')
            )->setParameter('title', '%' . $criteriaArray['title'] . '%');
        }
        $fieldsString = $this->convertFieldsToQuery($fields, 'r');
        $query->andWhere('r . report is null')
              ->select($fieldsString . ',cat.title as category');


        $reportsFound = $query->getQuery()->getArrayResult();
        $images = $this->getReportImages();
        foreach ($reportsFound as &$report) {
            $report['fullUrl'] = isset($images[$report['id']]['fullUrl']) ? $images[$report['id']]['fullUrl'] : null;
        }
        return $reportsFound;
    }

    /**
     * @return array
     */
    private function getReportImages()
    {
        $queryForImages = $this->createQueryBuilder('r', 'r . id')
                               ->leftJoin('r.reportImages', 'ri')
                               ->select('ri.fullUrl,r . id')
                               ->where('r.active = 1')
                               ->andWhere('r.report is null');
        return $queryForImages->getQuery()->getArrayResult();
    }

}