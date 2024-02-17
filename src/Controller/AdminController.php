<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectResponseAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    private function file_upload_max_size(): float|int
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $max_size = $this->parse_size(ini_get('post_max_size'));

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }

        return $max_size;
    }

    /**
     * Met en forme une taille.
     *
     * @param unknown $size
     */
    private function parse_size(string|bool $size): float
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * (1024 ** stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    /**
     * Simplifie une taille en bytes et fourni le symbole adequat.
     *
     * @param unknown $bytes
     */
    private function getSymbolByQuantity($bytes): string
    {
        $symbols = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $exp = $bytes ? floor(log($bytes) / log(1024)) : 0;

        return sprintf('%.2f '.$symbols[$exp], $bytes / pow(1024, floor($exp)));
    }

    /**
     * Calcul la taille d'un dossier.
     *
     * @param unknown $path
     */
    private function foldersize(string $path): int|float
    {
        $total_size = 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/').'/';

        foreach ($files as $t) {
            if ('.' !== $t && '..' !== $t) {
                $currentFile = $cleanPath.$t;
                if (is_dir($currentFile)) {
                    $size = $this->foldersize($currentFile);
                    $total_size += $size;
                } else {
                    $size = filesize($currentFile);
                    $total_size += $size;
                }
            }
        }

        return $total_size;
    }

    /**
     * Page d'accueil de l'interface d'administration.
     */
    #[Route('/admin', name: 'admin')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $extensions = get_loaded_extensions();
        $phpVersion = phpversion();
        $zendVersion = zend_version();
        $uploadMaxSize = $this->file_upload_max_size();

        // taille du cache
        $cacheTotalSpace = $this->foldersize(__DIR__.'/../../var/cache');
        if ($cacheTotalSpace) {
            $cacheTotalSpace = $this->getSymbolByQuantity($cacheTotalSpace);
        }

        // taille du log
        $logTotalSpace = $this->getSymbolByQuantity($this->foldersize(__DIR__.'/../../var/log'));

        // taille des documents
        // TODO $docTotalSpace = $this->getSymbolByQuantity($this->foldersize(__DIR__.'/../../private/doc'));
        $docTotalSpace = 0;

        return $this->render('index.twig', [
            'phpVersion' => $phpVersion,
            'zendVersion' => $zendVersion,
            'uploadMaxSize' => $uploadMaxSize,
            'extensions' => $extensions,
            'cacheTotalSpace' => $cacheTotalSpace,
            'logTotalSpace' => $logTotalSpace,
            'docTotalSpace' => $docTotalSpace,
        ]);
    }

    /**
     * Consulter les logs de larpManager.
     */
    #[Route('/admin/log', name: 'admin.log')]
    public function logAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        // TODO if ('prod' === $app['config']['env']['env']) {
        // $filename = __DIR__.'/../../../log/prod.log';
        // } else {
        $filename = __DIR__.'/../../var/log/dev.log';
        // }

        $logfile = new \SplFileObject($filename);

        $lines = [];
        $linesFatal = [];

        $handle = fopen($filename, 'r');
        if ($logfile) {
            $lineCount = 0;
            while (!$logfile->eof()) {
                $linetmp = $logfile->current();
                if (1 === preg_match('/CRITICAL/', $linetmp)) {
                    $linesFatal[] = $linetmp;
                }

                ++$lineCount;
                $logfile->next();
            }

            $start = $lineCount - 20;
            if ($start < 0) {
                $start = 0;
            }

            $logfile->seek($start);
            while (!$logfile->eof()) {
                $lines[] = $logfile->current();
                $logfile->next();
            }
        } else {
            var_dump('impossible d\'ouvrir '.$filename);
        }

        $lines = array_reverse($lines);
        $linesFatal = array_reverse($linesFatal);

        return $this->render('log.twig', [
            'lines' => $lines,
            'linesFatal' => $linesFatal,
        ]);
    }

    /**
     * Exporter la base de données.
     */
    #[Route('/admin/database/export', name: 'admin.database.export')]
    public function databaseExportAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        // TODO ?
        return $this->render('databaseExport.twig');
    }

    /**
     * Mettre à jour la base de données.
     */
    #[Route('/admin/database/update', name: 'admin.database.update')]
    public function databaseUpdateAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        // TODO ?
        return $this->render('databaseUpdate.twig');
    }

    /**
     * Vider le cache.
     */
    #[Route('/admin/cache/empty', name: 'admin.cache.empty')]
    public function cacheEmptyAction(Request $request, EntityManagerInterface $entityManager): RedirectResponseAlias
    {
       dump( shell_exec('php -d memory_limit=-1 '.__DIR__.'/../../bin/console cache:clear'));

        $this->addFlash('success', 'Le cache a été vidé.');

        return $this->redirectToRoute('admin', [], 303);
    }

    /**
     * Vider les logs.
     */
    #[Route('/admin/log/empty', name: 'admin.log.empty')]
    public function logEmptyAction(Request $request, EntityManagerInterface $entityManager): RedirectResponseAlias
    {
        file_put_contents(__DIR__.'/../../var/log/prod.log', '');
        file_put_contents(__DIR__.'/../../var/log/dev.log', '');

        $this->addFlash('success', 'Les logs ont été vidés.');

        return $this->redirectToRoute('admin', [], 303);
    }

    /**
     * Fourni les listes des utilisateurs n'ayants pas remplis certaines conditions.
     */
    #[Route('/admin/rappels', name: 'admin.rappels')]
    public function rappelsAction(Request $request, UserRepository $repository): Response
    {
        return $this->render(
            'admin/rappels.twig',
            ['usersWithoutEtatCivil' => $repository->findWithoutEtatCivil()]
        );
    }
}
