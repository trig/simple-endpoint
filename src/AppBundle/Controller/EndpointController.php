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
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $offset = (int)$req->query->get('offset', 0);
        $limit = (int)$req->query->get('limit', 50);
        $isPretty = (bool)$req->query->get('pretty', false);

        /* @var $qb QueryBuilder */
        $qb = $em->createQueryBuilder('l')
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

        $response = new \Symfony\Component\HttpFoundation\JsonResponse();

        $total = (int)$countQb->select('COUNT(l)')->getQuery()->getSingleScalarResult();
        $totalPages = round($total/$limit);

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

        if ($isPretty) {
            $response->setEncodingOptions(JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }

}
