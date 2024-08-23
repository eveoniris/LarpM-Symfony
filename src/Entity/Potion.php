<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\PotionRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: PotionRepository::class)]
class Potion extends BasePotion
{
    use EntityFileUploadTrait;

    public function __construct()
    {
        parent::__construct();
        $this->setDocumentType(DocumentType::Documents)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4);
    }

    public function getFullLabel(): string
    {
        return $this->getNumero().' - '.$this->getLabel().' - Niveau '.$this->getNiveau();
    }

    public function getPrintLabel(): string|array|null
    {
        return preg_replace(
            '/[^a-z0-9]+/',
            '_',
            strtolower($this->getNumero().'_'.$this->getLabel().'_'.$this->getNiveau())
        );
    }

    public function getDocument(string $projectDir): string
    {
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    protected function afterUpload(FileUploader $fileUploader): FileUploader
    {
        $this->setDocumentUrl($fileUploader->getStoredFileName());

        return $fileUploader;
    }
}
