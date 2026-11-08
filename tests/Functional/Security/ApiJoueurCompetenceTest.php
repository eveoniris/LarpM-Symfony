<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Personnage;
use App\Entity\User;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Achat de compétence par l'API joueur : propriétaire seulement, mêmes règles que le site.
 */
#[Group('functional')]
class ApiJoueurCompetenceTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/joueur/personnage/1/competences/disponibles');
        static::assertSame(401, $client->getResponse()->getStatusCode());
        $client->request('POST', '/api/joueur/personnage/1/competences');
        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testListeEtAchatAvecXpSuffisants(): void
    {
        $client = static::createClient();
        [$user, $personnage, $competence] = $this->scenario(1000);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/competences/disponibles', [], [], $auth);
        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{xp: int, verrouille: bool, competences: list<array{id: int, achetable: bool}>} $liste */
        $liste = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertFalse($liste['verrouille']);
        static::assertContains($competence->getId(), array_column($liste['competences'], 'id'));

        $this->acheter($client, $personnage, $competence->getId(), $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $frais = static::getContainer()->get(EntityManagerInterface::class)->find(Personnage::class, $personnage->getId());
        static::assertLessThan(1000, $frais->getXp());

        // Une deuxième fois : la compétence n'est plus disponible.
        $this->acheter($client, $personnage, $competence->getId(), $auth);
        static::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testXpInsuffisantsRenvoie422SansDebit(): void
    {
        $client = static::createClient();
        [$user, $personnage, $competence] = $this->scenario(0);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $this->acheter($client, $personnage, $competence->getId(), $auth);

        static::assertSame(422, $client->getResponse()->getStatusCode());
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        static::assertSame(0, static::getContainer()->get(EntityManagerInterface::class)->find(Personnage::class, $personnage->getId())->getXp());
    }

    public function testPersonnageDunAutreRefuse(): void
    {
        $client = static::createClient();
        [, $personnage, $competence] = $this->scenario(1000);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton(UserFactory::createOne())];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/competences/disponibles', [], [], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
        $this->acheter($client, $personnage, $competence->getId(), $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testCorpsInvalideEtCompetenceInconnue(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(1000);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $client->request('POST', '/api/joueur/personnage/' . $personnage->getId() . '/competences', [], [], $auth + ['CONTENT_TYPE' => 'application/json'], '{}');
        static::assertSame(422, $client->getResponse()->getStatusCode());

        $this->acheter($client, $personnage, 999999999, $auth);
        static::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testGroupeVerrouilleRefuseLAchat(): void
    {
        $client = static::createClient();
        [$user, $personnage, $competence] = $this->scenario(1000);
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => GnFactory::createOne()]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $groupeGn->getGn(), 'groupeGn' => $groupeGn, 'personnage' => $personnage]);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $this->acheter($client, $personnage, $competence->getId(), $auth);

        static::assertSame(403, $client->getResponse()->getStatusCode());
        static::assertStringContainsString('groupe_verrouille', (string) $client->getResponse()->getContent());
    }

    /**
     * @return array{0: User, 1: Personnage, 2: \App\Entity\Competence}
     */
    private function scenario(int $xp): array
    {
        $user = UserFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => CompetenceFamilyFactory::createOne(), 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['user' => $user, 'xp' => $xp]);

        return [$user, $personnage, $competence];
    }

    /** @param array<string, string> $auth */
    private function acheter(KernelBrowser $client, Personnage $personnage, int $competenceId, array $auth): void
    {
        $client->request(
            'POST',
            '/api/joueur/personnage/' . $personnage->getId() . '/competences',
            [],
            [],
            $auth + ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['competence_id' => $competenceId]),
        );
    }

    private function jeton(User $user): string
    {
        // Le jeton embarque l'IP et l'agent de la requête courante : il en faut une.
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
