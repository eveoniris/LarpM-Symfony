<?php

namespace App\Trait;

use App\Entity\Document;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Service\FileUploader;
use JetBrains\PhpStorm\NoReturn;
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
    protected ?string $filename = null;
    protected DocumentType $documentType;
    protected FolderType $folderType;

    protected ?string $projectDir = null;

    public function getDocument(?string $projectDir = null): string
    {
        $projectDir ??= $this->projectDir ?? '';

        $legacies = [
            ['old' => false, 'doc' => false],
            ['old' => false, 'doc' => true],
            ['old' => true, 'doc' => true],
            ['old' => true, 'doc' => false],
        ];
        foreach ($legacies as $legacy) {
            $filename = $this->getDocumentFilePath($projectDir, $legacy['old'], $legacy['doc']).$this->getDocumentUrl();
            if (file_exists($filename)) {
                return $filename;
            }
        }

        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    public function getDocumentFilePath(
        ?string $projectDir = null,
        bool $oldV1Prod = false,
        bool $inDocInsteadOfDocuments = false,
    ): string {
        $projectDir ??= $this->projectDir ?? '';

        if ($oldV1Prod) {
            $projectDir .= str_contains(__FILE__, 'larpmanager') ? '../larpm/' : '../larpmanager/';
        }

        $type = $this->getDocumentType()->value;
        if ($inDocInsteadOfDocuments) {
            $type = DocumentType::Doc->value;
        }

        return $projectDir.$this->getFolderType()->value.$type.DIRECTORY_SEPARATOR;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->documentType ?? $this->initFile()?->documentType ?? DocumentType::Doc;
    }

    public function setDocumentType(DocumentType $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function initFile(): self
    {
        return $this;
    }

    public function getFolderType(): FolderType
    {
        return $this->folderType ?? $this->initFile()->folderType ?? FolderType::Private;
    }

    public function setFolderType(FolderType $folderType): static
    {
        $this->folderType = $folderType;

        return $this;
    }

    public function getHasDocument(): Document
    {
        $document = new Document();

        $document->setDocumentType($this->getDocumentType());
        $document->setFolderType($this->getFolderType());
        $document->setProjectDir($this->getProjectDir());
        $document->setFilename($this->getFilename());
        $document->setLabel($this->getLabel());
        $document->setFilenameMaxLength($this->getFilenameMaxLength());
        $document->setDocumentUrl($this->getDocumentUrl());
        $document->setDocumentExtension($this->getDocumentExtension());
        $document->setDocumentMimeType($this->getDocumentMimeType());

        return $document;
    }

    public function getProjectDir(): ?string
    {
        return $this->projectDir;
    }

    public function setProjectDir(?string $projectDir): static
    {
        $this->projectDir = $projectDir;

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

    public function setDocumentExtension(?string $ext): self
    {
        $this->extension = $ext;

        return $this;
    }

    public function getDocumentExtension(): string
    {
        return $this->extension ?? '.pdf';
    }

    public function setDocumentMimeType(?string $mimetype): self
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    public function getDocumentMimeType(): string
    {
        return $this->mimetype ?? 'application/pdf';
    }

    #[NoReturn]
    public function getOldV1Document(?string $projectDir = null): string
    {
        $filename = $this->getDocumentFilePath($projectDir, true).$this->getDocumentUrl();
        if (DocumentType::Document->value === $this->getDocumentType()->value && !file_exists($filename)) {
            $filename = $this->getDocumentFilePath($projectDir, true, true).$this->getDocumentUrl();
        }
        if (DocumentType::Doc->value === $this->getDocumentType()->value && !file_exists($filename)) {
            $filename = $this->getDocumentFilePath($projectDir, true, false).$this->getDocumentUrl();
        }

        return $filename;
    }

    public function handleUpload(FileUploader $fileUploader): static
    {
        // la propriété « file » peut être vide si le champ n'est pas requis (cas Modification, on garde le doc)
        if (!isset($this->file)) {
            return $this;
        }

        // Ensure path folder type and document type.
        $this->initFile();

        $fileUploader->upload(
            $this->getFile(),
            $this->getFolderType(),
            $this->getDocumentType(),
            $this->getFilename(),
            $this->getFilenameMaxLength(),
            $this->isUseUniqueId(),
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
