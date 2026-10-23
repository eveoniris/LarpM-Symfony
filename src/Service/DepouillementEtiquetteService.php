<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\DepouillementHistory;
use App\Entity\Item;
use App\Entity\User;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Exception\EtiquetteInvalideException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use TypeError;

/**
 * Saisie des étiquettes d'une enveloppe : applique les règles, retrouve l'objet connu à partir du numéro (gauche) et de la
 * qualité (droite), et consigne les anomalies à signaler à un orga expert.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:too-many-methods
readonly class DepouillementEtiquetteService
{
    public const int QUANTITE_MAX = 999;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EtiquetteRegles $regles,
    ) {
    }

    /**
     * @throws EtiquetteInvalideException si le dépouillement n'est plus modifiable ou si la saisie est incomplète ou mal formée
     */
    public function ajouter(Depouillement $depouillement, EtiquetteSaisie $saisie, User $utilisateur): DepouillementEtiquette
    {
        if (!$depouillement->getStatut()->isEditable()) {
            throw new EtiquetteInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        if (EtiquetteNature::BANNIERE === $saisie->nature) {
            // Une bannière est toujours une étiquette bleue générique, seule, sans numéro ni qualité à saisir.
            $saisie->couleur = EtiquetteCouleur::BLEUE;
            $saisie->situation = EtiquetteSituation::SANS_OBJET;
            $saisie->numero = null;
            $saisie->qualite = null;
        }

        $this->completerDepuisLObjet($saisie);
        $qualite = null === $saisie->qualite || '' === trim($saisie->qualite) ? null : trim($saisie->qualite);
        $this->verifierSaisie($saisie, $qualite);

        try {
            $decision = EtiquetteNature::BANNIERE === $saisie->nature
                ? $this->regles->banniere()
                : $this->regles->evaluer($saisie->couleur, $qualite, $saisie->situation);
        } catch (InvalidArgumentException $exception) {
            throw new EtiquetteInvalideException($exception->getMessage(), previous: $exception);
        }

        $etiquette = $this->construire($saisie, $qualite, $decision);

        $depouillement->addEtiquette($etiquette);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setMotif(\sprintf('Étiquette ajoutée : %s', $this->resume($etiquette))),
        );
        $depouillement->touch();
        $this->entityManager->flush();

        return $etiquette;
    }

    public function supprimer(DepouillementEtiquette $etiquette, User $utilisateur): void
    {
        $depouillement = $etiquette->getDepouillement();

        $depouillement->removeEtiquette($etiquette);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setMotif(\sprintf('Étiquette supprimée : %s', $this->resume($etiquette))),
        );
        $depouillement->touch();
        $this->entityManager->remove($etiquette);
        $this->entityManager->flush();
    }

    /**
     * Objets connus dont le numéro ou le nom correspond à la recherche, pour aider à lire une étiquette dont on ne connaît que le nom.
     *
     * @return list<array{numero: int, label: string, couleur: string, qualite: string}>
     */
    public function rechercherObjets(string $recherche, int $limite = 15): array
    {
        $recherche = trim($recherche);
        if (mb_strlen($recherche) < 2 && !ctype_digit($recherche)) {
            return [];
        }

        $qb = $this->entityManager->getRepository(Item::class)->createQueryBuilder('i');
        $qb->where('i.label LIKE :motif')->setParameter('motif', '%' . addcslashes($recherche, characters: '%_') . '%');
        if (ctype_digit($recherche)) {
            $qb->orWhere('i.numero = :numero')->setParameter('numero', (int) $recherche);
        }

        /** @var list<Item> $objets */
        $objets = $qb->orderBy('i.numero', 'ASC')->setMaxResults($limite)->getQuery()->getResult();

        return array_map(fn (Item $objet): array => [
            'numero' => $objet->getNumero(),
            'label' => $objet->getLabel(),
            'couleur' => 'bleu' === strtolower($objet->getCouleur()) ? EtiquetteCouleur::BLEUE->value : EtiquetteCouleur::ORANGE->value,
            'qualite' => $this->qualident($objet),
        ], $objets);
    }

    /**
     * Un numéro d'objet qui n'en désigne qu'un seul rend la qualité et la couleur inutiles à saisir : elles sont reprises de l'objet
     * (le numéro est unique, donc la couleur de l'étiquette est celle de l'objet). Sans qualité saisie seulement : une qualité
     * saisie reste vérifiée contre l'objet comme avant.
     */
    private function completerDepuisLObjet(EtiquetteSaisie $saisie): void
    {
        if (EtiquetteNature::OBJET !== $saisie->nature || null === $saisie->numero || $saisie->numero < 1) {
            return;
        }

        if (null !== $saisie->qualite && '' !== trim($saisie->qualite)) {
            return;
        }

        /** @var list<Item> $objets */
        $objets = $this->entityManager->getRepository(Item::class)->findBy(['numero' => $saisie->numero], ['id' => 'ASC']);
        if (1 !== \count($objets)) {
            return;
        }

        $saisie->qualite = $this->qualident($objets[0]);
        $saisie->couleur = 'bleu' === strtolower($objets[0]->getCouleur()) ? EtiquetteCouleur::BLEUE : EtiquetteCouleur::ORANGE;
    }

    /**
     * Étiquette d'objet : numéro et qualité obligatoires. Étiquette d'arme : la qualité peut manquer pour un lot générique.
     */
    private function verifierSaisie(EtiquetteSaisie $saisie, ?string $qualite): void
    {
        if ($saisie->quantite < 1 || $saisie->quantite > self::QUANTITE_MAX) {
            throw new EtiquetteInvalideException(\sprintf('La quantité doit être comprise entre 1 et %d.', self::QUANTITE_MAX));
        }

        if (EtiquetteNature::BANNIERE === $saisie->nature) {
            return;
        }

        if (EtiquetteNature::OBJET === $saisie->nature && (null === $saisie->numero || $saisie->numero < 1)) {
            throw new EtiquetteInvalideException('Le numéro (en haut à gauche de l\'étiquette) est obligatoire pour une étiquette d\'objet.');
        }

        if (null === $qualite && (EtiquetteNature::OBJET === $saisie->nature || EtiquetteCouleur::BLEUE === $saisie->couleur)) {
            throw new EtiquetteInvalideException(
                'La qualité (trois chiffres, en haut à droite de l\'étiquette) est obligatoire, sauf quand le numéro ne désigne qu\'un seul objet connu : plusieurs objets portent ce numéro ou il est inconnu.',
            );
        }
    }

    /**
     * Une étiquette d'objet est rattachée à l'objet connu quand le numéro et la qualité le désignent sans ambiguïté.
     */
    private function construire(EtiquetteSaisie $saisie, ?string $qualite, EtiquetteDecision $decision): DepouillementEtiquette
    {
        $anomalies = $decision->anomalies;
        $item = null;

        if (EtiquetteNature::OBJET === $saisie->nature) {
            [$item, $anomaliesObjet] = $this->retrouverObjet((int) $saisie->numero, (string) $qualite, $saisie->couleur);
            $anomalies = [...$anomalies, ...$anomaliesObjet];
        }

        return (new DepouillementEtiquette())
            ->setNature($saisie->nature)
            ->setCouleur($saisie->couleur)
            ->setNumero($saisie->numero)
            ->setQualite($qualite)
            ->setQuantite(EtiquetteNature::OBJET === $saisie->nature ? 1 : $saisie->quantite)
            ->setSituation($saisie->situation)
            ->setItem($item)
            ->setDestination($decision->destination)
            ->setActionObjet($decision->actionObjet)
            ->setSpeciale($decision->speciale)
            ->setAPrevoirProchainOpus($decision->aPrevoirProchainOpus)
            ->setAnomalies($anomalies)
            ->setRemarque(null === $saisie->remarque || '' === trim($saisie->remarque) ? null : trim($saisie->remarque));
    }

    /**
     * @return array{0: ?Item, 1: list<string>} l'objet connu (ou null) et les anomalies à signaler
     */
    private function retrouverObjet(int $numero, string $qualite, EtiquetteCouleur $couleur): array
    {
        /** @var list<Item> $objets */
        $objets = $this->entityManager->getRepository(Item::class)->findBy(['numero' => $numero], ['id' => 'ASC']);
        $conseil = 'solliciter un orga expert et/ou le consigner dans les remarques.';

        if ([] === $objets) {
            return [null, [\sprintf('Aucun objet n°%d en base : %s', $numero, $conseil)]];
        }

        $correspondants = array_values(array_filter($objets, fn (Item $objet): bool => $this->qualident($objet) === $qualite));

        if (1 === \count($correspondants)) {
            $objet = $correspondants[0];
            $anomalies = [];
            if ($this->couleurObjet($couleur) !== strtolower($objet->getCouleur())) {
                $anomalies[] = \sprintf('L\'objet n°%d est de couleur %s en base mais l\'étiquette saisie est %s : %s', $numero, $objet->getCouleur(), $couleur->value, $conseil);
            }

            return [$objet, $anomalies];
        }

        if ([] === $correspondants && 1 === \count($objets)) {
            return [null, [\sprintf('L\'objet n°%d a la qualité %s en base, l\'étiquette indique %s : %s', $numero, $this->qualident($objets[0]), $qualite, $conseil)]];
        }

        $message = [] === $correspondants
            ? \sprintf('Plusieurs objets portent le n°%d et aucun n\'a la qualité %s : %s', $numero, $qualite, $conseil)
            : \sprintf('Plusieurs objets portent le n°%d avec la qualité %s : %s', $numero, $qualite, $conseil);

        return [null, [$message]];
    }

    private function qualident(Item $objet): string
    {
        try {
            return $objet->getQualident();
        } catch (TypeError) {
            // Objet sans qualité en base : seule l'identification est connue.
            return $objet->getIdentification();
        }
    }

    private function couleurObjet(EtiquetteCouleur $couleur): string
    {
        return EtiquetteCouleur::BLEUE === $couleur ? 'bleu' : 'orange';
    }

    private function resume(DepouillementEtiquette $etiquette): string
    {
        return \sprintf(
            '%s %s%s%s',
            $etiquette->getNature()->label(),
            $etiquette->getCouleur()->value,
            null === $etiquette->getNumero() ? '' : \sprintf(' n°%d', $etiquette->getNumero()),
            null === $etiquette->getQualite() ? \sprintf(' (x%d génériques)', $etiquette->getQuantite()) : \sprintf(' qualité %s', $etiquette->getQualite()),
        );
    }
}
