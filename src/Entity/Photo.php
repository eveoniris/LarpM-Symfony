<?php

namespace App\Entity;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Photo extends BasePhoto
{
    /**
     * @Assert\File(maxSize="6000000")
     */
    public $file;

    /**
     * Upload file on database.
     */
    public function upload(array $app): void
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
     * Transfert une photo du format sql blob à un ficher (en redimenssionnant la photo).
     *
     * @param unknown $app
     */
    public function blobToFile(array $app): void
    {
        if (null === $this->getData()) {
            return;
        }

        $path = __DIR__.'/../../../private/stock/';
        $filename = $this->getName();
        $extension = $this->getExtension();

        $photoFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

        $image = $app['imagine']->read($this->getData());
        $image->resize($image->getSize()->widen(480));
        $image->save($path.$photoFilename);

        $this->setFilename($photoFilename);
    }
}
