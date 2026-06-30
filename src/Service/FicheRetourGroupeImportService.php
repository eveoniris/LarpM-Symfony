<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FicheRetourGroupe;
use App\Entity\FicheRetourGroupeHistory;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FicheRetourGroupeImportService
{
    private const UPLOAD_DIR = 'var/uploads/fiches-retour';

    /** Mapping header CSV normalisé → setter de l'entité */
    private const COLUMN_MAP = [
        'submitted at' => 'setSubmittedAt',
        'groupe concerne' => null, // traité séparément
        'nb de pieces d argent' => 'setPiecesArgent',
        'nb de pieces d or' => 'setPiecesOr',
        'nb total d ingredients' => 'setNbIngredients',
        'nb total de potions' => 'setNbPotions',
        'armement' => 'setArmement',
        'chevaux' => 'setChevaux',
        'fruits leg' => 'setFruitsLegumes',
        'm simples' => 'setMatieresSimples',
        'sel' => 'setSel',
        'betail' => 'setBetail',
        'coton' => 'setCoton',
        'gemmes' => 'setGemmes',
        'moutons' => 'setMoutons',
        'soie' => 'setSoie',
        'bois' => 'setBois',
        'esclaves' => 'setEsclaves',
        'ivoire' => 'setIvoire',
        'pierre' => 'setPierre',
        'teinture' => 'setTeinture',
        'cereales' => 'setCereales',
        'fourrures' => 'setFourrures',
        'm precieux' => 'setMatieresPrecieux',
        'poisson' => 'setPoisson',
        'vin' => 'setVin',
        'commentaire concernant' => 'setCommentaire',
        'commentaire' => 'setCommentaire',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Importe un fichier CSV ou Excel de fiches retour pour un GN donné.
     *
     * @return array{imported: int, skipped: int, warnings: string[]}
     */
    public function import(UploadedFile $file, Gn $gn, User $user): array
    {
        $rows = $this->parseFile($file);
        $archivedPath = $this->archiveFile($file, $gn);

        $headers = null;
        $imported = 0;
        $skipped = 0;
        $warnings = [];
        $ficheCache = [];

        foreach ($rows as $i => $row) {
            if (null === $headers) {
                $headers = $this->normalizeHeaders($row);
                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = array_combine($headers, array_pad($row, count($headers), null));

            $groupeNumero = $this->extractGroupeNumero($data, $gn->getLabel() ?? '');
            if (null === $groupeNumero) {
                $warnings[] = sprintf('Ligne %d : impossible d\'extraire le numéro de groupe.', $i + 1);
                ++$skipped;
                continue;
            }

            $groupeGn = $this->findGroupeGn($gn, $groupeNumero);
            if (null === $groupeGn) {
                $warnings[] = sprintf('Ligne %d : groupe numéro %d non trouvé pour ce GN (ignoré).', $i + 1, $groupeNumero);
                ++$skipped;
                continue;
            }

            $this->upsertFiche($groupeGn, $data, $headers, $user, $archivedPath, $file->getClientOriginalName(), $ficheCache);
            ++$imported;
        }

        $this->entityManager->flush();

        return ['imported' => $imported, 'skipped' => $skipped, 'warnings' => $warnings];
    }

    private function archiveFile(UploadedFile $file, Gn $gn): string
    {
        $dir = sprintf('%s/%s/%d', $this->projectDir, self::UPLOAD_DIR, $gn->getId());
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $filename = sprintf('%s_%s', date('YmdHis'), $file->getClientOriginalName());
        $file->move($dir, $filename);

        return sprintf('%s/%d/%s', self::UPLOAD_DIR, $gn->getId(), $filename);
    }

    private function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ('csv' === $ext) {
            return $this->parseCsv($file->getPathname());
        }

        return $this->parseExcel($file->getPathname());
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (false === $handle) {
            return [];
        }

        while (false !== ($row = fgetcsv($handle, 0, ',', '"'))) {
            $rows[] = array_map(static fn ($v) => mb_convert_encoding((string) $v, 'UTF-8', 'UTF-8,ISO-8859-1'), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = (string) ($cell->getValue() ?? '');
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private function normalizeHeaders(array $row): array
    {
        return array_map(static function (string $h): string {
            $h = mb_strtolower($h);
            $h = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h) ?: $h;
            $h = preg_replace('/[^a-z0-9 ]/', ' ', $h) ?? $h;

            return trim(preg_replace('/\s+/', ' ', $h) ?? $h);
        }, $row);
    }

    private function isEmptyRow(array $row): bool
    {
        return 0 === count(array_filter($row, static fn ($v) => '' !== trim((string) $v)));
    }

    /**
     * Extrait le numéro de groupe depuis la cellule "Groupe concerné" (ex: "21    Argos" → 21).
     */
    private function extractGroupeNumero(array $data, string $gnLabel): ?int
    {
        $groupeKey = null;
        foreach (array_keys($data) as $key) {
            if (str_contains($key, 'groupe')) {
                $groupeKey = $key;
                break;
            }
        }

        if (null === $groupeKey || null === $data[$groupeKey]) {
            return null;
        }

        $value = trim((string) $data[$groupeKey]);
        if (preg_match('/^(\d+)/', $value, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function findGroupeGn(Gn $gn, int $groupeNumero): ?GroupeGn
    {
        return $this->entityManager
            ->createQuery('SELECT gg FROM App\Entity\GroupeGn gg
             JOIN gg.groupe g
             WHERE gg.gn = :gn AND g.numero = :numero')
            ->setParameter('gn', $gn)
            ->setParameter('numero', $groupeNumero)
            ->getOneOrNullResult();
    }

    private function upsertFiche(
        GroupeGn $groupeGn,
        array $data,
        array $headers,
        User $user,
        string $archivedPath,
        string $originalFilename,
        array &$ficheCache,
    ): void {
        $cacheKey = $groupeGn->getId();
        if (isset($ficheCache[$cacheKey])) {
            $fiche = $ficheCache[$cacheKey];
            $isNew = false;
        } else {
            $repo = $this->entityManager->getRepository(FicheRetourGroupe::class);
            $fiche = $repo->findOneBy(['groupeGn' => $groupeGn]);
            $isNew = null === $fiche;
        }

        if ($isNew) {
            $fiche = new FicheRetourGroupe();
            $fiche->setGroupeGn($groupeGn);
            $fiche->setCreatedBy($user);
        }

        $ficheCache[$cacheKey] = $fiche;

        $dataBefore = $isNew ? null : $fiche->toArray();

        $this->applyRowData($fiche, $data);
        $fiche->setUpdatedBy($user);
        $fiche->setUpdatedAt(new DateTime());

        $this->entityManager->persist($fiche);

        $history = new FicheRetourGroupeHistory();
        $history->setFicheRetourGroupe($fiche);
        $history->setUser($user);
        $history->setActionType($isNew ? FicheRetourGroupeHistory::ACTION_CREATE : FicheRetourGroupeHistory::ACTION_IMPORT);
        $history->setMotifType(FicheRetourGroupeHistory::ACTION_IMPORT);
        $history->setMotif(sprintf('Import fichier : %s', $originalFilename));
        $history->setImportFilePath($archivedPath);
        $history->setImportOriginalFilename($originalFilename);
        $history->setDataBefore($dataBefore);
        $history->setDataAfter($fiche->toArray());

        $this->entityManager->persist($history);
    }

    private function applyRowData(FicheRetourGroupe $fiche, array $data): void
    {
        foreach ($data as $normalizedHeader => $value) {
            $setter = $this->resolveColumnSetter($normalizedHeader);
            if (null === $setter) {
                continue;
            }

            if ('setSubmittedAt' === $setter) {
                try {
                    $fiche->setSubmittedAt(new DateTime((string) $value));
                } catch (Exception) {
                    // date invalide, on ignore
                }
                continue;
            }

            if ('setCommentaire' === $setter) {
                $fiche->setCommentaire((string) $value ?: null);
                continue;
            }

            $int = (int) preg_replace('/[^0-9]/', '', (string) $value);
            $fiche->$setter($int);
        }
    }

    private function resolveColumnSetter(string $normalizedHeader): ?string
    {
        foreach (self::COLUMN_MAP as $pattern => $setter) {
            if (str_starts_with($normalizedHeader, $pattern)) {
                return $setter;
            }
        }

        return null;
    }
}
