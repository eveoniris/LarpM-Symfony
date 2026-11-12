<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Espece;
use App\Enum\ConceptionIssue;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Exception\ConceptionInvalideException;
use App\Exception\EnveloppeIntrouvableException;
use App\Repository\ConceptionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre les conceptions d'héritier signalées dans une enveloppe de joueur : contrôle les parents, tire le d20 une seule
 * fois et consigne le résultat. La décision (créer l'héritier ou non) revient ensuite au scénariste.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:too-many-methods
readonly class ConceptionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConceptionRepository $conceptionRepository,
        private DepouillementEnveloppeResolver $resolver,
        private ConceptionRegles $regles,
    ) {
    }

    /**
     * @param string                         $numeroPartenaire numéro de personnage du partenaire, tel que saisi
     * @param string|null $note note complémentaire précisée par les joueurs pour cette conception
     *
     * @throws ConceptionInvalideException
     */
    public function enregistrer(Depouillement $depouillement, string $numeroPartenaire, User $utilisateur, ?string $note = null, ?string $nomPropose = null): Conception
    {
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            throw new ConceptionInvalideException('Une conception se signale dans une enveloppe de joueur.');
        }

        if (!$depouillement->getStatut()->isEditable()) {
            throw new ConceptionInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        $parent1 = $this->personnageEnveloppe($depouillement);
        $parent2 = $this->partenaire($depouillement, $numeroPartenaire);

        if ($parent1->getId() === $parent2->getId()) {
            throw new ConceptionInvalideException('Les deux parents doivent être deux personnages différents.');
        }

        if (null !== $this->conceptionRepository->findParCouple($depouillement->getGn(), $parent1, $parent2, $depouillement->isSimulation())) {
            throw new ConceptionInvalideException('Cette conception est déjà enregistrée pour ce couple (l\'autre joueur l\'a signalée).');
        }

        $anomaliesEspece = array_values(array_filter([$this->anomalieEspece($parent1), $this->anomalieEspece($parent2)]));
        $anomalies = array_values(array_filter([
            $this->regles->anomalieAge($this->nom($parent1), $parent1->getAgeReel()),
            $this->regles->anomalieAge($this->nom($parent2), $parent2->getAgeReel()),
            $this->regles->anomalieGenres($this->nom($parent1), $parent1->getGenreOrNull()?->getId(), $this->nom($parent2), $parent2->getGenreOrNull()?->getId()),
            ...$anomaliesEspece,
        ]));

        $conception = (new Conception())
            ->setGn($depouillement->getGn())
            ->setParent1($parent1)
            ->setParent2($parent2)
            ->setSimulation($depouillement->isSimulation())
            ->setAnomalies($anomalies)
            ->setNote(null === $note || '' === trim($note) ? null : trim($note))
            ->setNomPropose(null === $nomPropose || '' === trim($nomPropose) ? null : mb_substr(trim($nomPropose), start: 0, length: 100))
            ->setCreePar($utilisateur);

        // Parent non humain : échec d'office, sans tirage.
        if ([] !== $anomaliesEspece) {
            $conception->setIssue(ConceptionIssue::ECHEC);
        }

        // Le d20 n'est plus tiré ici : c'est une aide pour le staff, qui le demande depuis l'écran des conceptions (voir tirer()).
        $depouillement->addConception($conception);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setMotif($this->resume($conception)),
        );
        $depouillement->touch();
        $this->entityManager->persist($conception);
        $this->entityManager->flush();

        return $conception;
    }

    /**
     * Tire le d20 à la demande du staff (simple suggestion). Une seule fois par conception, jamais refait.
     *
     * @param (callable(int, int): int)|null $sourceTirage source de nombres du d20 (tests)
     *
     * @throws ConceptionInvalideException
     */
    public function tirer(Conception $conception, User $staff, ?callable $sourceTirage = null): Conception
    {
        if (!$conception->getStatut()->estEnAttente()) {
            throw new ConceptionInvalideException('Le scénariste a déjà décidé : le tirage n\'est plus possible.');
        }

        if (null !== $conception->getTirage()) {
            throw new ConceptionInvalideException('Le tirage est déjà fait pour cette conception.');
        }

        if ([] !== $conception->getAnomalies()) {
            throw new ConceptionInvalideException('Pas de tirage possible tant qu\'une anomalie est signalée (âge, genre ou espèce d\'un parent).');
        }

        $tirage = $this->regles->tirer($sourceTirage);
        $conception->setTirage($tirage)->setIssue($this->regles->issue($tirage));

        $depouillement = $conception->getDepouillement();
        if (null !== $depouillement) {
            $depouillement->addHistory(
                (new DepouillementHistory())
                    ->setUser($staff)
                    ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                    ->setMotif(\sprintf('Tirage demandé par le staff : %s, %s', $this->couple($conception), $this->resumeTirage($conception))),
            );
            $depouillement->touch();
        }

        $this->entityManager->flush();

        return $conception;
    }

    /**
     * Retire une conception encore en attente (erreur de saisie) tant que la saisie est modifiable.
     *
     * @throws ConceptionInvalideException
     */
    public function supprimer(Conception $conception, User $utilisateur): void
    {
        $depouillement = $conception->getDepouillement();
        if (null === $depouillement || !$depouillement->getStatut()->isEditable()) {
            throw new ConceptionInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        if (!$conception->getStatut()->estEnAttente()) {
            throw new ConceptionInvalideException('Le scénariste a déjà décidé : cette conception ne peut plus être retirée.');
        }

        $depouillement->removeConception($conception);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setMotif(\sprintf('Conception retirée : %s', $this->couple($conception))),
        );
        $depouillement->touch();
        $this->entityManager->remove($conception);
        $this->entityManager->flush();
    }

    /**
     * Le personnage de l'enveloppe est celui dont le numéro est inscrit dessus (le participant peut avoir une substitution ou un relève).
     */
    private function personnageEnveloppe(Depouillement $depouillement): Personnage
    {
        $participant = $depouillement->getParticipant();
        if (null === $participant) {
            throw new ConceptionInvalideException('Cette enveloppe n\'est rattachée à aucun personnage.');
        }

        return $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero()) ?? throw new ConceptionInvalideException('Le personnage de cette enveloppe est introuvable.');
    }

    private function partenaire(Depouillement $depouillement, string $saisie): Personnage
    {
        try {
            $numero = $this->resolver->normaliserNumero($saisie);
            $participant = $this->resolver->resoudre($depouillement->getGn(), DepouillementType::JOUEUR, $numero);
        } catch (EnveloppeIntrouvableException $exception) {
            throw new ConceptionInvalideException($exception->getMessage(), previous: $exception);
        }

        if (!$participant instanceof Participant) {
            throw new ConceptionInvalideException('Le partenaire doit être un personnage inscrit à ce GN.');
        }

        return $this->resolver->personnageDuNumero($participant, (int) $numero) ?? throw new ConceptionInvalideException(\sprintf('Le personnage n°%s est introuvable.', $numero));
    }

    private function anomalieEspece(Personnage $personnage): ?string
    {
        $noms = array_values(array_filter(
            array_map(static fn (Espece $espece): ?string => $espece->getNom(), $personnage->getEspeces()->toArray()),
            static fn (?string $nom): bool => null !== $nom && '' !== trim($nom),
        ));

        return $this->regles->anomalieEspece($this->nom($personnage), $noms);
    }

    private function nom(Personnage $personnage): string
    {
        return \sprintf('%s (n°%d)', $personnage->getNom(), $personnage->getId());
    }

    private function couple(Conception $conception): string
    {
        return \sprintf('%s et %s', $this->nom($conception->getParent1()), $this->nom($conception->getParent2()));
    }

    private function resume(Conception $conception): string
    {
        return \sprintf('Conception enregistrée : %s, %s', $this->couple($conception), $this->resumeTirage($conception));
    }

    private function resumeTirage(Conception $conception): string
    {
        return null === $conception->getTirage()
            ? 'pas de tirage pour l\'instant'
            : \sprintf('tirage %d, %s', $conception->getTirage(), $conception->getIssue()?->label());
    }
}
