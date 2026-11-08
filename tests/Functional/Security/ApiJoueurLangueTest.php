<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Langue;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\User;
use App\Enum\TriggerType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\LangueFactory;
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
 * Choix de langue courante ou commune via le trigger LANGUE COURANTE : propriétaire seulement.
 */
#[Group('functional')]
class ApiJoueurLangueTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/joueur/personnage/1/langues/courante');
        static::assertSame(401, $client->getResponse()->getStatusCode());
        $client->request('POST', '/api/joueur/personnage/1/langues/commune');
        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testListeEtChoixConsommentLeTrigger(): void
    {
        $client = static::createClient();
        [$user, $personnage, $langue] = $this->scenario(1);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/langues/courante', [], [], $auth);
        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{langues: list<array{id: int}>} $liste */
        $liste = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertContains($langue->getId(), array_column($liste['langues'], 'id'));

        $this->choisir($client, $personnage, 'courante', $langue->getId(), $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $frais = $em->find(Personnage::class, $personnage->getId());
        static::assertTrue($frais->isKnownLanguage($em->find(Langue::class, $langue->getId())));
        static::assertFalse($frais->hasTrigger(TriggerType::LANGUE_COURANTE));

        // Plus de choix en attente : refusé.
        $this->choisir($client, $personnage, 'courante', $langue->getId(), $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
        static::assertStringContainsString('aucun_choix', (string) $client->getResponse()->getContent());
    }

    public function testLangueCommuneEtMauvaisType(): void
    {
        $client = static::createClient();
        [$user, $personnage, $courante] = $this->scenario(1);
        $commune = LangueFactory::createOne(['diffusion' => 2]);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        // Une langue courante ne peut pas être prise dans la liste des communes.
        $this->choisir($client, $personnage, 'commune', $courante->getId(), $auth);
        static::assertSame(422, $client->getResponse()->getStatusCode());

        $this->choisir($client, $personnage, 'commune', $commune->getId(), $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());
    }

    public function testCorpsInvalideEtLangueInconnue(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(1);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $client->request('POST', '/api/joueur/personnage/' . $personnage->getId() . '/langues/courante', [], [], $auth + ['CONTENT_TYPE' => 'application/json'], '{}');
        static::assertSame(422, $client->getResponse()->getStatusCode());

        $this->choisir($client, $personnage, 'courante', 999999999, $auth);
        static::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testPersonnageDunAutreRefuse(): void
    {
        $client = static::createClient();
        [, $personnage, $langue] = $this->scenario(1);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton(UserFactory::createOne())];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/langues/courante', [], [], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
        $this->choisir($client, $personnage, 'courante', $langue->getId(), $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testGroupeVerrouilleRefuse(): void
    {
        $client = static::createClient();
        [$user, $personnage, $langue] = $this->scenario(1);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['lock' => true]), 'gn' => GnFactory::createOne()]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $groupeGn->getGn(), 'groupeGn' => $groupeGn, 'personnage' => $personnage]);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $this->choisir($client, $personnage, 'courante', $langue->getId(), $auth);

        static::assertSame(403, $client->getResponse()->getStatusCode());
        static::assertStringContainsString('groupe_verrouille', (string) $client->getResponse()->getContent());
    }

    /**
     * @return array{0: User, 1: Personnage, 2: Langue}
     */
    private function scenario(int $diffusion): array
    {
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setTag(TriggerType::LANGUE_COURANTE->value);
        $trigger->setDone(false);
        $em->persist($trigger);
        $em->flush();
        $personnage->addPersonnageTrigger($trigger);

        return [$user, $personnage, LangueFactory::createOne(['diffusion' => $diffusion])];
    }

    /** @param array<string, string> $auth */
    private function choisir(KernelBrowser $client, Personnage $personnage, string $type, int $langueId, array $auth): void
    {
        $client->request(
            'POST',
            '/api/joueur/personnage/' . $personnage->getId() . '/langues/' . $type,
            [],
            [],
            $auth + ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['langue_id' => $langueId]),
        );
    }

    private function jeton(User $user): string
    {
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
