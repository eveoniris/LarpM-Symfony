<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageHasToken.
 *
 * @Table(name="personnage_has_token", indexes={@Index(name="fk_personnage_has_token_token1_idx", columns={"token_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageHasToken", "extended":"PersonnageHasToken"})
 */
class BasePersonnageHasToken
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageHasTokens")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="Token", inversedBy="personnageHasTokens")
     *
     * @JoinColumn(name="token_id", referencedColumnName="id", nullable=false)
     */
    protected $token;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageHasToken
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageHasToken
     */
    public function setPersonnage(Personnage $personnage = null)
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getPersonnage()
    {
        return $this->personnage;
    }

    /**
     * Set Token entity (many to one).
     *
     * @return \App\Entity\PersonnageHasToken
     */
    public function setToken(Token $token = null)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get Token entity (many to one).
     *
     * @return \App\Entity\Token
     */
    public function getToken()
    {
        return $this->token;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'token_id'];
    }
}
