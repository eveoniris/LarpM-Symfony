<?php

declare(strict_types=1);

namespace App\Tests\Functional\Messenger;

use App\Service\MailService;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

#[Group('functional')]
class MessageNotificationSujetTest extends KernelTestCase
{
    public function testLeSujetDeLEmailEstLeTitreDuMessage(): void
    {
        self::bootKernel();
        $destinataire = UserFactory::createOne(['email' => 'joueur@example.org']);

        self::getContainer()->get(MailService::class)->newSystemMessage($destinataire, 'Corps', 'Ajout : XP');

        $transport = self::getContainer()->get('messenger.transport.async');
        static::assertInstanceOf(InMemoryTransport::class, $transport);
        $enveloppes = $transport->getSent();
        static::assertCount(1, $enveloppes);
        $message = $enveloppes[0]->getMessage();
        static::assertInstanceOf(SendEmailMessage::class, $message);
        static::assertSame('Ajout : XP', $message->getMessage()->getSubject());
    }
}
