<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;
use App\Entity\User;
use App\Repository\GnRepository;

/**
 * Enchaîne un ajout en masse : écriture, mentions post mortem, messages aux joueurs.
 */
final readonly class AjoutMasseOrchestrateur
{
    public function __construct(
        private AjoutMasseService $service,
        private AjoutMassePostMortem $postMortem,
        private AjoutMasseNotifier $notifier,
        private GnRepository $gnRepository,
    ) {
    }

    /**
     * @param list<int> $coches ids de personnages morts à qui l'XP est donnée malgré tout
     *
     * @return list<array{0: string, 1: string}> messages à afficher (niveau, texte)
     */
    public function executer(AjoutMasseDemande $demande, AjoutMasseAnalyse $analyse, array $coches, ?User $admin): array
    {
        $estCoche = static fn (Personnage $p): bool => \in_array($p->getId(), $coches, strict: true);
        $mentions = array_values(array_filter($analyse->morts, static fn (Personnage $p): bool => !$estCoche($p)));
        $recoivent = [...$analyse->applicables, ...array_filter($analyse->morts, $estCoche)];

        $nb = $this->service->appliquer($demande, $recoivent, $admin);
        $rapport = [['success', \sprintf('%s ajouté à %d personnage(s).', $demande->type->label(), $nb)]];

        $rapport = [...$rapport, ...$this->mentionner($demande, $mentions, $admin)];
        $rapport = [...$rapport, ...$this->prevenir($demande, $recoivent, $mentions)];
        if ([] !== $analyse->introuvables) {
            $rapport[] = ['warning', 'Numéros introuvables : ' . implode(', ', $analyse->introuvables)];
        }

        return $rapport;
    }

    /**
     * @param list<Personnage> $mentions
     *
     * @return list<array{0: string, 1: string}>
     */
    private function mentionner(AjoutMasseDemande $demande, array $mentions, ?User $admin): array
    {
        if ([] === $mentions) {
            return [];
        }
        $gn = $this->gnRepository->findCurrentActive();
        if (null === $admin || null === $gn) {
            return [['warning', 'Mentions post mortem non écrites : aucun GN actif trouvé.']];
        }
        $this->postMortem->mentionner($demande, $mentions, $admin, $gn);

        return [['info', \sprintf('%d mention(s) post mortem écrite(s) dans la biographie privée.', \count($mentions))]];
    }

    /**
     * @param list<Personnage> $recoivent
     * @param list<Personnage> $mentions
     *
     * @return list<array{0: string, 1: string}>
     */
    private function prevenir(AjoutMasseDemande $demande, array $recoivent, array $mentions): array
    {
        if (null === $demande->message) {
            return [];
        }
        $ids = static fn (array $liste): array => array_map(static fn (Personnage $p): int => (int) $p->getId(), $liste);

        $sansUser = $this->notifier->prevenir($demande, $demande->message->texte, $ids($recoivent))->sansUser;
        if ([] !== $mentions) {
            $sansUser = [...$sansUser, ...$this->notifier->prevenir($demande, AjoutMasseNotifier::TEXTE_POST_MORTEM, $ids($mentions))->sansUser];
        }

        return [] === $sansUser ? [] : [['warning', 'Aucun message (personnage sans joueur) : ' . implode(', ', array_unique($sansUser))]];
    }
}
