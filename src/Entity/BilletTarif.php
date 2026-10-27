<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BilletTarifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Correspondance, pour un GN, entre un produit HelloAsso (éventuellement à un prix donné) et un billet LarpManager.
 * Plusieurs produits peuvent mener au même billet, et un produit à plusieurs billets selon le prix payé.
 */
#[ORM\Entity(repositoryClass: BilletTarifRepository::class)]
#[ORM\Table(name: 'billet_tarif')]
#[ORM\UniqueConstraint(name: 'uniq_billet_tarif', columns: ['gn_id', 'product_id', 'price'])]
class BilletTarif
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Gn $gn = null;

    #[ORM\Column(name: 'product_id', type: Types::STRING, length: 64)]
    private string $productId = '';

    /** Prix en centimes ; null = quel que soit le prix. */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $price = null;

    #[ORM\ManyToOne(targetEntity: Billet::class)]
    #[ORM\JoinColumn(name: 'billet_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Billet $billet = null;

    /** Si le participant a aussi une autre ligne payée, le billet de ce tarif l'emporte (ex. place solidaire). */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $prioritaire = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    public function setGn(?Gn $gn): static
    {
        $this->gn = $gn;

        return $this;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getBillet(): ?Billet
    {
        return $this->billet;
    }

    public function setBillet(?Billet $billet): static
    {
        $this->billet = $billet;

        return $this;
    }

    public function isPrioritaire(): bool
    {
        return $this->prioritaire;
    }

    public function setPrioritaire(bool $prioritaire): static
    {
        $this->prioritaire = $prioritaire;

        return $this;
    }
}
