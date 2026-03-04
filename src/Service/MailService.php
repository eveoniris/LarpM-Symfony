<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class MailService
{
    private ?Request $request;

    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $router,
        private readonly Security $security,
    ) {
    }

    public function newMessage(
        User $destinataire,
        string $msg,
        ?string $sujet = null,
        ?User $auteur = null,
        bool $notify = true,
    ): void {
        $message = new Message();

        if (null === $auteur) {
            $securityUser = $this->security->getUser();
            $auteur = $securityUser instanceof User ? $securityUser : null;
        }

        $message->setTitle($sujet ?: 'Nouveau message');
        $message->setText($msg);
        $message->setUserRelatedByAuteur($auteur);
        $message->setUserRelatedByDestinataire($destinataire);
        $message->setCreationDate(new DateTime('NOW'));
        $message->setUpdateDate(new DateTime('NOW'));

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        if ($notify) {
            $this->notify($message);
        }
    }

    public function notify(Message $message): void
    {
        if (!($email = $message->getUserRelatedByDestinataire()?->getEmail())) {
            return; // unable to send
        }

        $this->mailer->send(
            $this
                ->getTemplatedEmail(
                    'user/email/message.twig',
                    ['message' => $message->getText()],
                    $message->getTitle() ?: 'Nouveau message de ' . $message->getUserRelatedByAuteur()?->getDisplayName(),
                )
                // TODO ->locale($user->getLocal())
                ->to($email),
        );
    }

    /** @param array<string, mixed> $context */
    private function getTemplatedEmail(
        string $template,
        array $context,
        ?string $subject = 'Par Crom!: Lis',
    ): TemplatedEmail {
        $subject = $this->twig->load($template)->renderBlock('subject', $context);
        $textBody = $this->twig->load($template)->renderBlock('body_text', $context);

        return (new TemplatedEmail())
            ->subject($subject)
            ->text($textBody)
            ->htmlTemplate($template)
            ->context($context);
    }

    public function sendConfirmEmail(User $user): void
    {
        $context = [
            'confirmationUrl' => $this->router->generate(
                'user.confirm-email',
                [
                    'token' => $user->getConfirmationToken(),
                    'user' => $user->getId(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];

        $this->mailer->send(
            $this
                ->getTemplatedEmail('user/email/confirm-email.twig', $context, 'Bienvenue ! Merci de confirmer votre adresse email.')
                // TODO ->locale($user->getLocal())
                ->to($user->getEmail()),
        );
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    private function getRequest(): Request
    {
        $this->request ??= $this->requestStack->getCurrentRequest();

        if (null === $this->request) {
            throw new LogicException('Request should exist so it can be processed for error.');
        }

        return $this->request;
    }
}
