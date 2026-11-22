<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Entity\Personnage;
use App\Repository\GnRepository;
use Closure;

/**
 * Verrouillage de l'édition des groupes d'un GN et effets de fin d'édition.
 *
 * Même chaîne que le bouton « Verrouiller » de la fiche GN ; partagée avec l'exécution planifiée.
 */
final readonly class GnLockService
{
    public function __construct(
        private GnRepository $gnRepository,
        private PersonnageService $personnageService,
        private MailService $mailService,
    ) {
    }

    public function verrouiller(Gn $gn): void
    {
        $this->gnRepository->lockAllGroup($gn);

        $this->personnageService->lockGnSetDefaultCharacter($gn);
        $this->personnageService->lockGnSetDefaultLangue($gn);
        $this->personnageService->lockGnSetDefaultSecondCharacter($gn);

        // Ces méthodes sont des générateurs : sans itération leur corps ne s'exécute pas
        $prevenir = $this->prevenirDuGain($gn);
        foreach ($this->personnageService->lockGnGiveNoblityGnRenomme($gn, onGain: $prevenir) as $_) {
        }
        foreach ($this->personnageService->lockGnGiveLiteratureGnBonus($gn, onGain: $prevenir) as $_) {
        }
        foreach ($this->personnageService->lockGnGiveSanctuaireGnEffect() as $_) {
        }
    }

    /**
     * Message système au joueur pour chaque gain de compétence réellement donné (un rejeu ne redonne rien, donc ne renvoie rien).
     *
     * @return Closure(Personnage, string, string): void
     */
    private function prevenirDuGain(Gn $gn): Closure
    {
        return function (Personnage $personnage, string $competence, string $gain) use ($gn): void {
            $user = $personnage->getUser();
            if (null === $user) {
                return;
            }

            $this->mailService->newSystemMessage(
                $user,
                \sprintf('Votre compétence %s vous octroie %s dans le cadre de votre participation à %s.', $competence, $gain, $gn->getLabel()),
                \sprintf('Compétence %s : gain pour %s', $competence, $gn->getLabel()),
                false,
            );
        };
    }
}
