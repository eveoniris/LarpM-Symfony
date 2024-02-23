<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\ObjetRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ObjetRepository::class)]
class Objet extends BaseObjet
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('NOW'));
    }

    public function getPhotosDocumentType(): DocumentType
    {
        return DocumentType::Stock;
    }

    public function getPhotosFolderType(): FolderType
    {
        return FolderType::Private;
    }

    public function getPhotoFilePath(string $projectDir): string
    {
        return $projectDir.$this->getPhotosFolderType()->value.$this->getPhotosDocumentType()->value.DIRECTORY_SEPARATOR;
    }

    /**
     * Manage relation when clone entity.
     */
    public function __clone()
    {
        $objetCarac = $this->getObjetCarac();
        if ($objetCarac) {
            $cloneObjetCarac = clone $objetCarac;
            $this->objetCarac = $cloneObjetCarac;
            $cloneObjetCarac->setObjet($this);
        }

        $photo = $this->getPhoto();
        if ($photo) {
            $this->photo = null; // on ne clone pas la photo par comodité
        }

        $this->setCreationDate(new \DateTime('NOW'));
    }

    /**
     * Fourni un tableau pour exporter l'objet dans un fichier CSV.
     */
    public function getExportValue(): array
    {
        return [
            'nom' => ('' !== $this->getNom() && '0' !== $this->getNom()) ? $this->getNom() : '',
            'code' => ($this->getcode()) ? $this->getCode() : '',
            'description' => ('' !== $this->getDescription() && '0' !== $this->getDescription()) ? $this->getDescription() : '',
            'photo' => ($this->getPhoto()) ? $this->getPhoto()?->getRealName() : '',
            'rangement' => ($this->getRangement()) ? $this->getRangement()->getAdresse() : '',
            'etat' => ($this->getEtat()) ? $this->getEtat()->getLabel() : '',
            'proprietaire' => ($this->getProprietaire()) ? $this->getProprietaire()->getNom() : '',
            'responsable' => ($this->getResponsable()) ? $this->getResponsable()->getUserName() : '',
            'nombre' => $this->getNombre(),
            'creation_date' => ($this->getCreationDate()) ? $this->getCreationDate()->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Fabrique le code d'un objet en fonction de son rangement et de son numéro.
     */
    public function getCode(): string
    {
        $code = '';
        if ($this->getRangement()) {
            $code .= substr($this->getRangement()->getLabel(), 0, 3);
        }

        return $code.('-'.$this->getNumero());
    }

    /**
     * Get User entity related by `responsable_id` (many to one).
     *
     * @return \App\Entity\User
     */
    public function getResponsable()
    {
        return $this->getUser();
    }

    /**
     * Set Users entity related by `responsable_id` (many to one).
     *
     * @param \App\Entity\Users $User
     *
     * @return \App\Entity\Objet
     */
    public function setResponsable(User $User = null)
    {
        return $this->setUser($User);
    }
}
