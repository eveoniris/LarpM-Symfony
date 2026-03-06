<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AccessEventListener
{
    public function __construct()
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // SAMPLE
        // if (!$this->security->isGranted('ROLE_ADMIN')) {
        //    $event->setController(fn() => new RedirectResponse('/login'));
        // }
        $controller = $event->getController();
        if (\is_array($controller) && \is_object($controller[0]) && method_exists($controller[0], 'loadGrantedAccess')) {
            $controller[0]->loadGrantedAccess();
        }
    }

    public function onKernelView(RequestEvent $event): void
    {
        /*
         * SAMPLE
         * $data = $event->getControllerResult();
         * $response = new Response(json_encode($data));
         * $response->headers->set('Content-Type', 'application/json');
         * $event->setResponse($response);
         */
    }
}
