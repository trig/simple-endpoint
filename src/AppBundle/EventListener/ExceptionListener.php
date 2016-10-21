<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $response = new \Symfony\Component\HttpFoundation\JsonResponse();
        $req = $event->getRequest();
        $e = $event->getException();

        $response->setStatusCode(\Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->setData([
            'error' => [
                'code' => (int)$e->getCode(),
                'message' => $req->query->has('debug') ? $e->getMessage() : 'Could not return payload'
            ]
        ]);

        $event->setResponse($response);
    }

}
