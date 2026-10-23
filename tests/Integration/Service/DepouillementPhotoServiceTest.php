<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementPhoto;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\PhotoInvalideException;
use App\Service\DepouillementPhotoService;
use App\Service\DepouillementService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Photos d'enveloppe : stockage hors du dossier public, validation d'après le contenu, suppression des fichiers.
 *
 * DAMA enveloppe chaque test dans une transaction annulée ; les fichiers du dossier de test sont supprimés à la fin.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class DepouillementPhotoServiceTest extends KernelTestCase
{
    /** PNG valide de 1 pixel. */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private EntityManagerInterface $em;

    private DepouillementPhotoService $service;

    private string $dossier;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementPhotoService::class);
        $this->dossier = static::getContainer()->getParameter('depouillement.upload_dir');
        (new Filesystem())->remove($this->dossier);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dossier);
        parent::tearDown();
    }

    private function depouillement(): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    private function fichier(string $contenu, string $nom, string $mime): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), prefix: 'dep');
        file_put_contents($chemin, $contenu);

        return new UploadedFile($chemin, $nom, $mime, null, test: true);
    }

    private function png(string $nom = 'ma photo.png'): UploadedFile
    {
        return $this->fichier((string) base64_decode(self::PNG, strict: true), $nom, 'image/png');
    }

    public function testAjouterStockeLeFichierHorsDuDossierPublicSousUnNomAleatoire(): void
    {
        $depouillement = $this->depouillement();
        $utilisateur = UserFactory::createOne();

        $photo = $this->service->ajouter($depouillement, $this->png(), $utilisateur);

        $chemin = $this->service->cheminComplet($photo);
        static::assertFileExists($chemin);
        static::assertStringStartsWith($this->dossier . '/' . $depouillement->getGn()->getId() . '/' . $depouillement->getId() . '/', $chemin);
        static::assertMatchesRegularExpression('#/[0-9a-f]{32}\.png$#', $chemin);
        static::assertStringNotContainsString('ma photo', $chemin);
        static::assertSame('ma photo.png', $photo->getNomOriginal());
        static::assertSame('image/png', $photo->getMimeType());
        static::assertSame($utilisateur->getId(), $photo->getTeleversePar()?->getId());
        static::assertFalse($photo->isPartage());
        static::assertCount(1, $depouillement->getHistories());
    }

    public function testUnFichierQuiNEstPasUneImageEstRefuseMemeAvecUneExtensionImage(): void
    {
        $depouillement = $this->depouillement();
        $faux = $this->fichier('<?php echo "pas une image";', 'virus.jpg', 'image/jpeg');

        try {
            $this->service->ajouter($depouillement, $faux, UserFactory::createOne());
            static::fail('Une exception était attendue.');
        } catch (PhotoInvalideException $exception) {
            static::assertStringContainsString('format non accepté', $exception->getMessage());
        }

        static::assertCount(0, $depouillement->getPhotos());
        static::assertDirectoryDoesNotExist($this->dossier);
    }

    public function testUnePhotoTropLourdeEstRefusee(): void
    {
        $depouillement = $this->depouillement();
        $lourde = $this->fichier((string) base64_decode(self::PNG, strict: true) . str_repeat("\0", DepouillementPhotoService::TAILLE_MAX), 'grosse.png', 'image/png');

        $this->expectException(PhotoInvalideException::class);
        $this->expectExceptionMessage('dépasse 10 Mo');

        $this->service->ajouter($depouillement, $lourde, UserFactory::createOne());
    }

    public function testImpossibleDAjouterUnePhotoQuandLaSaisieEstTerminee(): void
    {
        $depouillement = $this->depouillement()->setStatut(DepouillementStatut::A_VALIDER);

        $this->expectException(PhotoInvalideException::class);
        $this->expectExceptionMessage('plus modifiable');

        $this->service->ajouter($depouillement, $this->png(), UserFactory::createOne());
    }

    public function testSupprimerRetireLeFichierEtLaLigne(): void
    {
        $depouillement = $this->depouillement();
        $utilisateur = UserFactory::createOne();
        $photo = $this->service->ajouter($depouillement, $this->png(), $utilisateur);
        $chemin = $this->service->cheminComplet($photo);
        $id = $photo->getId();

        $this->service->supprimer($photo, $utilisateur);

        static::assertFileDoesNotExist($chemin);
        $this->em->clear();
        static::assertNull($this->em->find(DepouillementPhoto::class, $id));
    }

    public function testBasculerPartageAlterneEtEstTrace(): void
    {
        $depouillement = $this->depouillement();
        $utilisateur = UserFactory::createOne();
        $photo = $this->service->ajouter($depouillement, $this->png(), $utilisateur);

        static::assertTrue($this->service->basculerPartage($photo, $utilisateur));
        static::assertTrue($photo->isPartage());
        static::assertFalse($this->service->basculerPartage($photo, $utilisateur));
        static::assertFalse($photo->isPartage());
        // Ajout + partage + retrait du partage.
        static::assertCount(3, $depouillement->getHistories());
    }

    public function testSupprimerUneSimulationOuPurgerSupprimeAussiLesFichiers(): void
    {
        $service = static::getContainer()->get(DepouillementService::class);
        $utilisateur = UserFactory::createOne();
        $premiere = $this->depouillement();
        $seconde = $this->depouillement();
        $cheminPremiere = $this->service->cheminComplet($this->service->ajouter($premiere, $this->png(), $utilisateur));
        $cheminSeconde = $this->service->cheminComplet($this->service->ajouter($seconde, $this->png(), $utilisateur));

        static::assertTrue($service->supprimerSimulation($premiere));
        static::assertFileDoesNotExist($cheminPremiere);
        static::assertFileExists($cheminSeconde);

        $service->purgerSimulations();
        static::assertFileDoesNotExist($cheminSeconde);
    }
}
