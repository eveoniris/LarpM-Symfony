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
 * Déclaration du joueur : ce qu'il annonce de son retour de jeu en plus du contenu de l'enveloppe.
 *
 * Gardée dans `donnees['declaration']`, avec la provenance et l'état de chaque champ dans `donnees['decisions']`
 * (voir DecisionsDeclaration). Rien n'est appliqué au personnage avant la validation.
 */
readonly class DepouillementDeclarationService
{
    public const string CLE = 'declaration';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function lire(Depouillement $depouillement): DeclarationJoueur
    {
        /** @var array<string, mixed> $donnees */
        $donnees = $depouillement->getDonnees()[self::CLE] ?? [];

        return DeclarationJoueur::depuisTableau($donnees);
    }

    /**
     * @param bool $parJoueur true quand c'est le joueur qui enregistre : ses ajouts attendent la décision d'un responsable
     *
     * @throws ContenuInvalideException si le dossier n'est pas celui d'un joueur ou n'est plus modifiable
     */
    public function enregistrer(Depouillement $depouillement, DeclarationJoueur $declaration, User $utilisateur, bool $parJoueur): void
    {
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            throw new ContenuInvalideException('Cette déclaration ne concerne que les dossiers de joueur.');
        }

        if (!$depouillement->getStatut()->isEditable()) {
            throw new ContenuInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        $avant = $depouillement->getDonnees()[self::CLE] ?? null;
        $apres = $declaration->versTableau();
        if ($avant === $apres) {
            return;
        }

        $decisions = DecisionsDeclaration::suivre(
            DecisionsDeclaration::depuis($depouillement->getDonnees()),
            \is_array($avant) ? $avant : [],
            $apres,
            new AuteurDeclaration($parJoueur, (int) $utilisateur->getId(), new DateTimeImmutable()),
        );

        $depouillement->setDonnees([...$depouillement->getDonnees(), self::CLE => $apres, 'decisions' => $decisions]);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(null === $avant ? null : [self::CLE => $avant])
                ->setDataAfter([self::CLE => $apres])
                ->setMotif($parJoueur ? 'Déclaration du joueur enregistrée' : 'Déclaration du joueur complétée par le dépouillement'),
        );
        $depouillement->touch();
        $this->entityManager->flush();
    }
}
