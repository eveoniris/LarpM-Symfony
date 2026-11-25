<?php

declare(strict_types=1);

namespace App\Service\Competence;

use App\Controller\AbstractController;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Gn;
use App\Entity\Level;
use App\Entity\Personnage;
use App\Repository\CompetenceRepository;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Logique des pages de fiche compétence (création, accès, export), sortie du contrôleur.
 */
class CompetenceFicheService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompetenceRepository $competenceRepository,
        private readonly PersonnageService $personnageService,
    ) {
    }

    /**
     * Nouvelle compétence, préremplie avec la famille et le niveau passés dans l'URL s'ils existent.
     */
    public function nouvelle(mixed $familleId, mixed $niveauIndex): Competence
    {
        $competence = new Competence();

        $famille = $familleId ? $this->entityManager->find(CompetenceFamily::class, $familleId) : null;
        if ($famille instanceof CompetenceFamily) {
            $competence->setCompetenceFamily($famille);
        }

        $niveau = $niveauIndex
            ? $this->entityManager->getRepository(Level::class)->findOneBy(['index' => (int) $niveauIndex + 1])
            : null;
        if ($niveau instanceof Level) {
            $competence->setLevel($niveau);
        }

        return $competence;
    }

    /**
     * Droit de lecture d'un joueur sur la fiche : privé s'il connaît la compétence,
     * public s'il peut l'acheter, null sinon.
     */
    public function droitLecture(?Personnage $personnage, Competence $competence, bool $voitTout): ?string
    {
        if (null === $personnage) {
            return null;
        }

        if ($personnage->isKnownCompetence($competence)) {
            return AbstractController::CAN_READ_PRIVATE;
        }

        if ($this->personnageService->getAvailableCompetences($personnage, $voitTout)->contains($competence)) {
            return AbstractController::CAN_READ;
        }

        return null;
    }

    /**
     * Lignes de l'export des personnages ayant la compétence.
     *
     * @return list<array{id: int|null, personnage: string, groupe: string, joueur: string, vivant: string}>
     */
    public function lignesExportPersonnages(Competence $competence, ?Gn $gn): array
    {
        $lignes = [];
        /** @var Personnage $personnage */
        foreach ($this->competenceRepository->getPersonnages($competence, $gn)->getQuery()->toIterable() as $personnage) {
            $lignes[] = [
                'id' => $personnage->getId(),
                'personnage' => (string) $personnage->getFullName(),
                'groupe' => $personnage->getLastParticipantGnGroupe()?->getNom() ?? '',
                'joueur' => $personnage->getUser()?->getFullName() ?? '',
                'vivant' => $personnage->getVivant() ? 'oui' : 'non',
            ];
        }

        return $lignes;
    }
}
