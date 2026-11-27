<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Security\Voter\InterJeuVoter;
use App\Service\InterJeuExportFichier;
use App\Service\InterJeuExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Export des tableaux de statistiques d'un inter-jeu (un CSV par tableau, ou tous dans un zip).
 * Lecture ouverte aux orgas externes validés ; les tableaux nominatifs sont réservés à la gestion (MANAGE).
 */
#[IsGranted('ROLE_USER')]
#[Route('/inter-jeu/{inter}/statistiques/export', name: 'inter.statistiques.export.', requirements: ['inter' => Requirement::DIGITS])]
final class InterExportController extends AbstractController
{
    #[Route('/{tableau}.csv', name: 'csv', requirements: ['tableau' => 'familles-cles|toutes-familles|personnages-par-famille|personnages'])]
    #[IsGranted(InterJeuVoter::VIEW, subject: 'inter')]
    public function csvAction(InterJeu $inter, string $tableau, InterJeuExportService $exportService): Response
    {
        if (InterJeuExportFichier::estNominatif($tableau)) {
            $this->denyAccessUnlessGranted(InterJeuVoter::MANAGE, $inter);
        }

        $response = new Response(InterJeuExportFichier::csv($exportService->tableau($inter, $tableau)));
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="' . InterJeuExportFichier::nomFichier($inter, $tableau) . '.csv"');

        return $response;
    }

    #[Route('.zip', name: 'zip')]
    #[IsGranted(InterJeuVoter::VIEW, subject: 'inter')]
    public function zipAction(Request $request, InterJeu $inter, InterJeuExportService $exportService): BinaryFileResponse
    {
        $nominatif = $request->query->getBoolean('nominatif');
        if ($nominatif) {
            $this->denyAccessUnlessGranted(InterJeuVoter::MANAGE, $inter);
        }

        $nom = InterJeuExportFichier::nomFichier($inter, date('Y-m-d') . ($nominatif ? '-nominatif' : '-anonyme')) . '.zip';
        $response = new BinaryFileResponse($exportService->zip($inter, $nominatif));
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom);

        return $response->deleteFileAfterSend();
    }
}
