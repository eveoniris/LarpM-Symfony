<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\LogAction;
use App\Enum\LogActionType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[Group('integration')]
class LogActionRequestListenerTest extends KernelTestCase
{
    public function testIpEtUserAgentRenseignesDansUneRequete(): void
    {
        self::bootKernel();
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_USER_AGENT' => 'TestAgent/1.0']);
        self::getContainer()->get(RequestStack::class)->push($request);

        $log = $this->persister();

        static::assertSame('203.0.113.7', $log->getIp());
        static::assertSame('TestAgent/1.0', $log->getUserAgent());
    }

    public function testChampsNullsHorsRequete(): void
    {
        self::bootKernel();

        $log = $this->persister();

        static::assertNull($log->getIp());
        static::assertNull($log->getUserAgent());
    }

    private function persister(): LogAction
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $log = new LogAction();
        $log->setType(LogActionType::OTHER);
        $log->setDate(new DateTime());
        $em->persist($log);
        $em->flush();

        return $log;
    }
}
