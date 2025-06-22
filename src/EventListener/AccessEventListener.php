<?php

namespace App\EventListener;

use App\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AccessEventListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // SAMPLE
        //if (!$this->security->isGranted('ROLE_ADMIN')) {
        //    $event->setController(fn() => new RedirectResponse('/login'));
        //}
        if (is_array($event->getController()) && method_exists($event->getController()[0], 'loadGrantedAccess')) {
            $event->getController()[0]->loadGrantedAccess();
        }

    }

    public function onKernelView(RequestEvent $event): void
    {
        /*
         * SAMPLE
         $data = $event->getControllerResult();
        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        $event->setResponse($response);
         */

    }
}
