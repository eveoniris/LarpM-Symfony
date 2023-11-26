<?php

namespace App\Entity;

use App\Repository\RessourceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RessourceRepository::class)]
class Ressource extends BaseRessource implements \Stringable
{
    /**
     * @ManyToMany(targetEntity="Territoire", mappedBy="exportations")
     */
    protected $exportateurs;

    /**
     * @ManyToMany(targetEntity="Territoire", mappedBy="importations")
     */
    protected $importateurs;

    public function __construct()
    {
        $this->exportateurs = new ArrayCollection();
        $this->importateurs = new ArrayCollection();

        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getExportateurs()
    {
        return $this->exportateurs;
    }

    public function getImportateurs()
    {
        return $this->importateurs;
    }

    public function addImportateur($territoire): static
    {
        $this->importateurs[] = $territoire;

        return $this;
    }

    public function removeImportateur($territoire): static
    {
        $this->importateurs->removeElement($territoire);

        return $this;
    }

    public function addExportateur($territoire): static
    {
        $this->exportateurs[] = $territoire;

        return $this;
    }

    public function removeExportateur($territoire): static
    {
        $this->exportateurs->removeElement($territoire);

        return $this;
    }
}
