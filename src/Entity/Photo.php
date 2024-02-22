<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Service\FileUploader;
use Doctrine\ORM\Mapping\Entity;
use JetBrains\PhpStorm\Deprecated;
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
        if (null === $this->file) {
            return;
        }

        $fileUploader->upload($this->file, $docType, $folderType);
        // TODO resize image?

        $this->setCreationDate(new \DateTime('NOW'));
        $this->setName($fileUploader->getFileName());
        $this->setRealName($fileUploader->getFileName());
        $this->setFilename($fileUploader->getStoredFileName());
        $this->setExtension($fileUploader->getExtension());

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;

    }

    #[Deprecated]
    public function upload(): void
    {
        // la propriété « file » peut être vide si le champ n'est pas requis
        if (null === $this->file) {
            return;
        }

        $path = __DIR__.'/../../../private/stock/';
        $filename = $this->file->getClientOriginalName();
        $extension = $this->file->guessExtension();

        // create a unique filename
        $photoFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

        $this->setExtension($this->file->guessExtension());

        $image = $app['imagine']->open($this->file->getPathname());
        $image->resize($image->getSize()->widen(480));
        $image->save($path.$photoFilename);

        $this->setCreationDate(new \DateTime('NOW'));
        $this->setName($this->file->getClientOriginalName());
        $this->setRealName($photoFilename);
        $this->setFilename($photoFilename);

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;
    }

    /**
     * Transfert une photo du format sql blob à un fichier (en redimenssionnant la photo).
     */
    public function blobToFile(): void
    {
        if (null === $this->getData()) {
            return;
        }

        // Check https://symfony.com/doc/current/the-fast-track/en/14-form.html#uploading-files
        $path = $this->params->get('privatedir').'/stock/';
        $filename = $this->getName();
        $extension = $this->getExtension();

        $photoFilename = hash('md5', bin2hex(random_bytes(6)).$filename).'.'.$extension;

        /* Todo
        $image = $app['imagine']->read($this->getData());
        $image->resize($image->getSize()->widen(480));
        $image->save($path.$photoFilename);
        */

        $this->setFilename($photoFilename);
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
