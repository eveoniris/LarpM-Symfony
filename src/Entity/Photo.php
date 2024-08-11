<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Service\FileUploader;
use Doctrine\ORM\Mapping\Entity;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
class Photo extends BasePhoto
{
    #[Assert\File(['maxSize' => 6000000])]
    protected ?UploadedFile $file;

    public function handleUpload(
        FileUploader $fileUploader,
        DocumentType $docType = DocumentType::Photos,
        FolderType $folderType = FolderType::Photos
    ): void {
        // la propriété « file » peut être vide si le champ n'est pas requis
        if (!isset($this->file) || null === $this->file) {
            return;
        }

        $fileUploader->upload($this->file, $folderType, $docType, null, 70);

        // Try Rezise
        try {
            $image = (new Imagine())->open($fileUploader->getStoredFileWithPath());
            $image->resize($image->getSize()->widen(480));
            $image->save($fileUploader->getStoredFileWithPath());
        } catch (\RuntimeException $e) {
            dump($e);
        }

        $this->setCreationDate(new \DateTime('NOW'));
        $this->setName($fileUploader->getFileName());
        $this->setRealName($fileUploader->getFileName());
        $this->setFilename($fileUploader->getStoredFileName());
        $this->setExtension($fileUploader->getExtension());

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;
    }

    /**
     * Transfert une photo du format sql blob à un fichier (en redimenssionnant la photo).
     */
    public function blobToFile(string $path): void
    {
        if (null === $this->getData()) {
            return;
        }

        try {
            $image = (new Imagine())->read($this->getData());
            $image->resize($image->getSize()->widen(480));
            $image->save($path.$this->getFilename());
        } catch (\RuntimeException $e) {
            dump($e);
        }
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
