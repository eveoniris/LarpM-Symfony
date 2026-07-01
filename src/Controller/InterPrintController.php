<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Enum\Role;
use App\Security\MultiRolesExpression;
use App\Service\PrintService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Impression des fiches des personnages d'un inter-jeu (rendu en flux, mémoire bornée).
 */
#[IsGranted(new MultiRolesExpression(Role::INTER_JEU, Role::ADMIN))]
#[Route('/inter-jeu', name: 'inter.')]
final class InterPrintController extends AbstractController
{
    #[Route('/{inter}/personnages/print/materiel', name: 'personnages.print.materiel', requirements: ['inter' => Requirement::DIGITS])]
    public function materielAction(InterJeu $inter, PrintService $printService): Response
    {
        return $printService->streamPersonnageFiches($this->getPersonnageIds($inter), 'personnage/fragment/enveloppe.twig', \sprintf('Matériel - %s', $inter->getNom()));
    }

    #[Route('/{inter}/personnages/print/fiche', name: 'personnages.print.fiche', requirements: ['inter' => Requirement::DIGITS])]
    public function ficheAction(InterJeu $inter, PrintService $printService): Response
    {
        return $printService->streamPersonnageFiches($this->getPersonnageIds($inter), 'personnage/fragment/print.twig', \sprintf('Fiches - %s', $inter->getNom()));
    }

    /**
     * Identifiants des personnages de l'inter-jeu (sans hydrater les entités,
     * pour permettre le vidage de l'EntityManager pendant l'impression).
     *
     * @return list<int>
     */
    private function getPersonnageIds(InterJeu $inter): array
    {
        $rows = $this->entityManager
            ->createQuery('SELECT p.id FROM App\Entity\InterJeu i JOIN i.personnages p WHERE i = :inter ORDER BY p.id ASC')
            ->setParameter('inter', $inter)
            ->getScalarResult();

        return array_map('intval', array_column($rows, 'id'));
    }
}
