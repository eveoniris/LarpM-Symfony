<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\RuleRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RuleRepository::class)]
class Rule extends BaseRule implements \Stringable
{
    use EntityFileUploadTrait;

    public function __construct()
    {
        $this->initFile();
    }

    public function initFile(): static
    {
        $this->setDocumentType(DocumentType::Rule)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4);

        return $this;
    }

    /**
     * Affichage.
     */
    public function __toString(): string
    {
        return (string) $this->getLabel();
    }

    public function getPrintLabel(): string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->filename ?: time()));
    }

    protected function afterUpload(FileUploader $fileUploader): FileUploader
    {
        $this->setUrl($fileUploader->getStoredFileName());

        return $fileUploader;
    }

    public function getDocument(string $projectDir): string
    {
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    public function getDocumentUrl(): string
    {
        return $this->getUrl() ?? '';
    }

    public function getFilename(): ?string
    {
        return $this->getLabel() ?? (string) time();
    }
}
