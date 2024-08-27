<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\ConnaissanceRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: ConnaissanceRepository::class)]
class Connaissance extends BaseConnaissance
{
    use EntityFileUploadTrait;

    public function __construct()
    {
        parent::__construct();

        // Default level is 1
        $this->setNiveau(1);
    }

    public function getFullLabel(): string
    {
        return $this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return (new AsciiSlugger())->slug($this->getLabel());
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
