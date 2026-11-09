<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Validation par le wargame des ordres de chaque phase du retour d'un groupe (jusqu'à trois phases, une seule s'il ne reste pas
 * d'ordres disponibles). Gardée dans `donnees['ordres_valides']`, par numéro de phase. Modifier le texte d'une phase retire sa
 * validation (voir DepouillementRetourGroupeService). Les ordres restent du texte : la validation n'écrit rien d'autre.
 */
// @mago-expect lint:cyclomatic-complexity
readonly class DepouillementOrdresService
{
    public const string CLE = 'ordres_valides';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRetourGroupeService $retourGroupe,
    ) {
    }

    /**
     * Les phases du retour avec leur texte et leur état de validation (seules les phases renseignées sont listées).
     *
     * @return array<int, array{texte: string, valide: bool}>
     */
    public function phases(Depouillement $depouillement): array
    {
        $retour = $this->retourGroupe->lire($depouillement);
        $valides = self::valides($depouillement);
        $phases = [];
        foreach ([1 => $retour->ordres1, 2 => $retour->ordres2, 3 => $retour->ordres3] as $numero => $texte) {
            if (null !== $texte) {
                $phases[$numero] = ['texte' => $texte, 'valide' => isset($valides[$numero])];
            }
        }

        return $phases;
    }

    /**
     * @throws ContenuInvalideException si le dossier n'est pas un groupe modifiable, ou si la phase n'a pas d'ordres
     */
    public function valider(Depouillement $depouillement, int $phase, User $wargame): void
    {
        $this->verifier($depouillement);
        if (!isset($this->phases($depouillement)[$phase])) {
            throw new ContenuInvalideException(\sprintf("La phase %d n'a pas d'ordres à valider.", $phase));
        }

        $valides = self::valides($depouillement);
        if (isset($valides[$phase])) {
            return;
        }

        $valides[$phase] = ['par' => (int) $wargame->getId(), 'le' => (new DateTimeImmutable())->format('c')];
        $this->enregistrer($depouillement, $valides, $wargame, \sprintf('Ordres de la phase %d validés par le wargame', $phase));
    }

    /**
     * @throws ContenuInvalideException si le dossier n'est pas un groupe modifiable
     */
    public function rouvrir(Depouillement $depouillement, int $phase, User $wargame): void
    {
        $this->verifier($depouillement);
        $valides = self::valides($depouillement);
        if (!isset($valides[$phase])) {
            return;
        }

        unset($valides[$phase]);
        $this->enregistrer($depouillement, $valides, $wargame, \sprintf('Validation des ordres de la phase %d retirée', $phase));
    }

    /**
     * Phases dont le texte vient de changer : leur validation tombe.
     *
     * @param array<int, string|null> $anciens
     * @param array<int, string|null> $nouveaux
     *
     * @return array<int, array{par: int, le: string}>
     */
    public static function sansPhasesModifiees(Depouillement $depouillement, array $anciens, array $nouveaux): array
    {
        $valides = self::valides($depouillement);
        foreach (array_keys($valides) as $phase) {
            if (($anciens[$phase] ?? null) !== ($nouveaux[$phase] ?? null)) {
                unset($valides[$phase]);
            }
        }

        return $valides;
    }

    /**
     * @return array<int, array{par: int, le: string}>
     */
    public static function valides(Depouillement $depouillement): array
    {
        $donnees = $depouillement->getDonnees()[self::CLE] ?? [];
        $valides = [];
        foreach (\is_array($donnees) ? $donnees : [] as $phase => $entree) {
            if (\is_array($entree)) {
                $valides[(int) $phase] = ['par' => (int) ($entree['par'] ?? 0), 'le' => (string) ($entree['le'] ?? '')];
            }
        }

        return $valides;
    }

    private function verifier(Depouillement $depouillement): void
    {
        if (DepouillementType::GROUPE !== $depouillement->getType()) {
            throw new ContenuInvalideException('Les ordres ne concernent que les dossiers de groupe.');
        }

        if (!$depouillement->getStatut()->isEditable()) {
            throw new ContenuInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }
    }

    /**
     * @param array<int, array{par: int, le: string}> $valides
     */
    private function enregistrer(Depouillement $depouillement, array $valides, User $wargame, string $motif): void
    {
        $avant = $depouillement->getDonnees()[self::CLE] ?? null;
        $depouillement->setDonnees([...$depouillement->getDonnees(), self::CLE => $valides]);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($wargame)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(null === $avant ? null : [self::CLE => $avant])
                ->setDataAfter([self::CLE => $valides])
                ->setMotif($motif),
        );
        $depouillement->touch();
        $this->entityManager->flush();
    }
}
