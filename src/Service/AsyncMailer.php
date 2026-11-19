<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Mime\RawMessage;

/**
 * Envoi d'e-mail différé : le message part dans la file Messenger "async" et un worker
 * (cron, voir bin/cron-messenger.php) l'envoie ensuite par SMTP.
 *
 * Le routage global de SendEmailMessage reste "sync" : seuls les e-mails passés ici sont
 * mis en file. Les e-mails sensibles au délai (confirmation, mot de passe oublié) restent
 * envoyés immédiatement par MailerInterface.
 */
final readonly class AsyncMailer
{
    public const string TRANSPORT = 'async';

    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function send(RawMessage $email): void
    {
        $this->bus->dispatch(new SendEmailMessage($email), [new TransportNamesStamp([self::TRANSPORT])]);
    }
}
