<?php

namespace AppBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class StatisticsListener
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $manager
     * @return \AppBundle\EventListener\StatisticsListener
     */
    public function setEntityManager(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
        return $this;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $req = $event->getRequest();

        $headers = json_encode($req->headers->all(), JSON_UNESCAPED_UNICODE);
        $ip = $req->getClientIp();
        $hash = hash('sha512', $headers . $ip);

        $isAlreadyThere = $this->entityManager->createQueryBuilder('s')
          ->select('s')
          ->from('AppBundle:Statistics', 's')
          ->where('s.hash = :hash')
          ->andWhere('s.ip = :ip')
          ->setParameter('hash', $hash)
          ->setParameter('ip', $ip)
          ->getQuery()
          ->getOneOrNullResult();

        if ($isAlreadyThere) {
            $isAlreadyThere->setRequests($isAlreadyThere->getRequests()+1);
            $this->entityManager->flush();
            return;
        }

        $stats = (new \AppBundle\Entity\Statistics())
          ->setIp($ip)
          ->setHash($hash)
          ->setHeaders($headers)
          ->setRequests(1);

        $this->entityManager->persist($stats);
        $this->entityManager->flush();
    }

}
