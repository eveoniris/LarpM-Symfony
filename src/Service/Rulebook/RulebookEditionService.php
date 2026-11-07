<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use App\Entity\LogAction;
use App\Entity\RulebookRegle;
use App\Entity\RulebookRevision;
use App\Entity\User;
use App\Enum\LogActionType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistrement d'une règle du rulebook : nettoyage du markdown, nouvelle version, révision (historique) et trace dans
 * le journal d'actions. Point d'entrée unique de l'édition en ligne.
 */
final readonly class RulebookEditionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MarkdownNormalizer $normalizer,
        private RulebookExporter $exporter,
    ) {
    }

    /**
     * Enregistre la règle (nouvelle ou existante). Ne crée une version que si le contenu a changé.
     *
     * @param string|null $empreinteAvant résultat de {@see self::empreinte()} pris avant modification (null pour une création)
     *
     * @return array{version: int, nettoyes: int, modifiee: bool} nettoyes = caractères indésirables retirés des textes
     */
    public function enregistrer(RulebookRegle $regle, ?User $auteur, ?string $motif, ?string $empreinteAvant): array
    {
        $nettoyes = 0;
        $regle->setTexteClair($this->nettoyer($regle->getTexteClair(), $nettoyes));
        $regle->setDetailTechnique($this->nettoyer($regle->getDetailTechnique(), $nettoyes));
        $regle->setTitre(trim($this->normalizer->normaliser($regle->getTitre())->texte));

        $creation = null === $regle->getId();
        if (!$creation && $empreinteAvant === $this->empreinte($regle)) {
            return ['version' => $regle->getVersion(), 'nettoyes' => $nettoyes, 'modifiee' => false];
        }

        $motif = null === $motif || '' === trim($motif) ? null : mb_substr(trim($motif), 0, 255);

        if (!$creation) {
            $regle->setVersion($regle->getVersion() + 1);
        }

        $regle->setUpdatedAt(new DateTime());
        $regle->setUpdatedBy($auteur);
        $revision = RulebookRevision::depuis($regle, $auteur, $creation ? 'Création' : $motif);
        $regle->getRevisions()->add($revision);

        $this->entityManager->persist($regle);
        $this->entityManager->persist($revision);
        $this->journaliser($regle, $auteur, $creation ? 'creation' : 'modification', $motif);
        $this->entityManager->flush();
        $this->rafraichirCache();

        return ['version' => $regle->getVersion(), 'nettoyes' => $nettoyes, 'modifiee' => true];
    }

    /** Recopie le contenu d'une ancienne révision dans une nouvelle version (l'historique n'est jamais réécrit). */
    public function restaurer(RulebookRegle $regle, RulebookRevision $source, ?User $auteur): void
    {
        $regle
            ->setTitre($source->getTitre())
            ->setTexteClair($source->getTexteClair())
            ->setDetailTechnique($source->getDetailTechnique())
            ->setStatut($source->getStatut())
            ->setPubliee($source->isPubliee())
            ->setVersion($regle->getVersion() + 1)
            ->setUpdatedAt(new DateTime())
            ->setUpdatedBy($auteur);

        $motif = 'Restauration de la version ' . $source->getVersion();
        $revision = RulebookRevision::depuis($regle, $auteur, $motif);
        $regle->getRevisions()->add($revision);

        $this->entityManager->persist($revision);
        $this->journaliser($regle, $auteur, 'restauration', $motif);
        $this->entityManager->flush();
        $this->rafraichirCache();
    }

    /** Empreinte du contenu éditable, pour détecter l'absence de changement. */
    public function empreinte(RulebookRegle $regle): string
    {
        return hash('sha256', json_encode([
            $regle->getTitre(),
            $regle->getTexteClair(),
            $regle->getDetailTechnique(),
            $regle->getStatut()->value,
            $regle->isPubliee(),
            $regle->getPosition(),
            $regle->getDomaine()->getId(),
            $regle->getTestsRefs(),
        ], JSON_THROW_ON_ERROR));
    }

    /** Le cache local est un confort : une erreur d'écriture ne doit jamais empêcher l'enregistrement. */
    private function rafraichirCache(): void
    {
        try {
            $this->exporter->exporter();
        } catch (Throwable) {
            // ignoré volontairement
        }
    }

    private function nettoyer(?string $texte, int &$nettoyes): ?string
    {
        if (null === $texte) {
            return null;
        }

        $resultat = $this->normalizer->normaliser($texte);
        $nettoyes += $resultat->nettoyes;

        return '' === $resultat->texte ? null : $resultat->texte;
    }

    private function journaliser(RulebookRegle $regle, ?User $auteur, string $action, ?string $motif): void
    {
        $this->entityManager->persist((new LogAction())
            ->setType(LogActionType::RULEBOOK)
            ->setUser($auteur)
            ->setDate(new DateTime())
            ->setData([
                'action' => $action,
                'regle' => $regle->getCode(),
                'version' => $regle->getVersion(),
                'motif' => $motif,
            ]));
    }
}
