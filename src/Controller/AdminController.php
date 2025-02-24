<?php

namespace App\Controller;

use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Enum\BonusPeriode;
use App\Repository\PersonnageRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Criteria;
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
    public function indexAction(): Response
    {
        $extensions = get_loaded_extensions();
        $phpVersion = PHP_VERSION;
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
        $docTotalSpace = $this->getSymbolByQuantity($this->foldersize(__DIR__.'/../../private/doc'));

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
                if (str_contains($linetmp, 'CRITICAL')) {
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
            echo 'impossible d\'ouvrir '.$filename;
        }

        $lines = array_reverse($lines);
        $linesFatal = array_reverse($linesFatal);

        return $this->render('log.twig', [
            'lines' => $lines,
            'linesFatal' => $linesFatal,
        ]);
    }

    /**
     * Vider le cache.
     */
    #[Route('/admin/cache/empty', name: 'admin.cache.empty')]
    public function cacheEmptyAction(): RedirectResponseAlias
    {
        shell_exec('php -d memory_limit=-1 '.__DIR__.'/../../bin/console cache:clear');

        $this->addFlash('success', 'Le cache a été vidé.');

        return $this->redirectToRoute('admin', [], 303);
    }

    /**
     * Vider les logs.
     */
    #[Route('/admin/log/empty', name: 'admin.log.empty')]
    public function logEmptyAction(): RedirectResponseAlias
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
        $alias = 'u';

        $orderBy = $this->getRequestOrder(
            defOrderBy: 'email',
            alias: $alias,
            allowedFields: $repository->getFieldNames()
        );

        $paginator = $repository->getPaginator(
            limit: $this->getRequestLimit(25),
            page: $this->getRequestPage(),
            orderBy: $orderBy,
            alias: $alias,
            criterias: [Criteria::create()->where(
                Criteria::expr()?->isNull($alias.'.etatCivil')
            )]
        );

        return $this->render(
            'admin/rappels.twig',
            ['paginator' => $paginator, 'orderDir' => $this->getRequestOrderDir()]
        );
    }

    #[Route('/originebonus', name: 'originebonus')]
    public function bonusOrigineAction(PersonnageRepository $rep): void
    {
        /** @var Personnage $personnage */
        foreach ($rep->findBy(['vivant' => true]) as $personnage) {
            if (!$territoire = $personnage->getTerritoire()) {
                continue;
            }
            if (!$groupe = $personnage->getFirstParticipantGnGroupe()) {
                continue;
            }

            if (!$origine = $groupe->getTerritoire()) {
                continue;
            }

            if ($territoire->getId() !== $groupe->getTerritoire()?->getId()) {
                continue;
            }

            foreach ($origine->getValideOrigineBonus() as $bonus) {
                if (BonusPeriode::NATIVE === $bonus->getPeriode()) {
                    $pBonus = new PersonnageBonus();
                    $pBonus->setPersonnage($personnage);
                    $pBonus->setBonus($bonus);
                    $this->entityManager->persist($pBonus);
                    $this->entityManager->flush($pBonus);
                }
            }
        }

        echo 'ok';
        exit;
    }
}
