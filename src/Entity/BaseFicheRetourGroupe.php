<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity]
#[ORM\Table(name: 'fiche_retour_groupe')]
#[ORM\UniqueConstraint(name: 'uq_fiche_retour_groupe_gn', columns: ['groupe_gn_id'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseFicheRetourGroupe', 'extended' => 'FicheRetourGroupe'])]
abstract class BaseFicheRetourGroupe
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: GroupeGn::class)]
    #[JoinColumn(name: 'groupe_gn_id', referencedColumnName: 'id', nullable: false)]
    protected GroupeGn $groupeGn;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $submitted_at = null;

    #[Column(type: Types::INTEGER)]
    protected int $pieces_argent = 0;

    #[Column(type: Types::INTEGER)]
    protected int $pieces_or = 0;

    #[Column(type: Types::INTEGER)]
    protected int $nb_ingredients = 0;

    #[Column(type: Types::INTEGER)]
    protected int $nb_potions = 0;

    #[Column(type: Types::INTEGER)]
    protected int $armement = 0;

    #[Column(type: Types::INTEGER)]
    protected int $chevaux = 0;

    #[Column(type: Types::INTEGER)]
    protected int $fruits_legumes = 0;

    #[Column(type: Types::INTEGER)]
    protected int $matieres_simples = 0;

    #[Column(type: Types::INTEGER)]
    protected int $sel = 0;

    #[Column(type: Types::INTEGER)]
    protected int $betail = 0;

    #[Column(type: Types::INTEGER)]
    protected int $coton = 0;

    #[Column(type: Types::INTEGER)]
    protected int $gemmes = 0;

    #[Column(type: Types::INTEGER)]
    protected int $moutons = 0;

    #[Column(type: Types::INTEGER)]
    protected int $soie = 0;

    #[Column(type: Types::INTEGER)]
    protected int $bois = 0;

    #[Column(type: Types::INTEGER)]
    protected int $esclaves = 0;

    #[Column(type: Types::INTEGER)]
    protected int $ivoire = 0;

    #[Column(type: Types::INTEGER)]
    protected int $pierre = 0;

    #[Column(type: Types::INTEGER)]
    protected int $teinture = 0;

    #[Column(type: Types::INTEGER)]
    protected int $cereales = 0;

    #[Column(type: Types::INTEGER)]
    protected int $fourrures = 0;

    #[Column(type: Types::INTEGER)]
    protected int $matieres_precieux = 0;

    #[Column(type: Types::INTEGER)]
    protected int $poisson = 0;

    #[Column(type: Types::INTEGER)]
    protected int $vin = 0;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $commentaire = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected DateTimeInterface $created_at;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected DateTimeInterface $updated_at;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true)]
    protected ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[JoinColumn(name: 'updated_by_id', referencedColumnName: 'id', nullable: true)]
    protected ?User $updatedBy = null;

    public function __construct()
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupeGn(): GroupeGn
    {
        return $this->groupeGn;
    }

    public function setGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGn = $groupeGn;

        return $this;
    }

    public function getSubmittedAt(): ?DateTimeInterface
    {
        return $this->submitted_at;
    }

    public function setSubmittedAt(?DateTimeInterface $submitted_at): static
    {
        $this->submitted_at = $submitted_at;

        return $this;
    }

    public function getPiecesArgent(): int
    {
        return $this->pieces_argent;
    }

    public function setPiecesArgent(int $pieces_argent): static
    {
        $this->pieces_argent = $pieces_argent;

        return $this;
    }

    public function getPiecesOr(): int
    {
        return $this->pieces_or;
    }

    public function setPiecesOr(int $pieces_or): static
    {
        $this->pieces_or = $pieces_or;

        return $this;
    }

    public function getNbIngredients(): int
    {
        return $this->nb_ingredients;
    }

    public function setNbIngredients(int $nb_ingredients): static
    {
        $this->nb_ingredients = $nb_ingredients;

        return $this;
    }

    public function getNbPotions(): int
    {
        return $this->nb_potions;
    }

    public function setNbPotions(int $nb_potions): static
    {
        $this->nb_potions = $nb_potions;

        return $this;
    }

    public function getArmement(): int
    {
        return $this->armement;
    }

    public function setArmement(int $armement): static
    {
        $this->armement = $armement;

        return $this;
    }

    public function getChevaux(): int
    {
        return $this->chevaux;
    }

    public function setChevaux(int $chevaux): static
    {
        $this->chevaux = $chevaux;

        return $this;
    }

    public function getFruitsLegumes(): int
    {
        return $this->fruits_legumes;
    }

    public function setFruitsLegumes(int $fruits_legumes): static
    {
        $this->fruits_legumes = $fruits_legumes;

        return $this;
    }

    public function getMatieresSimples(): int
    {
        return $this->matieres_simples;
    }

    public function setMatieresSimples(int $matieres_simples): static
    {
        $this->matieres_simples = $matieres_simples;

        return $this;
    }

    public function getSel(): int
    {
        return $this->sel;
    }

    public function setSel(int $sel): static
    {
        $this->sel = $sel;

        return $this;
    }

    public function getBetail(): int
    {
        return $this->betail;
    }

    public function setBetail(int $betail): static
    {
        $this->betail = $betail;

        return $this;
    }

    public function getCoton(): int
    {
        return $this->coton;
    }

    public function setCoton(int $coton): static
    {
        $this->coton = $coton;

        return $this;
    }

    public function getGemmes(): int
    {
        return $this->gemmes;
    }

    public function setGemmes(int $gemmes): static
    {
        $this->gemmes = $gemmes;

        return $this;
    }

    public function getMoutons(): int
    {
        return $this->moutons;
    }

    public function setMoutons(int $moutons): static
    {
        $this->moutons = $moutons;

        return $this;
    }

    public function getSoie(): int
    {
        return $this->soie;
    }

    public function setSoie(int $soie): static
    {
        $this->soie = $soie;

        return $this;
    }

    public function getBois(): int
    {
        return $this->bois;
    }

    public function setBois(int $bois): static
    {
        $this->bois = $bois;

        return $this;
    }

    public function getEsclaves(): int
    {
        return $this->esclaves;
    }

    public function setEsclaves(int $esclaves): static
    {
        $this->esclaves = $esclaves;

        return $this;
    }

    public function getIvoire(): int
    {
        return $this->ivoire;
    }

    public function setIvoire(int $ivoire): static
    {
        $this->ivoire = $ivoire;

        return $this;
    }

    public function getPierre(): int
    {
        return $this->pierre;
    }

    public function setPierre(int $pierre): static
    {
        $this->pierre = $pierre;

        return $this;
    }

    public function getTeinture(): int
    {
        return $this->teinture;
    }

    public function setTeinture(int $teinture): static
    {
        $this->teinture = $teinture;

        return $this;
    }

    public function getCereales(): int
    {
        return $this->cereales;
    }

    public function setCereales(int $cereales): static
    {
        $this->cereales = $cereales;

        return $this;
    }

    public function getFourrures(): int
    {
        return $this->fourrures;
    }

    public function setFourrures(int $fourrures): static
    {
        $this->fourrures = $fourrures;

        return $this;
    }

    public function getMatieresPrecieux(): int
    {
        return $this->matieres_precieux;
    }

    public function setMatieresPrecieux(int $matieres_precieux): static
    {
        $this->matieres_precieux = $matieres_precieux;

        return $this;
    }

    public function getPoisson(): int
    {
        return $this->poisson;
    }

    public function setPoisson(int $poisson): static
    {
        $this->poisson = $poisson;

        return $this;
    }

    public function getVin(): int
    {
        return $this->vin;
    }

    public function setVin(int $vin): static
    {
        $this->vin = $vin;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'pieces_argent' => $this->pieces_argent,
            'pieces_or' => $this->pieces_or,
            'nb_ingredients' => $this->nb_ingredients,
            'nb_potions' => $this->nb_potions,
            'armement' => $this->armement,
            'chevaux' => $this->chevaux,
            'fruits_legumes' => $this->fruits_legumes,
            'matieres_simples' => $this->matieres_simples,
            'sel' => $this->sel,
            'betail' => $this->betail,
            'coton' => $this->coton,
            'gemmes' => $this->gemmes,
            'moutons' => $this->moutons,
            'soie' => $this->soie,
            'bois' => $this->bois,
            'esclaves' => $this->esclaves,
            'ivoire' => $this->ivoire,
            'pierre' => $this->pierre,
            'teinture' => $this->teinture,
            'cereales' => $this->cereales,
            'fourrures' => $this->fourrures,
            'matieres_precieux' => $this->matieres_precieux,
            'poisson' => $this->poisson,
            'vin' => $this->vin,
            'commentaire' => $this->commentaire,
        ];
    }
}
