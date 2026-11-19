<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\LogActionType;
use App\Service\IdListParser;
use App\Service\MessageMasseCibles;
use App\Service\MessageMasseResolver;
use App\Service\MessageMasseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Envoi d'un message à plusieurs joueurs à partir d'une liste d'identifiants utilisateur ou personnage.
 */
#[IsGranted('ROLE_ADMIN')]
final class MessageMasseController extends AbstractController
{
    private const string TEXTE_PAR_DEFAUT = "<p>Bonjour,</p><p>Texte du message.</p><p>{personnages}</p><p>Cordialement,<br />L'équipe de gestion.</p>";

    #[Route('/admin/messages-masse', name: 'admin.messages.masse', methods: ['GET', 'POST'])]
    public function indexAction(Request $request, IdListParser $parser, MessageMasseService $service, MessageMasseResolver $resolver): Response
    {
        $saisie = [
            'type' => $request->request->getString('type', MessageMasseResolver::TYPE_PERSONNAGE),
            'ids' => $request->request->getString('ids'),
            'titre' => $request->request->getString('titre'),
            'texte' => $request->request->getString('texte', self::TEXTE_PAR_DEFAUT),
            'auteur' => $request->request->getString('auteur', 'systeme'),
            'notify' => !$request->isMethod('POST') || $request->request->getBoolean('notify'),
        ];
        $cibles = null;
        $erreurs = [];
        $apercu = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('messages_masse', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $ids = $parser->parse($saisie['ids']);
            $erreurs = $service->valider($ids, $saisie['titre'], $saisie['texte']);

            if ([] === $erreurs) {
                $cibles = $resolver->resolve($saisie['type'], $ids);
                $premier = $cibles->premier();
                $erreurs = null === $premier ? ['Aucun destinataire trouvé.'] : [];
                $apercu = null === $premier ? null : $service->render($saisie['texte'], $premier);
                if (null !== $premier && 'envoyer' === $request->request->getString('action')) {
                    return $this->envoyer($service, $cibles, $saisie);
                }
            }
        }

        return $this->render('message/masse.twig', [
            'saisie' => $saisie,
            'cibles' => $cibles,
            'erreurs' => $erreurs,
            'apercu' => $apercu,
        ]);
    }

    /** @param array{type: string, ids: string, titre: string, texte: string, auteur: string, notify: bool} $saisie */
    private function envoyer(MessageMasseService $service, MessageMasseCibles $cibles, array $saisie): Response
    {
        $user = $this->getUser();
        $auteur = 'moi' === $saisie['auteur'] && $user instanceof User ? $user : null;

        $envoyes = $service->send($cibles, $saisie['titre'], $saisie['texte'], $auteur, $saisie['notify']);

        $this->log(
            [
                'type' => $saisie['type'],
                'envoyes' => $envoyes,
                'sans_user' => $cibles->sansUser,
                'introuvables' => $cibles->introuvables,
                'titre' => $saisie['titre'],
                'auteur' => null === $auteur ? 'systeme' : $auteur->getId(),
                'email' => $saisie['notify'],
            ],
            LogActionType::MESSAGE_MASSE,
            true,
        );

        $this->addFlash('success', \sprintf('%d message(s) envoyé(s).', $envoyes));
        if ([] !== $cibles->sansUser || [] !== $cibles->introuvables) {
            $this->addFlash('warning', \sprintf('Non envoyés : personnages sans joueur [%s], identifiants introuvables [%s].', implode(', ', $cibles->sansUser), implode(', ', $cibles->introuvables)));
        }

        return $this->redirectToRoute('admin.messages.masse');
    }
}
