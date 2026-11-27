<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\EnTetesSecuriteSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EnTetesSecuriteSubscriberTest extends TestCase
{
    public function testCspAppliqueeQuandCspEnforceVautUn(): void
    {
        $reponse = $this->reponse(new EnTetesSecuriteSubscriber(cspAppliquee: true));

        static::assertSame(EnTetesSecuriteSubscriber::CSP, $reponse->headers->get('Content-Security-Policy'));
        static::assertFalse($reponse->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function testUneCspPoseeParLeControleurNEstPasEcrasee(): void
    {
        $reponse = new Response();
        $reponse->headers->set('Content-Security-Policy-Report-Only', "default-src 'none'");

        $reponse = $this->reponse(new EnTetesSecuriteSubscriber(), $reponse);

        static::assertSame("default-src 'none'", $reponse->headers->get('Content-Security-Policy-Report-Only'));
    }

    private function reponse(EnTetesSecuriteSubscriber $subscriber, Response $reponse = new Response()): Response
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $subscriber->onResponse(new ResponseEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $reponse));

        return $reponse;
    }
}
