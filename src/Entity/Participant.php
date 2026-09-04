<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LevelType;
use App\Enum\PersonnageRoleType;
use App\Repository\ParticipantRepository;
use App\Validator\ChainePersonnagesCoherente;
use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: ParticipantRepository::class)]
#[ChainePersonnagesCoherente]
class Participant extends BaseParticipant implements Stringable
{
    public function __construct()
    {
        parent::__construct();
        $this->setSubscriptionDate(new DateTime('NOW'));
    }

    public function __toString(): string
    {
        return (string) $this->getUser()?->getDisplayName();
    }

    /**
     * Verifie si le participant a répondu à cette question.
     */
    public function asAnswser(Question $q): bool
    {
        foreach ($this->getReponses() as $reponse) {
            if ($reponse->getQuestion() == $q) {
                return true;
            }
        }

        return false;
    }

    public function getAgeJoueur(): int
    {
        $gn_date = $this->getGn()->getDateFin();
        $naissance = $this->getUser()?->getEtatCivil()?->getDateNaissance();

        if (!$naissance || !$gn_date) {
            return 0;
        }

        $interval = date_diff($gn_date, $naissance);

        return (int) $interval->format('%y');
    }

    public function getBesoinValidationCi(): bool
    {
        return $this->getGn()->getBesoinValidationCi() && null == $this->getValideCiLe();
    }

    /**
     * Retourne le groupe du groupe gn associé.
     */
    public function getGroupe(): ?Groupe
    {
        if (null !== $this->getGroupeGn()) {
            return $this->getGroupeGn()->getGroupe();
        }

        return null;
    }

    /**
     * @return array<int, Potion>
     */
    public function getPotionsEnveloppe(): array
    {
        $niveauMax = $this->getPersonnage()?->getCompetenceNiveau('Alchimie');
        $i = 1;
        $potions = [];
        while ($i <= $niveauMax) {
            $potions = [...$this->getPotionsDepartByLevel($i), ...$potions];
            ++$i;
        }

        return $potions;
    }

    /** @return array<int, Potion> */
    public function getPotionsDepartByLevel(int|LevelType|null $niveau = 1): array
    {
        if (!$niveau) {
            return [];
        }

        $return = [];
        foreach ($this->getPotionsDepart() as $potion) {
            if ($niveau instanceof LevelType) {
                $niveau = $niveau->getIndex();
            }

            if ($potion->getNiveau() === $niveau) {
                $return[] = $potion;
            }
        }

        if (empty($return)) {
            $random = $this->getPotionsRandomByLevel($niveau);
            if ($random !== null) {
                $return[] = $random;
            }
        }

        return $return;
    }

    public function getPotionsRandomByLevel(int|LevelType|null $niveau = 1): ?Potion
    {
        if (!$niveau) {
            return null;
        }

        $potions = [];
        foreach ($this->getPersonnage()?->getPotions() as $potion) {
            if ($niveau instanceof LevelType) {
                $niveau = $niveau->getIndex();
            }
            if ($potion->getNiveau() === $niveau) {
                $potions[] = $potion;
            }
        }

        if (empty($potions)) {
            return null;
        }

        return $potions[random_int(0, \count($potions) - 1)];
    }

    /**
     * Fourni la session de jeu auquel participe l'utilisateur.
     */
    public function getSession(): ?GroupeGn
    {
        return $this->getGroupeGn();
    }

    /**
     * Retourne le nom complet de l'utilisateur (nom prénom).
     */
    public function getUserFullName(): string
    {
        return $this->getUser()->getFullName();
    }

    public function getUserIdentity(): string
    {
        return $this->getUser()?->getDisplayName() . ' ' . $this->getUser()?->getEmail();
    }

    public function hasPotionsDepart(Potion $potionDepart): bool
    {
        /** @var Potion $potion */
        foreach ($this->getPotionsDepart() as $potion) {
            if ($potion->getNumero() === $potionDepart->getNumero()) {
                return true;
            }
        }

        return false;
    }

    public function hasPotionsDepartByLevel(int $niveau = 1): ?Potion
    {
        foreach ($this->getPotionsDepart() as $potion) {
            if ($potion->getNiveau() === $niveau) {
                return $potion;
            }
        }

        return null;
    }

    /**
     * Retourne true si le participant a un billet PNJ, false sinon.
     */
    public function isPnj(): bool
    {
        if ($this->getBillet()) {
            return $this->getBillet()->isPnj();
        }

        return false;
    }

    /**
     * Vérifie si le joueur est responsable du groupe.
     */
    public function isResponsable(Groupe $groupe, GroupeGn $groupeGn): bool
    {
        foreach ($groupe->getGroupeGns() as $session) {
            if ($groupeGn->getId() === $session->getId() && $this->getGroupeGns()->contains($session)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retire un participant d'un groupe.
     */
    public function setGroupeGnNull(): static
    {
        $this->setGroupeGn(null);

        return $this;
    }

    /**
     * Retire un personnage du participant.
     */
    public function setPersonnageNull(): static
    {
        $this->setPersonnage(null);

        return $this;
    }

    /**
     * Le personnage réellement joué dans les instances hors temps / hors lieu.
     *
     * Ne pas choisir de personnage de substitution signifie que le personnage
     * principal endosse les deux rôles.
     */
    public function getPersonnageSubstitutionEffectif(): ?Personnage
    {
        return $this->getPersonnageSubstitution() ?? $this->getPersonnage();
    }

    /**
     * La chaîne de jeu du participant, dans l'ordre où le joueur la descend.
     *
     * Le rôle de substitution n'apparaît que si l'opus propose l'option ; l'entrée
     * correspondante peut être nulle (le principal endosse alors les deux rôles).
     *
     * La clé « libelle » porte le texte prêt à afficher, ou null si le rôle n'est
     * pas pourvu : les vues n'ont ainsi pas à savoir accorder un archétype.
     *
     * @return array<int, array{role: PersonnageRoleType, personnage: Personnage|null, archetype: PersonnageSecondaire|null, libelle: string|null}>
     */
    public function getChainePersonnages(): array
    {
        $chaine = [$this->maillonPersonnage(PersonnageRoleType::PRINCIPAL, $this->getPersonnage())];

        if ($this->getGn()->isSubstitutionActive()) {
            $chaine[] = $this->maillonPersonnage(
                PersonnageRoleType::SUBSTITUTION,
                $this->getPersonnageSubstitution(),
            );
        }

        $chaine[] = $this->maillonPersonnage(PersonnageRoleType::RELEVE, $this->getPersonnageReleve());

        $archetype = $this->getPersonnageSecondaire();
        $chaine[] = [
            'role' => PersonnageRoleType::ARCHETYPE,
            'personnage' => null,
            'archetype' => $archetype,
            'libelle' => $archetype?->getLabelPourGenre($this->getPersonnage()?->getGenreOrNull()),
        ];

        return $chaine;
    }

    /**
     * @return array{role: PersonnageRoleType, personnage: Personnage|null, archetype: null, libelle: string|null}
     */
    private function maillonPersonnage(PersonnageRoleType $role, ?Personnage $personnage): array
    {
        return [
            'role' => $role,
            'personnage' => $personnage,
            'archetype' => null,
            'libelle' => $personnage?->getIdentity(),
        ];
    }

    /**
     * Les personnages engagés par cette participation, tous rôles confondus.
     *
     * Sert au garde-fou d'unicité : un personnage ne peut tenir qu'un seul rôle
     * sur un GN donné, toutes participations confondues.
     *
     * @return array<string, Personnage> indexé par rôle
     */
    public function getPersonnagesEngages(): array
    {
        $engages = [];

        foreach ([
            PersonnageRoleType::PRINCIPAL->value => $this->getPersonnage(),
            PersonnageRoleType::SUBSTITUTION->value => $this->getPersonnageSubstitution(),
            PersonnageRoleType::RELEVE->value => $this->getPersonnageReleve(),
        ] as $role => $personnage) {
            if ($personnage instanceof Personnage) {
                $engages[$role] = $personnage;
            }
        }

        return $engages;
    }

    /**
     * Un personnage alternatif (relève, substitution, archétype) ne peut être choisi
     * que si la participation dispose d'un billet et d'un personnage principal.
     */
    public function peutChoisirPersonnageAlternatif(): bool
    {
        return null !== $this->getBillet() && null !== $this->getPersonnage();
    }

    /**
     * Le groupe du participant est-il verrouillé ? Dans ce cas la composition de la
     * chaîne de personnages n'est plus modifiable.
     */
    public function isVerrouille(): bool
    {
        return $this->getGroupeGn()?->getGroupe()?->getLock() ?? false;
    }
}
