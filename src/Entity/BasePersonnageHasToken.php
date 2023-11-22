<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_has_token')]
#[ORM\Index(columns: ['token_id'], name: 'fk_personnage_has_token_token1_idx')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_billet_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageHasToken', 'extended' => 'PersonnageHasToken'])]
abstract class BasePersonnageHasToken
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageHasTokens')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: Token::class, inversedBy: 'personnageHasTokens')]
    #[JoinColumn(name: 'token_id', referencedColumnName: 'id', nullable: 'false')]
    protected Token $token;

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Token entity (many to one).
     */
    public function setToken(Token $token = null): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get Token entity (many to one).
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'token_id'];
    }
}
