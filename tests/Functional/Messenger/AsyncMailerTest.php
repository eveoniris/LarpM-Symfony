<?php

declare(strict_types=1);

namespace App\Tests\Functional\Messenger;

use App\Service\AsyncMailer;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Mime\Email;

#[Group('functional')]
class AsyncMailerTest extends KernelTestCase
{
    public function testEmailMisEnFileSansEnvoiImmediat(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $email = (new Email())
            ->from('a@example.test')
            ->to('b@example.test')
            ->subject('Sujet')
            ->text('Corps');
        $container->get(AsyncMailer::class)->send($email);

        $transport = $container->get('messenger.transport.async');
        static::assertInstanceOf(InMemoryTransport::class, $transport);
        $envelopes = $transport->getSent();
        static::assertCount(1, $envelopes);
        static::assertInstanceOf(SendEmailMessage::class, $envelopes[0]->getMessage());
    }
}
