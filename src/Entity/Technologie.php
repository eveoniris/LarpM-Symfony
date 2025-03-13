<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\TechnologieRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TechnologieRepository::class)]
class Technologie extends BaseTechnologie implements \Stringable
{
    use EntityFileUploadTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initFile();
        $this->discr = 'extended'; // TODO migrate
    }

    public function initFile(): static
    {
        return $this->setDocumentType(DocumentType::Documents)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4);
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

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel()));
    }

    public function __toString(): string
    {
        return $this->getLabel() ?? '';
    }
}
