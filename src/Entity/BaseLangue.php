<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Langue.
 *
 * @Table(name="langue", indexes={@Index(name="groupe_langue_id_idx", columns={"groupe_langue_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseLangue", "extended":"Langue"})
 */
class BaseLangue
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=100)
     */
    protected $label;

    /**
     * @Column(type="string", length=450, nullable=true)
     */
    protected $description;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $diffusion;

    /**
     * @OneToMany(targetEntity="PersonnageLangues", mappedBy="langue")
     *
     * @JoinColumn(name="id", referencedColumnName="langue_id", nullable=false)
     */
    protected $personnageLangues;

    /**
     * @OneToMany(targetEntity="Territoire", mappedBy="langue")
     *
     * @JoinColumn(name="id", referencedColumnName="langue_id", nullable=false)
     */
    protected $territoires;

    /**
     * @ManyToOne(targetEntity="GroupeLangue", inversedBy="langues")
     *
     * @JoinColumn(name="groupe_langue_id", referencedColumnName="id", nullable=false)
     */
    protected $groupeLangue;

    /**
     * @ManyToMany(targetEntity="Document", mappedBy="langues")
     */
    protected $documents;

    /**
     * @Column(type="boolean", nullable=false, options={"default":0})
     */
    protected $secret;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $documentUrl;

    public function __construct()
    {
        $this->personnageLangues = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Langue
     */
    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Langue
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\Langue
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of diffusion.
     *
     * @param int $diffusion
     *
     * @return \App\Entity\Langue
     */
    public function setDiffusion($diffusion)
    {
        $this->diffusion = $diffusion;

        return $this;
    }

    /**
     * Get the value of diffusion.
     *
     * @return int
     */
    public function getDiffusion()
    {
        return $this->diffusion;
    }

    /**
     * Add PersonnageLangues entity to collection (one to many).
     *
     * @return \App\Entity\Langue
     */
    public function addPersonnageLangues(PersonnageLangues $personnageLangues)
    {
        $this->personnageLangues[] = $personnageLangues;

        return $this;
    }

    /**
     * Remove PersonnageLangues entity from collection (one to many).
     *
     * @return \App\Entity\Langue
     */
    public function removePersonnageLangues(PersonnageLangues $personnageLangues)
    {
        $this->personnageLangues->removeElement($personnageLangues);

        return $this;
    }

    /**
     * Get PersonnageLangues entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     *
     * @OrderBy({"secret" = "ASC", "diffusion" = "DESC", "label" = "ASC"})
     */
    public function getPersonnageLangues()
    {
        return $this->personnageLangues;
    }

    /**
     * Add Territoire entity to collection (one to many).
     *
     * @return \App\Entity\Langue
     */
    public function addTerritoire(Territoire $territoire)
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     *
     * @return \App\Entity\Langue
     */
    public function removeTerritoire(Territoire $territoire)
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoires()
    {
        return $this->territoires;
    }

    /**
     * Set GroupeLangue entity (many to one).
     *
     * @return \App\Entity\Langue
     */
    public function setGroupeLangue(GroupeLangue $groupeLangue = null)
    {
        $this->groupeLangue = $groupeLangue;

        return $this;
    }

    /**
     * Get GroupeLangue entity (many to one).
     *
     * @return \App\Entity\GroupeLangue
     */
    public function getGroupeLangue()
    {
        return $this->groupeLangue;
    }

    /**
     * Add Document entity to collection.
     *
     * @return \App\Entity\Langue
     */
    public function addDocument(Document $document)
    {
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove Document entity from collection.
     *
     * @return \App\Entity\Langue
     */
    public function removeDocument(Document $document)
    {
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Get Document entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Set the value of secret.
     *
     * @param bool $secret
     *
     * @return \App\Entity\Langue
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     *
     * @return bool
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set the value of documentUrl.
     *
     * @param string $documentUrl
     *
     * @return \App\Entity\Langue
     */
    public function setDocumentUrl($documentUrl)
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get the value of documentUrl.
     *
     * @return string
     */
    public function getDocumentUrl()
    {
        return $this->documentUrl;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'diffusion', 'groupe_langue_id', 'secret'];
    }
}
