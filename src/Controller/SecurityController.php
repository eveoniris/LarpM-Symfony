<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/access_denied', name: 'access_denied')]
    public function denied(): Response
    {
        return $this->render('security/denied.html.twig');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        ContainerBagInterface $params,
        MailerInterface $mailer,
        UserRepository $userRepository,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }

        // Handle password renew
        if ($user = $userRepository->findOneBy(['email' => $authenticationUtils->getLastUsername(), 'pwd' => null])) {
            $this->sendRenewMail($user, $entityManager, $mailer, $params, $userRepository);
            $error['messageKey'] = "Migration de compte: Vous devez renouveler votre mot de passe. les instructions pour réinitialiser votre mot de passe vous ont été envoyés.<br />
                          Si vous ne recevez pas de mail d'ici quelques minutes, vérifiez votre dossier spam ou poubelle.<br />
		                  L'expéditeur est ".$params->get('fromEmailAddress');
            $error['messageData'] = [];

            return $this->render(
                'security/login.html.twig',
                ['last_username' => $user->getUsername(), 'error' => $error],
            );
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    protected function sendRenewMail(
        User $user,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        ContainerBagInterface $params,
        UserRepository $userRepository,
    ): void {
        $user->setTimePasswordResetRequested(time());
        if (!$user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $url = $this->generateUrl(
            'user.reset-password',
            ['token' => $user->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $resetExpireAt = Carbon::createFromTimestamp($user->getTimePasswordResetRequested())
            ->addSeconds($params->get('passwordTokenTTL'))
            ->format('Y-m-d H:i:s');
        $context = [
            'resetUrl' => $url,
            'resetExpireAt' => $resetExpireAt,
        ];
        $subject = $this->renderBlock(
            'user/email/renewPassword.twig',
            'subject',
            $context,
        ) ?: 'Renouvellement de mot de passe';
        $textBody = $this->renderBlock('user/email/renewPassword.twig', 'body_text', $context);
        $context['subject'] = $subject;

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject($subject->getContent())
            // TODo ->locale($user->getLocal())
            ->text($textBody->getContent())
            ->htmlTemplate('user/email/renewPassword.twig')
            ->context($context);
        $mailer->send($email);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.',
        );
    }
}
