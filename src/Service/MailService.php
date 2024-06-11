<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\BaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class MailService
{
    protected ?Request $request;
    protected ?BaseRepository $repository = null;

    public function __construct(
        protected readonly Environment $twig,
        protected readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $router,
    ) {
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
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];

        $this->mailer->send(
            $this->getTemplatedEmail(
                'user/email/confirm-email.twig',
                $context,
                'Bienvenue ! Merci de confirmer votre adresse email.'
            )
            // TODO ->locale($user->getLocal())
                ->to($user->getEmail())
        );
    }

    private function getTemplatedEmail(
        string $template,
        array $context,
        ?string $subject = 'Par Crom!: Lis'
    ): TemplatedEmail {
        $subject = $this->twig->load($template)->renderBlock('subject', $context) ?? $subject;
        $textBody = $this->twig->load($template)->renderBlock('body_text', $context);

        return (new TemplatedEmail())
            ->subject($subject)
            ->text($textBody)
            ->htmlTemplate($template)
            ->context($context);
    }

    public function setRequest(Request $request): MailService
    {
        $this->request = $request;

        return $this;
    }

    private function getRequest(): Request
    {
        $this->request ??= $this->requestStack->getCurrentRequest();

        if (null === $this->request) {
            throw new \LogicException('Request should exist so it can be processed for error.');
        }

        return $this->request;
    }
}
