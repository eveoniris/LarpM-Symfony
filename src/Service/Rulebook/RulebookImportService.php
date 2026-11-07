<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Entity\RulebookRevision;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Import initial des règles lues dans la spec. Ne remplace jamais une règle déjà en base (même domaine et même titre) :
 * après l'import, le rulebook en base fait foi et s'édite en ligne.
 */
final class RulebookImportService
{
    /** Préfixes lisibles des codes de règle, par code de domaine. Les autres domaines utilisent leurs 4 premières lettres. */
    private const array PREFIXES = [
        'territoire' => 'TERR',
        'groupe' => 'GRP',
        'xp' => 'XP',
        'competence' => 'COMP',
        'religion' => 'REL',
        'anomalie' => 'ANO',
        'langues' => 'LNG',
        'conditionsservice' => 'COND',
        'leveltype' => 'LVL',
        'personnage' => 'PERS',
        'gn' => 'GN',
        'initiative' => 'INIT',
        'origine' => 'ORI',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkdownNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<RegleImportee> $regles
     *
     * @return list<ImportLigne> une ligne par règle lue, dans l'ordre du fichier
     */
    public function importer(array $regles, bool $appliquer): array
    {
        /** @var array<string, RulebookDomaine> $domaines */
        $domaines = [];
        /** @var array<string, int> $compteurs prochain numéro par code de domaine */
        $compteurs = [];
        /** @var array<string, int> $positions */
        $positions = [];
        $lignes = [];

        foreach ($regles as $importee) {
            $domaine = $domaines[$importee->domaineCode] ?? $this->trouverOuCreerDomaine($importee, \count($domaines), $appliquer);
            $domaines[$importee->domaineCode] = $domaine;

            $existante = null === $domaine->getId() ? null : $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['domaine' => $domaine, 'titre' => $importee->titre]);
            if (null !== $existante) {
                $lignes[] = new ImportLigne($importee, $existante->getCode(), false);

                continue;
            }

            $compteurs[$importee->domaineCode] ??= $this->numeroSuivant($domaine);
            $code = $this->codeUnique($importee->domaineCode, $compteurs[$importee->domaineCode]);
            $positions[$importee->domaineCode] = ($positions[$importee->domaineCode] ?? 0) + 10;

            if ($appliquer) {
                $this->creerRegle($domaine, $importee, $code, $positions[$importee->domaineCode]);
            }

            $lignes[] = new ImportLigne($importee, $code, true);
        }

        if ($appliquer) {
            $this->entityManager->flush();
        }

        return $lignes;
    }

    private function trouverOuCreerDomaine(RegleImportee $importee, int $position, bool $appliquer): RulebookDomaine
    {
        $domaine = $this->entityManager->getRepository(RulebookDomaine::class)->findOneBy(['code' => $importee->domaineCode]);
        if (null !== $domaine) {
            return $domaine;
        }

        $domaine = (new RulebookDomaine())
            ->setCode($importee->domaineCode)
            ->setTitre($importee->domaineTitre)
            ->setPosition(($position + 1) * 10);
        if ($appliquer) {
            $this->entityManager->persist($domaine);
        }

        return $domaine;
    }

    private function creerRegle(RulebookDomaine $domaine, RegleImportee $importee, string $code, int $position): void
    {
        $regle = (new RulebookRegle())
            ->setDomaine($domaine)
            ->setCode($code)
            ->setTitre($importee->titre)
            ->setPosition($position)
            ->setDetailTechnique($this->normalizer->normaliser($importee->detail)->texte)
            ->setStatut($importee->statut)
            ->setTestsRefs($importee->testsRefs)
            ->setPubliee(false);

        $this->entityManager->persist($regle);
        $this->entityManager->persist(RulebookRevision::depuis($regle, null, 'Import initial depuis la spec de gameplay'));
    }

    /** Numéro suivant dans le domaine : un de plus que le plus grand déjà utilisé par un code existant. */
    private function numeroSuivant(RulebookDomaine $domaine): int
    {
        if (null === $domaine->getId()) {
            return 1;
        }

        $maximum = 0;
        foreach ($domaine->getRegles() as $regle) {
            if (preg_match('/-(\d+)$/', $regle->getCode(), $trouve)) {
                $maximum = max($maximum, (int) $trouve[1]);
            }
        }

        return $maximum + 1;
    }

    /** Code stable "PREFIXE-NN", le préfixe étant tiré du code du domaine. Garantit l'unicité globale. */
    private function codeUnique(string $domaineCode, int &$numero): string
    {
        $prefixe = self::PREFIXES[$domaineCode] ?? strtoupper(substr((string) preg_replace('/[^a-z]/', '', $domaineCode), 0, 4));
        $prefixe = '' === $prefixe ? 'REGLE' : $prefixe;

        do {
            $code = \sprintf('%s-%02d', $prefixe, $numero++);
        } while (null !== $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => $code]));

        return $code;
    }
}
