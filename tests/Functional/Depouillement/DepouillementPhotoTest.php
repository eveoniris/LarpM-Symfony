<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\DepouillementPhoto;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Photos d'enveloppe : envoi depuis l'étape Photos, affichage protégé (jamais public), suppression et partage.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class DepouillementPhotoTest extends WebTestCase
{
    /** PNG valide de 1 pixel. */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function dossier(): string
    {
        return static::getContainer()->getParameter('depouillement.upload_dir');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(static::getContainer()->getParameter('depouillement.upload_dir'));
        parent::tearDown();
    }

    private function beta(string $nom = 'moi'): User
    {
        return UserFactory::createOne(['username' => $nom, 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
    }

    private function depouillement(User $depouilleur): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true)
            ->setDepouilleur($depouilleur);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($depouillement);
        $em->flush();

        return $depouillement;
    }

    private function url(Depouillement $depouillement, string $suite = ''): string
    {
        return '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . $suite;
    }

    private function png(string $nom = 'preuve.png'): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), prefix: 'dep');
        file_put_contents($chemin, (string) base64_decode(self::PNG, strict: true));

        return new UploadedFile($chemin, $nom, 'image/png', null, test: true);
    }

    /**
     * Envoie des fichiers par le formulaire de l'étape Photos et retourne le navigateur.
     *
     * @param list<UploadedFile> $fichiers
     */
    private function envoyer(KernelBrowser $client, Depouillement $depouillement, array $fichiers): void
    {
        $crawler = $client->request('GET', $this->url($depouillement, '/etape/photos'));
        $jeton = $crawler->filter('form[enctype="multipart/form-data"] input[name=_token]')->attr('value');

        $client->request('POST', $this->url($depouillement, '/photos'), ['_token' => $jeton], ['photos' => $fichiers]);
    }

    /**
     * @return list<DepouillementPhoto>
     */
    private function photos(): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return array_values($em->getRepository(DepouillementPhoto::class)->findAll());
    }

    public function testEnvoiDePlusieursPhotosPuisAffichageProtege(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $this->envoyer($client, $depouillement, [$this->png('a.png'), $this->png('b.png')]);

        static::assertResponseRedirects($this->url($depouillement, '/etape/photos'));
        $photos = $this->photos();
        static::assertCount(2, $photos);

        $client->request('GET', $this->url($depouillement, '/photos/' . $photos[0]->getId()));
        static::assertResponseIsSuccessful();
        static::assertResponseHeaderSame('Content-Type', 'image/png');
        static::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');

        $client->request('GET', $this->url($depouillement, '/etape/photos'));
        static::assertSelectorCount(2, 'img[loading=lazy]');
        static::assertSelectorTextContains('body', 'a.png');
    }

    public function testUnFichierNonImageEstRefuseAvecUnMessage(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $chemin = tempnam(sys_get_temp_dir(), prefix: 'dep');
        file_put_contents($chemin, data: 'ceci est du texte');

        $this->envoyer($client, $depouillement, [new UploadedFile($chemin, 'faux.jpg', 'image/jpeg', null, true)]);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'faux.jpg : format non accepté');
        static::assertCount(0, $this->photos());
    }

    public function testEnvoiSansFichierOuSansJetonEstRefuse(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        // @mago-expect lint:no-literal-password
        $client->request('POST', $this->url($depouillement, '/photos'), ['_token' => 'faux'], ['photos' => [$this->png()]]);
        static::assertResponseRedirects($this->url($depouillement, '/etape/photos'));
        static::assertCount(0, $this->photos());

        $this->envoyer($client, $depouillement, []);
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'Aucune photo sélectionnée');
    }

    public function testUnUtilisateurSansRapportNePeutPasVoirLaPhoto(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);
        $photo = $this->photos()[0];

        $client->loginUser($this->beta('curieux'));
        $client->request('GET', $this->url($depouillement, '/photos/' . $photo->getId()));

        static::assertResponseRedirects('/access_denied');
    }

    public function testLeStaffSansLeRoleBetaNAccedePasAuxPhotos(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);
        $photo = $this->photos()[0];

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));
        $client->request('GET', $this->url($depouillement, '/photos/' . $photo->getId()));

        static::assertResponseRedirects('/access_denied');
    }

    public function testUnScenaristeBetaTesteurVoitLaPhoto(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);
        $photo = $this->photos()[0];

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']]));
        $client->request('GET', $this->url($depouillement, '/photos/' . $photo->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testPhotoDUnAutreDepouillementOuGnDonne404(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $autre = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);
        $photo = $this->photos()[0];

        $client->request('GET', $this->url($autre, '/photos/' . $photo->getId()));
        static::assertResponseStatusCodeSame(404);

        $client->request('GET', '/gn/' . $autre->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/photos/' . $photo->getId());
        static::assertResponseStatusCodeSame(404);
    }

    public function testSuppressionDUnePhotoParLeDepouilleur(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);
        $photo = $this->photos()[0];
        $chemin = $this->dossier() . '/' . $photo->getChemin();
        static::assertFileExists($chemin);

        $crawler = $client->request('GET', $this->url($depouillement, '/etape/photos'));
        $client->submit($crawler->selectButton('Supprimer')->form());

        static::assertResponseRedirects($this->url($depouillement, '/etape/photos'));
        static::assertCount(0, $this->photos());
        static::assertFileDoesNotExist($chemin);
    }

    public function testSuppressionRefuseQuandLeDepouillementEstTermine(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->find(Depouillement::class, $depouillement->getId())->setStatut(DepouillementStatut::A_VALIDER);
        $em->flush();

        $crawler = $client->request('GET', $this->url($depouillement, '/etape/photos'));
        static::assertSelectorNotExists('form[action$="/supprimer"]');
        static::assertSelectorNotExists('input[type=file]');
        static::assertCount(1, $this->photos());
        static::assertSame(1, $crawler->filter('img[loading=lazy]')->count());
    }

    public function testPartageEtRetraitDuPartage(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->envoyer($client, $depouillement, [$this->png()]);

        $crawler = $client->request('GET', $this->url($depouillement, '/etape/photos'));
        $client->submit($crawler->selectButton('Partager')->form());
        static::assertTrue($this->photos()[0]->isPartage());

        $crawler = $client->request('GET', $this->url($depouillement, '/etape/photos'));
        static::assertSelectorTextContains('body', 'Partagée');
        $client->submit($crawler->selectButton('Retirer le partage')->form());
        static::assertFalse($this->photos()[0]->isPartage());
    }
}
