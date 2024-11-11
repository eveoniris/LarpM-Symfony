<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $payload = $event->getData();
        $payload['ip'] = $request->getClientIp();
        $payload['username'] = $event->getUser()->getEmail();
        $payload['aud'] = ''; // TODO add UUID on USER

        $event->setData($payload);

        // $header = $event->getHeader();
        // $header['cty'] = 'CTY';
        // $event->setHeader($header);
    }
}
