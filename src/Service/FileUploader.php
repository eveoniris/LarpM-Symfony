<?php

namespace App\Service;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
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

    public function __construct(string $projectDirectory, SluggerInterface $slugger, protected LoggerInterface $logger)
    {
        $this->storedFileName = '';
        $this->filePath = '';
        $this->fileName = '';
        $this->extension = '';
        $this->projectDirectory = $projectDirectory;
        $this->slugger = $slugger;
        $this->logger = $this->logger;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getSlugger(): SluggerInterface
    {
        return $this->slugger;
    }

    public function getStoredFileWithPath(): string
    {
        return $this->getFilePath() . '/' . $this->getStoredFileName();
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getStoredFileName(): string
    {
        return $this->storedFileName;
    }

    /**
     * @param int|null $filenameMaxLength without 28 chars reserved for uid and extension
     *
     * @return $this
     */
    public function upload(
        UploadedFile $file,
        FolderType   $folderType,
        DocumentType $docType,
        ?string      $filename = null,
        ?int         $filenameMaxLength = null,
        bool         $useUniqueId = true,
    ): self
    {
        $this->extension = $file->guessExtension();

        $filenameMaxLength ??= mb_strlen($this->getOriginalFilename($file));

        $uid = $useUniqueId ? str_replace('.', '-', '-' . uniqid('', true)) : '';
        $filename ??= mb_substr($this->getOriginalFilename($file) . $uid, 0, $filenameMaxLength);

        $this->storedFileName = sprintf(
            '%s.%s',
            $filename,
            $this->extension,
        );

        $this->filePath = $this->getDirectory($folderType, $docType);

        try {
            $file->move($this->filePath, $this->storedFileName);

            // Keep for V1
            if (str_contains(__FILE__, 'larpmanager')) {
                $mainProdFile = $this->getProjectDirectory() . '../larpm/' . $folderType->value . $docType->value . '/' . $this->storedFileName;
            } else {
                $mainProdFile = $this->getProjectDirectory() . '../larpmanager/' . $folderType->value . $docType->value . '/' . $this->storedFileName;
            }

            if (!file_exists($mainProdFile)) {
                $filesystem = new Filesystem();
                $filesystem->copy($this->filePath . '/' . $this->storedFileName, $mainProdFile);
            }
        } catch (FileException $e) {
            $this->logger->error($e);
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
        return $this->getProjectDirectory() . ($folderType->value ?? '') . ($docType->value ?? '');
    }

    public function getProjectDirectory(): string
    {
        return $this->projectDirectory;
    }
}
