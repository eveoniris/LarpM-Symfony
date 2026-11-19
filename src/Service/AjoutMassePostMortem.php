<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Entity\Personnage;
use App\Entity\PersonnageBackground;
use App\Entity\User;
use App\Enum\VisibilityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * XP non attribuée à un personnage mort : on garde seulement une mention dans sa biographie privée (scénaristes).
 */
final readonly class AjoutMassePostMortem
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<Personnage> $morts
     *
     * @return int nombre de mentions écrites
     */
    public function mentionner(AjoutMasseDemande $demande, array $morts, User $admin, Gn $gn): int
    {
        foreach ($morts as $mort) {
            $background = new PersonnageBackground();
            $background->setPersonnage($mort);
            $background->setUser($admin);
            $background->setGn($gn);
            $background->setVisibility(VisibilityType::PRIVATE->value);
            $background->setText(\sprintf(
                "<p>Gain d'XP post mortem à attribuer au prochain personnage : %+d XP, motif : %s.</p>",
                $demande->valeur(),
                htmlspecialchars($demande->motifPropre(), \ENT_QUOTES),
            ));
            $this->entityManager->persist($background);
        }
        $this->entityManager->flush();

        return \count($morts);
    }
}
