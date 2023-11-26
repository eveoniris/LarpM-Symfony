<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'participant')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseParticipant', 'extended' => 'Participant'])]
class BaseParticipant
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $subscription_date = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected \DateTime $billet_date;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $valide_ci_le = null;

    #[OneToMany(mappedBy: 'participant', targetEntity: GroupeGn::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'responsable_id', nullable: 'false')]
    protected Collection $groupeGns;

    #[OneToMany(mappedBy: 'participant', targetEntity: ParticipantHasRestauration::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'participant_id', nullable: 'false')]
    protected Collection $participantHasRestaurations;

    #[OneToMany(mappedBy: 'participant', targetEntity: Reponse::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'participant_id', nullable: 'false')]
    protected Collection $reponses;

    #[ManyToOne(inversedBy: 'participant', targetEntity: Gn::class, cascade: ['persist'])]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: 'false')]
    protected Gn $gn;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    #[ManyToOne(targetEntity: PersonnageSecondaire::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'personnage_secondaire_id', referencedColumnName: 'id', nullable: 'false')]
    protected PersonnageSecondaire $personnageSecondaire;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: Billet::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'billet_id', referencedColumnName: 'id')]
    protected ?Billet $billet = null;

    #[ManyToOne(targetEntity: GroupeGn::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'groupe_gn_id', referencedColumnName: 'id')]
    protected GroupeGn $groupeGn;

    #[ORM\ManyToMany(targetEntity: Potion::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'participant_potions_depart')]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'potion_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OrderBy(['label' => 'ASC', 'niveau' => 'ASC'])]
    protected Collection $potions_depart;

    public function __construct()
    {
        $this->groupeGns = new ArrayCollection();
        $this->participantHasRestaurations = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->potions_depart = new ArrayCollection();
    }

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
     * Set the value of subscription_date.
     *
     * @param \DateTime $subscription_date
     */
    public function setSubscriptionDate(?\DateTimeInterface $subscription_date): static
    {
        $this->subscription_date = $subscription_date;

        return $this;
    }

    /**
     * Get the value of subscription_date.
     */
    public function getSubscriptionDate(): \DateTime
    {
        return $this->subscription_date;
    }

    /**
     * Set the value of billet_date.
     *
     * @param \DateTime $billet_date
     */
    public function setBilletDate(string $billet_date): static
    {
        $this->billet_date = $billet_date;

        return $this;
    }

    /**
     * Get the value of billet_date.
     */
    public function getBilletDate(): \DateTime
    {
        return $this->billet_date;
    }

    /**
     * Set the value of valide_ci_le.
     *
     * @param \DateTime $valide_ci_le
     */
    public function setValideCiLe(?\DateTimeInterface $valide_ci_le): static
    {
        $this->valide_ci_le = $valide_ci_le;

        return $this;
    }

    /**
     * Get the value of valide_ci_le.
     */
    public function getValideCiLe(): \DateTime
    {
        return $this->valide_ci_le;
    }

    /**
     * Add GroupeGn entity to collection (one to many).
     */
    public function addGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGns[] = $groupeGn;

        return $this;
    }

    /**
     * Remove GroupeGn entity from collection (one to many).
     */
    public function removeGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGns->removeElement($groupeGn);

        return $this;
    }

    /**
     * Get GroupeGn entity collection (one to many).
     */
    public function getGroupeGns(): Collection
    {
        return $this->groupeGns;
    }

    /**
     * Add ParticipantHasRestauration entity to collection (one to many).
     */
    public function addParticipantHasRestauration(ParticipantHasRestauration $participantHasRestauration): static
    {
        $this->participantHasRestaurations[] = $participantHasRestauration;

        return $this;
    }

    /**
     * Remove ParticipantHasRestauration entity from collection (one to many).
     */
    public function removeParticipantHasRestauration(ParticipantHasRestauration $participantHasRestauration): static
    {
        $this->participantHasRestaurations->removeElement($participantHasRestauration);

        return $this;
    }

    /**
     * Get ParticipantHasRestauration entity collection (one to many).
     */
    public function getParticipantHasRestaurations(): Collection
    {
        return $this->participantHasRestaurations;
    }

    /**
     * Add Reponse entity to collection (one to many).
     */
    public function addReponse(Reponse $reponse): static
    {
        $this->reponses[] = $reponse;

        return $this;
    }

    /**
     * Remove Reponse entity from collection (one to many).
     */
    public function removeReponse(Reponse $reponse): static
    {
        $this->reponses->removeElement($reponse);

        return $this;
    }

    /**
     * Get Reponse entity collection (one to many).
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    /**
     * Set Gn entity (many to one).
     */
    public function setGn(Gn $gn = null): static
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get Gn entity (many to one).
     */
    public function getGn(): Gn
    {
        return $this->gn;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set PersonnageSecondaire entity (many to one).
     */
    public function setPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire = null): static
    {
        $this->personnageSecondaire = $personnageSecondaire;

        return $this;
    }

    /**
     * Get PersonnageSecondaire entity (many to one).
     */
    public function getPersonnageSecondaire(): PersonnageSecondaire
    {
        return $this->personnageSecondaire;
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
     * Set Billet entity (many to one).
     */
    public function setBillet(Billet $billet = null): static
    {
        $this->billet = $billet;

        return $this;
    }

    /**
     * Get Billet entity (many to one).
     */
    public function getBillet(): Billet
    {
        return $this->billet;
    }

    /**
     * Set GroupeGn entity (many to one).
     */
    public function setGroupeGn(GroupeGn $groupeGn = null): static
    {
        $this->groupeGn = $groupeGn;

        return $this;
    }

    /**
     * Get GroupeGn entity (many to one).
     */
    public function getGroupeGn(): GroupeGn
    {
        return $this->groupeGn;
    }

    /**
     * Add Potion entity to collection.
     */
    public function addPotionDepart(Potion $potion): static
    {
        $this->potions_depart[] = $potion;

        return $this;
    }

    /**
     * Remove Potion entity from collection.
     */
    public function removePotionDepart(Potion $potion): static
    {
        $this->potions_depart->removeElement($potion);

        return $this;
    }

    /**
     * Get Potion entity collection.
     */
    public function getPotionsDepart(): Collection
    {
        return $this->potions_depart;
    }

    public function __sleep()
    {
        return ['id', 'gn_id', 'subscription_date', 'User_id', 'personnage_secondaire_id', 'personnage_id', 'billet_id', 'billet_date', 'groupe_gn_id', 'valide_ci_le'];
    }
}
