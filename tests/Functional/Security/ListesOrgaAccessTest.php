<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * SEC-00 : billets, restrictions alimentaires, lieux de restauration et questions étaient lisibles
 * sans être connecté (aucun IsGranted sur les routes de lecture). Lecture réservée à l'orga
 * (et au gestionnaire, hors questions), l'admin héritant de tous les rôles.
 */
#[Group('functional')]
class ListesOrgaAccessTest extends WebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function routesGestion(): iterable
    {
        yield 'billets' => ['/billet/list'];
        yield 'restrictions' => ['/restriction/list'];
        yield 'restrictions impression' => ['/restriction/print'];
        yield 'restauration' => ['/restauration/list'];
    }

    /**
     * L'export CSV termine le processus (exit) : il n'est testé que côté refus.
     *
     * @return iterable<string, array{string}>
     */
    public static function routesProtegees(): iterable
    {
        yield from self::routesGestion();
        yield 'restrictions export' => ['/restriction/download'];
        yield 'questions' => ['/question'];
    }

    #[DataProvider('routesProtegees')]
    public function testAnonymeEstRefuse(string $path): void
    {
        $client = static::createClient();

        $client->request('GET', $path);

        static::assertResponseRedirects();
    }

    #[DataProvider('routesProtegees')]
    public function testJoueurEstRefuse(string $path): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', $path);

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    #[DataProvider('routesGestion')]
    public function testOrgaEtAdminLisent(string $path): void
    {
        $client = static::createClient();
        foreach (['ROLE_ORGA', 'ROLE_ADMIN'] as $role) {
            $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', $role]]));

            $client->request('GET', $path);

            static::assertResponseIsSuccessful(\sprintf('%s doit lire %s', $role, $path));
        }
    }

    public function testOrgaLitLesQuestions(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ORGA']]));

        $client->request('GET', '/question');

        static::assertResponseIsSuccessful();
    }

    #[DataProvider('routesGestion')]
    public function testGestionnaireLitLesListes(string $path): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GESTION']]));

        $client->request('GET', $path);

        static::assertResponseIsSuccessful();
    }

    public function testGestionnaireNeLitPasLesQuestions(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GESTION']]));

        $client->request('GET', '/question');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testListeDesParticipantsDUnBilletEstProtegee(): void
    {
        $client = static::createClient();
        $billet = BilletFactory::createOne();
        $paths = ['/billet/' . $billet->getId() . '/participants', '/billet/' . $billet->getId() . '/detail'];

        foreach ($paths as $path) {
            $client->request('GET', $path);
            static::assertResponseRedirects(null, null, 'Anonyme : ' . $path);
        }

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        foreach ($paths as $path) {
            $client->request('GET', $path);
            static::assertNotSame(200, $client->getResponse()->getStatusCode(), 'Joueur : ' . $path);
        }

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ORGA']]));
        foreach ($paths as $path) {
            $client->request('GET', $path);
            static::assertResponseIsSuccessful('Orga : ' . $path);
        }
    }
}
