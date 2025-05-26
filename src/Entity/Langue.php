<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\LangueRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: LangueRepository::class)]
class Langue extends BaseLangue implements \Stringable
{
    use EntityFileUploadTrait;

    public const DIFFUSION_COURANTE = 1;
    public const DIFFUSION_COMMUNE = 2;
    public const DIFFUSION_RARE = 0;
    public const DIFFUSION_INCONNUE = null;

    public const SECRET_VISIBLE = 0;
    public const SECRET_HIDDEN = 1;

    public function __construct()
    {
        $this->initFile();
        parent::__construct();
    }

    public function initFile(): static
    {
        $this->setDocumentType(DocumentType::Doc)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4);

        return $this;
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    /**
     * Renvoie le libellé de diffusion, incluant la catégorie.
     */
    public function getDiffusionLabel(): string
    {
        return (null !== $this->getDiffusion() ? $this->getDiffusion().' - ' : '').$this->getCategorie();
    }

    /**
     * Fourni la catégorie de la langue.
     */
    public function getCategorie(): string
    {
        return match ($this->getDiffusion()) {
            self::DIFFUSION_COMMUNE => 'Commune',
            self::DIFFUSION_COURANTE => 'Courante',
            self::DIFFUSION_RARE => 'Rare',
            default => 'Inconnue',
        };
    }

    public function getFullDescription(): string
    {
        return $this->getLabel().' : '.$this->getDescription();
    }

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel()));
    }

    /**
     * Fourni la liste des territoires ou la langue est la langue principale.
     */
    public function getTerritoirePrincipaux()
    {
        return $this->getTerritoires();
    }

    protected function afterUpload(FileUploader $fileUploader): FileUploader
    {
        $this->setDocumentUrl($fileUploader->getStoredFileName());

        return $fileUploader;
    }

    public function getDocument(string $projectDir): string
    {
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }
}
