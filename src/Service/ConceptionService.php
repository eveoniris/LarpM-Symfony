<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
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
     * @param (callable(int, int): int)|null $sourceTirage     source de nombres du d20 (tests)
     *
     * @throws ConceptionInvalideException
     */
    public function enregistrer(Depouillement $depouillement, string $numeroPartenaire, User $utilisateur, ?callable $sourceTirage = null): Conception
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

        $anomalies = array_values(array_filter([
            $this->regles->anomalieAge($this->nom($parent1), $parent1->getAgeReel()),
            $this->regles->anomalieAge($this->nom($parent2), $parent2->getAgeReel()),
        ]));

        $conception = (new Conception())
            ->setGn($depouillement->getGn())
            ->setParent1($parent1)
            ->setParent2($parent2)
            ->setSimulation($depouillement->isSimulation())
            ->setAnomalies($anomalies)
            ->setCreePar($utilisateur);

        if ([] === $anomalies) {
            $tirage = $this->regles->tirer($sourceTirage);
            $conception->setTirage($tirage)->setIssue($this->regles->issue($tirage));
        }

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

        return $this->personnageDuNumero($participant, (int) $depouillement->getNumero()) ?? throw new ConceptionInvalideException('Le personnage de cette enveloppe est introuvable.');
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

        return $this->personnageDuNumero($participant, (int) $numero) ?? throw new ConceptionInvalideException(\sprintf('Le personnage n°%s est introuvable.', $numero));
    }

    private function personnageDuNumero(Participant $participant, int $numero): ?Personnage
    {
        foreach ([$participant->getPersonnage(), $participant->getPersonnageSubstitution(), $participant->getPersonnageReleve()] as $personnage) {
            if ($personnage instanceof Personnage && $personnage->getId() === $numero) {
                return $personnage;
            }
        }

        return null;
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
        $suite = null === $conception->getTirage()
            ? 'pas de tirage (âge)'
            : \sprintf('tirage %d, %s', $conception->getTirage(), $conception->getIssue()?->label());

        return \sprintf('Conception enregistrée : %s, %s', $this->couple($conception), $suite);
    }
}
