<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * App\Controllers\AdminController.
 */
class AdminController extends AbstractController
{
    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    private function file_upload_max_size()
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
    private function parse_size(string|bool $size)
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
            if ('.' != $t && '..' != $t) {
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
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $extensions = get_loaded_extensions();
        $phpVersion = phpversion();
        $zendVersion = zend_version();
        $uploadMaxSize = $this->file_upload_max_size();

        // taille du cache
        $cacheTotalSpace = $this->foldersize(__DIR__.'/../../../cache');
        if ($cacheTotalSpace) {
            $cacheTotalSpace = $this->getSymbolByQuantity($cacheTotalSpace);
        }

        // taille du log
        $logTotalSpace = $this->getSymbolByQuantity($this->foldersize(__DIR__.'/../../../logs'));

        // taille des documents
        $docTotalSpace = $this->getSymbolByQuantity($this->foldersize(__DIR__.'/../../../private/doc'));

        return $this->render('admin/index.twig', [
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
    public function logAction(Request $request,  EntityManagerInterface $entityManager)
    {
        if ('prod' == $app['config']['env']['env']) {
            $filename = __DIR__.'/../../../logs/production.log';
        } else {
            $filename = __DIR__.'/../../../logs/development.log';
        }

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

        return $this->render('admin/log.twig', [
            'lines' => $lines,
            'linesFatal' => $linesFatal,
        ]);
    }

    /**
     * Exporter la base de données.
     */
    public function databaseExportAction(Request $request,  EntityManagerInterface $entityManager)
    {
        return $this->render('admin/databaseExport.twig');
    }

    /**
     * Mettre à jour la base de données.
     */
    public function databaseUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        return $this->render('admin/databaseUpdate.twig');
    }

    /**
     * Vider le cache.
     */
    public function cacheEmptyAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $app['twig']->clearTemplateCache();
        $app['twig']->clearCacheFiles();

       $this->addFlash('success', 'Le cache a été vidé.');

        return $this->redirectToRoute('admin', [], 303);
    }

    /**
     * Vider les logs.
     */
    public function logEmptyAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $filename = __DIR__.'/../../../logs/production.log';

        $myTextFileHandler = @fopen($filename, 'r+');
        @ftruncate($myTextFileHandler, 0);
        @fclose($myTextFileHandle);

        $filename = __DIR__.'/../../../logs/development.log';

        $myTextFileHandler = @fopen($filename, 'r+');
        @ftruncate($myTextFileHandler, 0);
        @fclose($myTextFileHandle);

       $this->addFlash('success', 'Les logs ont été vidés.');

        return $this->redirectToRoute('admin', [], 303);
    }

    /**
     * Fourni les listes des utilisateurs n'ayants pas remplis certaines conditions.
     */
    #[Route('/admin/rappels', name: 'admin.rappels')]
    public function rappelsAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\User::class);

        $UsersWithoutEtatCivil = $repo->findWithoutEtatCivil();

        return $this->render('admin/rappels.twig', [
            'UsersWithoutEtatCivil' => $UsersWithoutEtatCivil,
        ]);
    }
}
