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

    public function upload(
        UploadedFile $file,
        FolderType $folderType,
        DocumentType $docType,
        ?string $filename = null,
        ?int $filenameMaxLength = null,
        bool $useUniqueId = true
    ): self {
        $this->extension = $file->guessExtension();
        $filenameMaxLength ??= mb_strlen($this->getOriginalFilename($file));

        $this->storedFileName = sprintf(
            '%s%s.%s',
            $filename ?? mb_substr($this->getOriginalFilename($file), 0, $filenameMaxLength),
            $useUniqueId ? str_replace('.', '-', '-'.uniqid('', true)) : '',
            $this->extension
        );

        $this->filePath = $this->getDirectory($folderType, $docType);

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

    public function getDirectory(?FolderType $folderType, ?DocumentType $docType = null): string
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

    public function getStoredFileWithPath(): string
    {
        return $this->getFilePath().'/'.$this->getStoredFileName();
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
