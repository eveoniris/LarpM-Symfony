<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Lecture de la file « failed » (liste, rejeu, suppression) et du volume en attente dans la file « async ».
 */
final readonly class MessengerSurveillance
{
    private const string TRANSPORT_ASYNC = 'async';

    public function __construct(
        #[Autowire(service: 'messenger.transport.failed')]
        private TransportInterface $failed,
        #[Autowire(service: 'messenger.transport.async')]
        private TransportInterface $async,
        private MessageBusInterface $bus,
    ) {
    }

    public function nombreEchecs(): int
    {
        return $this->failed instanceof MessageCountAwareInterface ? $this->failed->getMessageCount() : 0;
    }

    /** Messages en attente d'envoi, null quand le transport ne sait pas les compter. */
    public function enAttente(): ?int
    {
        return $this->async instanceof MessageCountAwareInterface ? $this->async->getMessageCount() : null;
    }

    /** @return list<MessengerEchec> */
    public function echecs(int $limite = 100): array
    {
        if (!$this->failed instanceof ListableReceiverInterface) {
            return [];
        }

        $echecs = [];
        foreach ($this->failed->all($limite) as $envelope) {
            $echecs[] = $this->decrire($envelope);
        }

        return $echecs;
    }

    /** Remet le message dans la file async puis le retire des échecs. */
    public function rejouer(int|string $id): bool
    {
        $envelope = $this->trouver($id);
        if (null === $envelope) {
            return false;
        }

        $this->bus->dispatch($envelope->getMessage(), [new TransportNamesStamp([self::TRANSPORT_ASYNC])]);
        $this->failed->reject($envelope);

        return true;
    }

    public function supprimer(int|string $id): bool
    {
        $envelope = $this->trouver($id);
        if (null === $envelope) {
            return false;
        }
        $this->failed->reject($envelope);

        return true;
    }

    private function trouver(int|string $id): ?Envelope
    {
        return $this->failed instanceof ListableReceiverInterface ? $this->failed->find($id) : null;
    }

    private function decrire(Envelope $envelope): MessengerEchec
    {
        $id = $envelope->last(\Symfony\Component\Messenger\Stamp\TransportMessageIdStamp::class)?->getId() ?? 0;
        $erreur = $envelope->last(ErrorDetailsStamp::class)?->getExceptionMessage() ?? '';
        $date = $envelope->last(RedeliveryStamp::class)?->getRedeliveredAt();
        $message = $envelope->getMessage();

        return new MessengerEchec($id, $message::class, $erreur, null === $date ? null : DateTimeImmutable::createFromInterface($date), $this->destinataires($message));
    }

    /** @return list<string> */
    private function destinataires(object $message): array
    {
        $email = $message instanceof SendEmailMessage ? $message->getMessage() : null;
        if (!$email instanceof Email) {
            return [];
        }

        return array_map(static fn (Address $a): string => $a->getAddress(), $email->getTo());
    }
}
