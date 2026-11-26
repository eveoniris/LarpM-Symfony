<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InterJeu;
use App\Enum\InterJeuParticipantStatut;
use App\Repository\PersonnageRepository;

/**
 * Import de la liste des participants d'un inter-jeu : identifiants saisis et/ou fichier CSV
 * (1re colonne l'identifiant, 2e colonne facultative le statut). Le statut importé fait foi :
 * un personnage déjà présent voit son statut mis à jour et une éventuelle proposition d'orga externe est tranchée.
 */
final readonly class InterJeuImportService
{
    public function __construct(
        private PersonnageRepository $personnageRepository,
    ) {
    }

    /**
     * @return array{ajoutes: int, misAJour: int, introuvables: list<int>, statutsInconnus: list<int>, propositionsTranchees: int}
     */
    public function importer(InterJeu $inter, string $idsSaisis, ?string $cheminCsv, InterJeuParticipantStatut $statutDefaut): array
    {
        $saisies = InterJeuImportLecture::lire($idsSaisis, $cheminCsv);

        $inconnus = array_filter($saisies, static fn (string $saisie): bool => '' !== $saisie && null === InterJeuParticipantStatut::fromSaisie($saisie));
        $bilan = ['ajoutes' => 0, 'misAJour' => 0, 'introuvables' => [], 'statutsInconnus' => array_keys($inconnus), 'propositionsTranchees' => 0];
        foreach ($saisies as $id => $saisie) {
            $statut = InterJeuParticipantStatut::fromSaisie($saisie);

            $personnage = $this->personnageRepository->find($id);
            if (null === $personnage) {
                $bilan['introuvables'][] = $id;
                continue;
            }

            $existante = $inter->getParticipation($personnage);
            ++$bilan[null === $existante ? 'ajoutes' : 'misAJour'];
            $bilan['propositionsTranchees'] += (int) $existante?->hasProposition();
            $inter->addPersonnage($personnage, $statut ?? $statutDefaut)->annulerProposition();
        }

        return $bilan;
    }
}
