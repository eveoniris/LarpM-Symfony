<?php

namespace App\Service;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FileUploader
{
    private string $fileName;
    private string $storedFileName;
    private string $filePath;
    private string $projectDirectory;
    private string $extension;
    private SluggerInterface $slugger;

    public function __construct(string $projectDirectory, SluggerInterface $slugger)
    {
        $this->storedFileName = '';
        $this->filePath = '';
        $this->fileName = '';
        $this->extension = '';
        $this->projectDirectory = $projectDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file, DocumentType $docType, FolderType $folderType = FolderType::Photos): self
    {
        $this->extension = $file->guessExtension();
        $this->storedFileName = sprintf(
            '%s-%s.%s',
            substr($this->getOriginalFilename($file), 0, 70),
            uniqid('', true),
            $this->extension
        );

        try {
            $file->move($this->getDirectory($folderType, $docType), $this->storedFileName);
        } catch (FileException $e) {
            // TODO log or handle exception if something happens during file upload
        }

        return $this;
    }

    public function getOriginalFilename(UploadedFile $file, bool $unSafe = false): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        if ($unSafe) {
            return $originalFilename;
        }

        $this->fileName = $this->slugger->slug($originalFilename);

        return $this->fileName;
    }

    public function getDirectory(?FolderType $folderType, DocumentType $docType = null): string
    {
        return $this->getProjectDirectory().($folderType->value ?? '').($docType->value ?? '');
    }

    public function getProjectDirectory(): string
    {
        return $this->projectDirectory;
    }

    public function getSlugger(): SluggerInterface
    {
        return $this->slugger;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getStoredFileName(): string
    {
        return $this->storedFileName;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
