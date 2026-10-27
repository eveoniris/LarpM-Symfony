<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Competence;
use App\Entity\Depouillement;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\DepouillementType;
use App\Exception\EnveloppeIntrouvableException;

/**
 * Règles d'un apprentissage de compétence déclaré dans un retour de jeu (les mêmes que la saisie d'un orga) : l'enseignant est un
 * personnage inscrit à ce GN, il connaît la compétence et la maîtrise au moins au niveau expert, la compétence est accessible à
 * l'élève, et personne ne s'enseigne à soi-même.
 *
 * Utilisé à la saisie (pour avertir tout de suite le dépouilleur) et à la validation (pour décider d'enregistrer ou non).
 */
readonly class ControleApprentissage
{
    public function __construct(
        private DepouillementEnveloppeResolver $resolver,
        private PersonnageService $personnageService,
    ) {
    }

    /** L'enseignant est un personnage inscrit à ce GN, retrouvé par son numéro. */
    public function enseignant(Depouillement $depouillement, ?int $numero): ?Personnage
    {
        if (null === $numero || $numero < 1) {
            return null;
        }

        try {
            $participant = $this->resolver->resoudre($depouillement->getGn(), DepouillementType::JOUEUR, (string) $numero);
        } catch (EnveloppeIntrouvableException) {
            return null;
        }

        return $participant instanceof Participant ? $this->resolver->personnageDuNumero($participant, $numero) : null;
    }

    /**
     * Raison pour laquelle l'apprentissage est refusé, ou null s'il respecte les règles.
     */
    public function refus(Personnage $eleve, Competence $competence, ?Personnage $enseignant, ?int $numeroEnseignant): ?string
    {
        return match (true) {
            null === $enseignant => \sprintf('l\'enseignant n°%s est introuvable parmi les personnages inscrits à ce GN', $numeroEnseignant ?? '?'),
            $enseignant->getId() === $eleve->getId() => 'un personnage ne peut pas s\'enseigner à lui-même',
            !$this->personnageService->canTeachCompetence($enseignant, $competence) => \sprintf(
                '%s doit connaître cette compétence au moins au niveau expert pour l\'enseigner',
                $enseignant->getNom(),
            ),
            !$this->personnageService->getApprentissageCompetences($eleve)->contains($competence) => \sprintf(
                'elle ne fait pas partie des compétences actuellement accessibles pour %s',
                $eleve->getNom(),
            ),
            default => null,
        };
    }

    /**
     * Avertissement à afficher dès la saisie quand l'apprentissage déclaré ne sera pas enregistré à la validation (null sinon).
     */
    public function avertissement(Depouillement $depouillement, ContenuJoueur $contenu): ?string
    {
        $competence = $contenu->competenceApprise;
        $participant = $depouillement->getParticipant();
        if (null === $competence || null === $participant) {
            return null;
        }

        $eleve = $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero());
        if (null === $eleve) {
            return null;
        }

        $refus = $this->refus($eleve, $competence, $this->enseignant($depouillement, $contenu->enseignantNumero), $contenu->enseignantNumero);

        return null === $refus ? null : \sprintf('Apprentissage de la compétence %s : il ne sera pas enregistré à la validation car %s.', $competence->getLabel(), $refus);
    }
}
