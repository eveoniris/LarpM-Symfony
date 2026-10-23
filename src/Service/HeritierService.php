<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Age;
use App\Entity\Classe;
use App\Entity\Conception;
use App\Entity\DepouillementHistory;
use App\Entity\ExperienceGain;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\PersonnageLignee;
use App\Entity\User;
use App\Enum\ConceptionStatut;
use App\Enum\LangueSourceType;
use App\Exception\ConceptionInvalideException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Décisions du scénariste sur une conception : créer le ou les héritiers, gérer la création plus tard, ou clôturer.
 *
 * L'issue du tirage n'est qu'une suggestion : le scénariste choisit le nombre d'héritiers (un ou deux), leur genre et leur
 * origine. Tout est consigné dans l'historique du dépouillement. Une simulation ne crée jamais d'héritier.
 *
 * Création dédiée (pas PersonnageService::createNewPersonnage) : l'héritier n'a pas de joueur, naît à 0 an dans la tranche
 * « Enfant héritier », porte la classe secrète et n'a pas d'événement de participation.
 */
// @mago-expect lint:cyclomatic-complexity
readonly class HeritierService
{
    public const int HERITIERS_MAX = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<HeritierSaisie> $saisies
     *
     * @return list<Personnage>
     *
     * @throws ConceptionInvalideException
     */
    public function creer(Conception $conception, array $saisies, User $scenariste): array
    {
        $this->verifierDecision($conception);

        if ($conception->isSimulation()) {
            throw new ConceptionInvalideException('Une simulation ne crée jamais d\'héritier.');
        }

        if ([] === $saisies || \count($saisies) > self::HERITIERS_MAX) {
            throw new ConceptionInvalideException(\sprintf('Il faut créer un ou deux héritiers (%d au maximum).', self::HERITIERS_MAX));
        }

        foreach ($saisies as $saisie) {
            if ('' === trim($saisie->nom) || null === $saisie->genre || null === $saisie->territoire) {
                throw new ConceptionInvalideException('Chaque héritier doit avoir un nom, un genre et une origine.');
            }
        }

        $age = $this->entityManager->getRepository(Age::class)->findOneBy(['label' => ConceptionRegles::AGE_HERITIER]);
        $classe = $this->entityManager->getRepository(Classe::class)->findOneBy(['secrete' => true]);
        if (!$age instanceof Age || !$classe instanceof Classe) {
            throw new ConceptionInvalideException('La tranche d\'âge « Enfant héritier » ou la classe secrète est introuvable (migrations non exécutées ?).');
        }

        $heritiers = array_map(fn (HeritierSaisie $saisie): Personnage => $this->construire($conception, $saisie, $age, $classe), $saisies);
        $this->entityManager->flush();

        $conception
            ->setStatut(ConceptionStatut::HERITIERS_CREES)
            ->setHeritiers(array_values(array_map(static fn (Personnage $heritier): int => (int) $heritier->getId(), $heritiers)));
        $this->decider(
            $conception,
            $scenariste,
            \sprintf(
                'Héritier(s) créé(s) : %s (tirage %s, suggestion : %s)',
                implode(', ', array_map(static fn (Personnage $heritier): string => \sprintf('%s n°%d', $heritier->getNom(), $heritier->getId()), $heritiers)),
                $conception->getTirage() ?? 'aucun',
                $conception->getIssue()?->label() ?? 'aucune',
            ),
        );
        $this->entityManager->flush();

        return $heritiers;
    }

    /**
     * Le scénariste gérera la création lui-même plus tard dans le jeu : la remarque est obligatoire (choix consigné).
     *
     * @throws ConceptionInvalideException
     */
    public function gererPlusTard(Conception $conception, string $remarque, User $scenariste): void
    {
        $this->terminer($conception, ConceptionStatut::A_GERER_PLUS_TARD, $remarque, $scenariste, 'Création gérée plus tard par le scénariste');
    }

    /**
     * Échec ou conséquence pour les parents : pas d'héritier, la décision est consignée avec sa remarque obligatoire.
     *
     * @throws ConceptionInvalideException
     */
    public function cloturer(Conception $conception, string $remarque, User $scenariste): void
    {
        $this->terminer($conception, ConceptionStatut::CLOTUREE, $remarque, $scenariste, 'Conception clôturée sans héritier');
    }

    private function terminer(Conception $conception, ConceptionStatut $statut, string $remarque, User $scenariste, string $libelle): void
    {
        $this->verifierDecision($conception);

        $remarque = trim($remarque);
        if ('' === $remarque) {
            throw new ConceptionInvalideException('Une remarque est obligatoire pour consigner ce choix.');
        }

        $conception->setStatut($statut)->setRemarque($remarque);
        $this->decider($conception, $scenariste, \sprintf('%s : %s', $libelle, $remarque));
        $this->entityManager->flush();
    }

    private function verifierDecision(Conception $conception): void
    {
        if (!$conception->getStatut()->estEnAttente()) {
            throw new ConceptionInvalideException('Cette conception a déjà été traitée : ' . $conception->getStatut()->label() . '.');
        }
    }

    private function decider(Conception $conception, User $scenariste, string $motif): void
    {
        $conception->setDecidePar($scenariste)->setDecideAt(new DateTime());
        $conception
            ->getDepouillement()
            ?->addHistory(
                (new DepouillementHistory())
                    ->setUser($scenariste)
                    ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                    ->setMotif($motif),
            );
    }

    private function construire(Conception $conception, HeritierSaisie $saisie, Age $age, Classe $classe): Personnage
    {
        $gn = $conception->getGn();
        $territoire = $saisie->territoire;
        $heritier = (new Personnage())
            ->setNom(trim($saisie->nom))
            ->setGenre($saisie->genre)
            ->setTerritoire($territoire)
            ->setAge($age)
            ->setAgeReel(0)
            ->setClasse($classe)
            ->setBracelet(false)
            ->setXp($gn->getXpCreation());
        $this->entityManager->persist($heritier);

        if (null !== $gn->getDateJeu()) {
            $this->entityManager->persist(
                (new PersonnageChronologie())
                    ->setAnnee($gn->getDateJeu())
                    ->setEvenement('Naissance')
                    ->setPersonnage($heritier),
            );
        }

        if ($gn->getXpCreation() > 0) {
            $this->entityManager->persist(
                (new ExperienceGain())
                    ->setExplanation('Naissance de l\'héritier')
                    ->setOperationDate(new DateTime())
                    ->setPersonnage($heritier)
                    ->setXpGain($gn->getXpCreation()),
            );
        }

        [$parent1, $parent2] = $this->parentsOrdonnes($conception);
        $this->entityManager->persist(
            (new PersonnageLignee())
                ->setPersonnage($heritier)
                ->setParent1($parent1)
                ->setParent2($parent2)
                ->setLignee($saisie->lignee),
        );

        $langues = [[$territoire?->getLangue(), LangueSourceType::ORIGINE], [$saisie->langueParents, LangueSourceType::PARENTS]];
        foreach ($langues as [$langue, $source]) {
            $personnageLangue = null === $langue ? null : $heritier->addLangueSource($langue, $source);
            if (null !== $personnageLangue) {
                $this->entityManager->persist($personnageLangue);
            }
        }

        return $heritier;
    }

    /**
     * Le premier parent est de genre 1 (ou 3), le second de genre 2 (ou 3) : on les remet dans cet ordre si besoin.
     *
     * @return array{0: Personnage, 1: Personnage}
     */
    private function parentsOrdonnes(Conception $conception): array
    {
        $parent1 = $conception->getParent1();
        $parent2 = $conception->getParent2();
        $genre1 = $parent1->getGenre()->getId();
        $genre2 = $parent2->getGenre()->getId();

        if (2 === $genre1 && 2 !== $genre2 || 1 === $genre2 && 1 !== $genre1) {
            return [$parent2, $parent1];
        }

        return [$parent1, $parent2];
    }
}
