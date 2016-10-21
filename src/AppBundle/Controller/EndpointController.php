<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class EndpointController extends Controller
{
    /**
     * @Route("/log", methods={"GET"})
     */
    public function logAction(\Symfony\Component\HttpFoundation\Request $req)
    {
        $offset = (int)$req->query->get('offset', 0);
        $limit = (int)$req->query->get('limit', 50);
        $isPretty = (bool)$req->query->get('pretty', false);

        /* @var $qb QueryBuilder */
        $qb = $this->getDoctrine()
          ->getEntityManager()
          ->createQueryBuilder('l')
          ->select('l')
          ->from('AppBundle:ObjectsLog', 'l');

        if ($filterCriteria = $req->query->get('f', false)) {
            if (isset($filterCriteria['type'])) {
                $qb->andWhere('l.type = :type')
                  ->setParameter('type', trim($filterCriteria['type']));
            }
            if (isset($filterCriteria['start'])) {
                $qb->andWhere('l.createdAt >= :start')
                  ->setParameter('start', trim($filterCriteria['start']));
            }
            if (isset($filterCriteria['end'])) {
                $qb->andWhere('l.createdAt <= :end')
                  ->setParameter('end', trim($filterCriteria['end']));
            }
        }

        $countQb = clone $qb;

        $qb->setMaxResults($limit)
          ->setFirstResult($offset);

        $response = $this->getResponse($isPretty);

        $total = (int)$countQb->select('COUNT(l)')->getQuery()->getSingleScalarResult();
        $totalPages = round($total / $limit);

        try {
            $result = $qb->getQuery()->getArrayResult();
            if (!empty($result)) {
                $result = array_map(function($log) {
                    $log['createdAt'] = $log['createdAt']->format('Y-m-d H:i');
                    return $log;
                }, $result);
            }
            $response->setData([
                'status' => true,
                'payload' => [
                    'collection' => $result,
                    'pagination' => [
                        'totalEntities' => $total,
                        'totalPages' => $totalPages
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $response->setStatusCode(\Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->setData([
                'error' => [
                    'code' => (int)$e->getCode(),
                    'message' => $req->query->has('debug') ? $e->getMessage() : 'Could not return payload'
                ]
            ]);
        }

        return $response;
    }

    /**
     * @Route("/properties", methods={"GET"})
     */
    public function propertiesAction(\Symfony\Component\HttpFoundation\Request $req)
    {
        $result = [];

        $isPretty = (bool)$req->query->get('pretty', false);

        try {

            $conn = $this->getDoctrine()->getConnection();

            $result['types'] = $conn->query("SELECT DISTINCT type FROM objects_log")
              ->fetchAll(\PDO::FETCH_COLUMN);

            $result['min_date'] = substr($conn->query("SELECT MAX(created_at) FROM objects_log")
                ->fetchAll(\PDO::FETCH_COLUMN)[0], 0, -3);

            $result['max_date'] = substr($conn->query("SELECT MIN(created_at) FROM objects_log")
                ->fetchAll(\PDO::FETCH_COLUMN)[0], 0, -3);

            $response = $this->getResponse($isPretty);
            $response->setData([
                'status' => true,
                'payload' => $result
            ]);
        } catch (\Exception $e) {
            $response->setStatusCode(\Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->setData([
                'error' => [
                    'code' => (int)$e->getCode(),
                    'message' => $req->query->has('debug') ? $e->getMessage() : 'Could not return payload'
                ]
            ]);
        }
        return $response;
    }

    /**
     * @param bool $isPretty
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function getResponse($isPretty = false)
    {
        $response = new \Symfony\Component\HttpFoundation\JsonResponse();
        if ($isPretty) {
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET');
        return $response;
    }

}
