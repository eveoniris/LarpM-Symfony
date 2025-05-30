<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\LoiRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Entity(repositoryClass: LoiRepository::class)]
class Loi extends BaseLoi
{
    use EntityFileUploadTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initFile();
    }

    public function initFile(): static
    {
        $this->setDocumentType(DocumentType::Doc)
            ->setFolderType(FolderType::Private);

        return $this;
    }

    public function getFullLabel(): string
    {
        return $this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return (new AsciiSlugger())->slug($this->getLabel());
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
