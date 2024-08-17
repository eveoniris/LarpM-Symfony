<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\ConnaissanceRepository;
use App\Service\FileUploader;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: ConnaissanceRepository::class)]
class Connaissance extends BaseConnaissance
{
    #[Assert\File(
        [
            'maxSize' => 6000000,
            'extensions' => ['pdf' => ['application/pdf', 'application/x-pdf']],
            'mimeTypes' => ['application/pdf', 'application/x-pdf'],
        ]
    )]
    protected ?UploadedFile $file;

    public function __construct()
    {
        parent::__construct();

        // Default level is 1
        $this->setNiveau(1);
    }

    public function getDocumentType(): DocumentType
    {
        return DocumentType::Documents;
    }

    public function getDocumentFolderType(): FolderType
    {
        return FolderType::Private;
    }

    public function getDocumentFilePath(string $projectDir): string
    {
        return $projectDir.$this->getDocumentFolderType()->value.$this->getDocumentType()->value.DIRECTORY_SEPARATOR;
    }

    public function getDocument(string $projectDir): string
    {
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    public function handleUpload(
        FileUploader $fileUploader,
        DocumentType $docType = DocumentType::Photos,
        FolderType $folderType = FolderType::Photos
    ): void {
        // la propriété « file » peut être vide si le champ n'est pas requis
        if (!isset($this->file)) {
            return;
        }

        // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
        $fileUploader->upload($this->file, $folderType, $docType, null, 45 - 23 - 4, true);

        $this->setDocumentUrl($fileUploader->getStoredFileName());

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;
    }

    public function getFullLabel(): string
    {
        return $this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return (new AsciiSlugger())->slug($this->getLabel());
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;

        return $this;
    }
}
