<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected ?DateTimeInterface $subscription_date = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected DateTime $billet_date;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $valide_ci_le = null;
    #[OneToMany(mappedBy: 'participant', targetEntity: GroupeGn::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'responsable_id', nullable: false)]
    protected Collection $groupeGns;
    #[OneToMany(mappedBy: 'participant', targetEntity: ParticipantHasRestauration::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'participant_id', nullable: false)]
    protected Collection $participantHasRestaurations;
    #[OneToMany(mappedBy: 'participant', targetEntity: Reponse::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'participant_id', nullable: false)]
    protected Collection $reponses;
    #[ManyToOne(targetEntity: Gn::class, cascade: ['persist'], inversedBy: 'participants')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false)]
    protected Gn $gn;
    #[ManyToOne(targetEntity: User::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;
    #[ManyToOne(targetEntity: PersonnageSecondaire::class, cascade: ['persist'], inversedBy: 'participants')]
    #[JoinColumn(name: 'personnage_secondaire_id', referencedColumnName: 'id', nullable: false)]
    protected ?PersonnageSecondaire $personnageSecondaire;
    #[ManyToOne(targetEntity: Personnage::class, cascade: ['persist'], inversedBy: 'participants')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected ?Personnage $personnage;
    #[ManyToOne(targetEntity: Billet::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'billet_id', referencedColumnName: 'id')]
    protected ?Billet $billet = null;
    #[ManyToOne(targetEntity: GroupeGn::class, inversedBy: 'participants')]
    #[JoinColumn(name: 'groupe_gn_id', referencedColumnName: 'id')]
    protected ?GroupeGn $groupeGn;
    #[ORM\ManyToMany(targetEntity: Potion::class, inversedBy: 'participants')]
    #[ORM\JoinTable(name: 'participant_potions_depart')]
    #[JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'potion_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OrderBy(['label' => 'ASC', 'niveau' => 'ASC'])]
    protected Collection $potions_depart;
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $couchage = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $special = null;
    #[ORM\OneToMany(mappedBy: 'participant', targetEntity: QrCodeScanLog::class)]
    private Collection $qrCodeScanLogs;

    public function __construct()
    {
        $this->groupeGns = new ArrayCollection();
        $this->participantHasRestaurations = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->potions_depart = new ArrayCollection();
        $this->qrCodeScanLogs = new ArrayCollection();
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
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
     * Get the value of subscription_date.
     */
    public function getSubscriptionDate(): DateTimeInterface
    {
        return $this->subscription_date;
    }

    /**
     * Set the value of subscription_date.
     *
     * @param DateTime $subscription_date
     */
    public function setSubscriptionDate(?DateTimeInterface $subscription_date): static
    {
        $this->subscription_date = $subscription_date;

        return $this;
    }

    /**
     * Get the value of billet_date.
     */
    public function getBilletDate(): DateTime
    {
        return $this->billet_date;
    }

    /**
     * Set the value of billet_date.
     */
    public function setBilletDate(DateTime $billet_date): static
    {
        $this->billet_date = $billet_date;

        return $this;
    }

    /**
     * Get the value of valide_ci_le.
     */
    public function getValideCiLe(): ?DateTimeInterface
    {
        return $this->valide_ci_le;
    }

    /**
     * Set the value of valide_ci_le.
     *
     * @param DateTime $valide_ci_le
     */
    public function setValideCiLe(?DateTimeInterface $valide_ci_le): static
    {
        $this->valide_ci_le = $valide_ci_le;

        return $this;
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
     * Get Gn entity (many to one).
     */
    public function getGn(): Gn
    {
        return $this->gn;
    }

    /**
     * Set Gn entity (many to one).
     */
    public function setGn(?Gn $gn = null): static
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(?User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get PersonnageSecondaire entity (many to one).
     */
    public function getPersonnageSecondaire(): ?PersonnageSecondaire
    {
        return $this->personnageSecondaire;
    }

    /**
     * Set PersonnageSecondaire entity (many to one).
     */
    public function setPersonnageSecondaire(?PersonnageSecondaire $personnageSecondaire = null): static
    {
        $this->personnageSecondaire = $personnageSecondaire;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Billet entity (many to one).
     */
    public function getBillet(): ?Billet
    {
        return $this->billet;
    }

    /**
     * Set Billet entity (many to one).
     */
    public function setBillet(?Billet $billet = null): static
    {
        $this->billet = $billet;

        return $this;
    }

    /**
     * Get GroupeGn entity (many to one).
     */
    public function getGroupeGn(): ?GroupeGn
    {
        return $this->groupeGn;
    }

    /**
     * Set GroupeGn entity (many to one).
     */
    public function setGroupeGn(?GroupeGn $groupeGn = null): static
    {
        $this->groupeGn = $groupeGn;

        return $this;
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


    public function getCouchage(): ?string
    {
        return $this->couchage;
    }

    public function setCouchage(?string $couchage): static
    {
        $this->couchage = $couchage;

        return $this;
    }

    public function getSpecial(): ?string
    {
        return $this->special;
    }

    public function setSpecial(?string $special): static
    {
        $this->special = $special;

        return $this;
    }

    /**
     * @return Collection<int, QrCodeScanLog>
     */
    public function getQrCodeScanLogs(): Collection
    {
        return $this->qrCodeScanLogs;
    }

    public function addQrCodeScanLog(QrCodeScanLog $qrCodeScanLog): static
    {
        if (!$this->qrCodeScanLogs->contains($qrCodeScanLog)) {
            $this->qrCodeScanLogs->add($qrCodeScanLog);
            $qrCodeScanLog->setParticipant($this);
        }

        return $this;
    }

    public function removeQrCodeScanLog(QrCodeScanLog $qrCodeScanLog): static
    {
        if ($this->qrCodeScanLogs->removeElement($qrCodeScanLog)) {
            // set the owning side to null (unless already changed)
            if ($qrCodeScanLog->getParticipant() === $this) {
                $qrCodeScanLog->setParticipant(null);
            }
        }

        return $this;
    }
}
