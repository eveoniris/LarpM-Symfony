<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\DepouillementPhoto;
use App\Entity\User;
use App\Exception\PhotoInvalideException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Photos du contenu d'une enveloppe.
 *
 * Les fichiers sont stockés hors du dossier public, sous {dossier}/{gn}/{dépouillement}/, avec un nom aléatoire
 * (jamais le nom d'origine). Ils ne sont servis que par le contrôleur, après contrôle des droits.
 */
readonly class DepouillementPhotoService
{
    public const int TAILLE_MAX = 10 * 1024 * 1024;

    /** Types acceptés (reconnus d'après le contenu du fichier, pas son nom) et extension de stockage. */
    public const array TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%depouillement.upload_dir%')]
        private string $dossier,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @throws PhotoInvalideException si la saisie n'est plus modifiable, ou si le fichier est incomplet, trop lourd ou d'un autre type
     */
    public function ajouter(Depouillement $depouillement, UploadedFile $fichier, User $utilisateur): DepouillementPhoto
    {
        if (!$depouillement->getStatut()->isEditable()) {
            throw new PhotoInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        $nom = $this->nomAffichable($fichier);
        if (!$fichier->isValid()) {
            throw new PhotoInvalideException(\sprintf('%s : envoi incomplet ou trop volumineux (%s).', $nom, $fichier->getErrorMessage()));
        }

        if ($fichier->getSize() > self::TAILLE_MAX) {
            throw new PhotoInvalideException(\sprintf('%s : la photo dépasse %d Mo.', $nom, (self::TAILLE_MAX / 1024) / 1024));
        }

        $mime = (string) $fichier->getMimeType();
        if (!array_key_exists($mime, self::TYPES)) {
            throw new PhotoInvalideException(\sprintf('%s : format non accepté (JPEG, PNG ou WebP uniquement).', $nom));
        }

        $dossierRelatif = $depouillement->getGn()->getId() . '/' . $depouillement->getId();
        $fichierStocke = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        $taille = (int) $fichier->getSize();
        $fichier->move($this->dossier . '/' . $dossierRelatif, $fichierStocke);

        $photo = (new DepouillementPhoto())
            ->setChemin($dossierRelatif . '/' . $fichierStocke)
            ->setNomOriginal($nom)
            ->setMimeType($mime)
            ->setTaille($taille)
            ->setTeleversePar($utilisateur);
        $depouillement->addPhoto($photo);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setMotif(\sprintf('Photo ajoutée : %s', $nom)),
        );
        $depouillement->touch();
        $this->entityManager->flush();

        return $photo;
    }

    public function supprimer(DepouillementPhoto $photo, User $utilisateur): void
    {
        $depouillement = $photo->getDepouillement();
        $chemin = $this->cheminComplet($photo);

        $depouillement->removePhoto($photo);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setMotif(\sprintf('Photo supprimée : %s', $photo->getNomOriginal())),
        );
        $depouillement->touch();
        $this->entityManager->remove($photo);
        $this->entityManager->flush();

        $this->filesystem->remove($chemin);
    }

    /**
     * Partage (ou retire le partage) avec le propriétaire de l'enveloppe. Retourne le nouvel état.
     */
    public function basculerPartage(DepouillementPhoto $photo, User $utilisateur): bool
    {
        $partage = !$photo->isPartage();
        $photo->setPartage($partage);
        $photo
            ->getDepouillement()
            ->addHistory(
                (new DepouillementHistory())
                    ->setUser($utilisateur)
                    ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                    ->setMotif(\sprintf('Photo %s avec le propriétaire de l\'enveloppe : %s', $partage ? 'partagée' : 'retirée du partage', $photo->getNomOriginal())),
            );
        $this->entityManager->flush();

        return $partage;
    }

    public function cheminComplet(DepouillementPhoto $photo): string
    {
        return $this->dossier . '/' . $photo->getChemin();
    }

    /**
     * Supprime tous les fichiers d'un dépouillement (avant sa suppression : les lignes partent en cascade en base).
     */
    public function supprimerFichiers(Depouillement $depouillement): void
    {
        $this->filesystem->remove($this->dossier . '/' . $depouillement->getGn()->getId() . '/' . $depouillement->getId());
    }

    private function nomAffichable(UploadedFile $fichier): string
    {
        $nom = preg_replace('/[\x00-\x1F\x7F]/u', replacement: '', subject: $fichier->getClientOriginalName()) ?? '';

        return mb_substr('' === $nom ? 'photo' : $nom, start: 0, length: 200);
    }
}
