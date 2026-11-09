<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Enum\DepouillementType;
use App\Repository\DepouillementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique à la validation d'un dossier de groupe les titres déclarés (suzerain, chef de guerre, diplomate, intendant, navigateur,
 * éminence grise) sur la session du groupe. Un titre déjà tenu par un autre personnage n'est pas remplacé : c'est signalé au
 * scénariste. Une simulation calcule le même résultat sans rien écrire. Le flush est laissé à l'appelant.
 */
readonly class DepouillementTitresService
{
    public function __construct(
        private DepouillementRetourGroupeService $retourGroupe,
        private DepouillementRepository $depouillementRepository,
        private DepouillementOrdresService $ordres,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function appliquer(Depouillement $depouillement): ResultatApprentissages
    {
        $resultat = new ResultatApprentissages('Titres et ordres du groupe', 'Aucun titre ni ordre de groupe à traiter.');
        $groupeGn = $depouillement->getGroupeGn();
        if (DepouillementType::GROUPE !== $depouillement->getType() || !$groupeGn instanceof GroupeGn) {
            return $resultat;
        }

        $resultat->applique = !$depouillement->isSimulation();
        $titulaires = $groupeGn->titulaires();
        foreach ($this->retourGroupe->lire($depouillement)->titres as $cle => $numero) {
            if (null === $numero) {
                continue;
            }

            $this->titre($groupeGn, $cle, $numero, $titulaires[$cle] ?? null, $depouillement, $resultat);
        }

        foreach ($this->ordres->phases($depouillement) as $numero => $phase) {
            if (!$phase['valide']) {
                $resultat->anomalies[] = \sprintf("Les ordres de la phase %d n'ont pas été validés par le wargame.", $numero);
            }
        }

        return $resultat;
    }

    // @mago-expect lint:excessive-parameter-list
    private function titre(GroupeGn $groupeGn, string $cle, int $numero, ?Personnage $actuel, Depouillement $depouillement, ResultatApprentissages $resultat): void
    {
        $libelle = mb_strtolower(RetourGroupe::TITRES[$cle] ?? $cle) . ' n° ' . $numero;
        if ($actuel?->getId() === $numero) {
            $resultat->ignores[] = $libelle;

            return;
        }

        if ($actuel instanceof Personnage) {
            $resultat->anomalies[] = \sprintf('%s déclaré, mais le titre est déjà tenu par le personnage n° %d : non modifié.', ucfirst($libelle), (int) $actuel->getId());

            return;
        }

        $personnage = $this->entityManager->find(Personnage::class, $numero);
        if (!$personnage instanceof Personnage || null === $this->depouillementRepository->findParticipantParPersonnage($depouillement->getGn(), $numero)) {
            $resultat->anomalies[] = \sprintf('%s : personnage introuvable parmi les inscrits du GN.', ucfirst($libelle));

            return;
        }

        $resultat->ajoutes[] = $libelle;
        if ($resultat->applique) {
            match ($cle) {
                'suzerain' => $groupeGn->setSuzerin($personnage),
                'chef_guerre' => $groupeGn->setConnetable($personnage),
                'diplomate' => $groupeGn->setDiplomate($personnage),
                'intendant' => $groupeGn->setIntendant($personnage),
                'navigateur' => $groupeGn->setNavigateur($personnage),
                'eminence_grise' => $groupeGn->setCamarilla($personnage),
                default => null,
            };
        }
    }
}
