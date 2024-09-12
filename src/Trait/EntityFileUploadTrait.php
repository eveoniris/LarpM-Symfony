<?php

namespace App\Trait;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

// may be better as Service to handle multiple file field in an entity and get some Project DI
trait EntityFileUploadTrait
{
    #[Assert\File(
        [
            'maxSize' => 6000000,
            'extensions' => [
                'pdf' => ['application/pdf', 'application/x-pdf'],
            ],
            'mimeTypes' => ['application/pdf', 'application/x-pdf'],
            'groups' => ['pdf', 'file'],
        ]
    )]
    protected ?UploadedFile $file;
    protected ?int $filenameMaxLength;
    protected bool $useUniqueId = true;
    protected ?string $filename;
    protected DocumentType $documentType;
    protected FolderType $folderType;

    public function getDocument(string $projectDir = ''): string
    {
        // TODO find a way to DI the projectDir
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    public function getDocumentFilePath(string $projectDir = ''): string
    {
        // TODO find a way to DI the projectDir
        return $projectDir.$this->getFolderType()->value.$this->getDocumentType()->value.DIRECTORY_SEPARATOR;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->documentType ?? DocumentType::Documents;
    }

    public function setDocumentType(DocumentType $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function handleUpload(FileUploader $fileUploader): static
    {
        // la propriété « file » peut être vide si le champ n'est pas requis (cas Modification, on garde le doc)
        dump($this->file);
        if (!isset($this->file)) {
            return $this;
        }

        $fileUploader->upload(
            $this->getFile(),
            $this->getFolderType(),
            $this->getDocumentType(),
            $this->getFilename(),
            $this->getFilenameMaxLength(),
            $this->isUseUniqueId()
        );

        $this->afterUpload($fileUploader);

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;

        return $this;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file ?? null;
    }

    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFolderType(): FolderType
    {
        return $this->folderType ?? FolderType::Private;
    }

    public function setFolderType(FolderType $folderType): static
    {
        $this->folderType = $folderType;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename ?? null;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilenameMaxLength(): ?int
    {
        return $this->filenameMaxLength ?? null;
    }

    public function setFilenameMaxLength(?int $filenameMaxLength): static
    {
        $this->filenameMaxLength = $filenameMaxLength;

        return $this;
    }

    public function isUseUniqueId(): bool
    {
        return $this->useUniqueId;
    }

    public function setUseUniqueId(bool $useUniqueId): static
    {
        $this->useUniqueId = $useUniqueId;

        return $this;
    }

    protected function afterUpload(FileUploader $fileUploader): FileUploader
    {
        return $fileUploader;
    }
}
