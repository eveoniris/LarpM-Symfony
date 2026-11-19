<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\AjoutMasseType;
use App\Service\AjoutMasseAnalyseur;
use App\Service\AjoutMasseDemande;
use App\Service\AjoutMasseMessage;
use App\Service\AjoutMasseNotifier;
use App\Service\AjoutMasseOrchestrateur;
use App\Service\IdListParser;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Ajout en masse d'XP, de renommée ou de chronologie à une liste de personnages.
 */
#[IsGranted('ROLE_ADMIN')]
final class AjoutMasseController extends AbstractController
{
    #[Route('/admin/masse', name: 'admin.masse', methods: ['GET', 'POST'])]
    public function indexAction(
        Request $request,
        IdListParser $parser,
        AjoutMasseAnalyseur $analyseur,
        AjoutMasseOrchestrateur $orchestrateur,
    ): Response {
        $post = $request->request;
        $demande = new AjoutMasseDemande(
            AjoutMasseType::tryFrom($post->getString('type')) ?? AjoutMasseType::XP,
            $post->getString('valeur'),
            $post->getString('motif'),
            $post->getString('date', (new DateTime())->format('Y-m-d')),
            $this->message($request),
        );
        $analyse = null;
        $erreurs = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('ajout_masse', $post->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $fichier = $request->files->get('fichier');
            $ids = $parser->parse($post->getString('ids') . "\n" . (null === $fichier ? '' : (string) file_get_contents($fichier->getPathname())));
            $erreurs = $demande->erreurs($ids);

            if ([] === $erreurs) {
                $analyse = $analyseur->analyser($demande->type, $ids);
                if ('appliquer' === $post->getString('action')) {
                    $admin = $this->getUser();
                    $coches = array_map(intval(...), $post->all('morts'));
                    foreach ($orchestrateur->executer($demande, $analyse, $coches, $admin instanceof User ? $admin : null) as [$niveau, $texte]) {
                        $this->addFlash($niveau, $texte);
                    }

                    return $this->redirectToRoute('admin.masse');
                }
            }
        }

        return $this->render('admin/masse.twig', [
            'demande' => $demande,
            'ids' => $post->getString('ids'),
            'analyse' => $analyse,
            'erreurs' => $erreurs,
            'types' => AjoutMasseType::cases(),
            'texte' => $request->request->getString('texte', AjoutMasseNotifier::TEXTE_PAR_DEFAUT),
            'mail' => !$request->isMethod('POST') || $request->request->getBoolean('mail'),
            'prevenir' => $request->request->getBoolean('prevenir'),
        ]);
    }

    private function message(Request $request): ?AjoutMasseMessage
    {
        if (!$request->request->getBoolean('prevenir')) {
            return null;
        }

        return new AjoutMasseMessage($request->request->getString('texte', AjoutMasseNotifier::TEXTE_PAR_DEFAUT), $request->request->getBoolean('mail'));
    }
}
