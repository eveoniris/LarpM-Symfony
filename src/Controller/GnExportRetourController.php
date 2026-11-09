<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Service\RetourExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Exports CSV des retours de jeu pour le responsable du wargame. Les fiches de groupe suivent les colonnes de l'import ; les retours
 * de groupe et de joueur font partie du module en bêta (ROLE_BETA_TEST).
 */
final class GnExportRetourController extends AbstractController
{
    #[Route('/gn/{gn}/export/fiches-groupe.csv', name: 'gn.export.fiches_groupe', requirements: ['gn' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_WARGAME')]
    public function fichesGroupeAction(Gn $gn, RetourExportService $export): Response
    {
        return $this->csv($export, $export->fichesGroupe($gn), 'fiches_retour_groupe', $gn);
    }

    #[Route('/gn/{gn}/export/retours-groupe.csv', name: 'gn.export.retours_groupe', requirements: ['gn' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
    #[IsGranted('ROLE_WARGAME')]
    public function retoursGroupeAction(Gn $gn, RetourExportService $export): Response
    {
        return $this->csv($export, $export->retoursGroupe($gn), 'retours_groupe', $gn);
    }

    #[Route('/gn/{gn}/export/retours-joueurs.csv', name: 'gn.export.retours_joueurs', requirements: ['gn' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
    #[IsGranted('ROLE_WARGAME')]
    public function retoursJoueursAction(Gn $gn, RetourExportService $export): Response
    {
        return $this->csv($export, $export->retoursJoueurs($gn), 'retours_joueurs', $gn);
    }

    /**
     * @param list<list<string|int>> $lignes
     */
    private function csv(RetourExportService $export, array $lignes, string $nom, Gn $gn): Response
    {
        $reponse = new Response($export->csv($lignes));
        $reponse->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $reponse->headers->set('Content-Disposition', \sprintf('attachment; filename="%s_%d.csv"', $nom, (int) $gn->getId()));

        return $reponse;
    }
}
