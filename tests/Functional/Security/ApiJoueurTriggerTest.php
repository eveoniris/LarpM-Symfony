<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\User;
use App\Enum\TriggerType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Liste des choix en attente (triggers) : propriétaire seulement.
 */
#[Group('functional')]
class ApiJoueurTriggerTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/joueur/personnage/1/triggers');

        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testListeLesChoixEnAttenteSeulement(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $this->trigger($personnage, TriggerType::LANGUE_COURANTE);
        $this->trigger($personnage, TriggerType::TECHNOLOGIE)->consume('Forge');
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/triggers', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)]);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{verrouille: bool, triggers: list<array{tag: string, description: string}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertFalse($data['verrouille']);
        static::assertCount(1, $data['triggers']);
        static::assertSame('LANGUE COURANTE', $data['triggers'][0]['tag']);
        static::assertSame('permet de choisir une langue commune', $data['triggers'][0]['description']);
    }

    public function testPersonnageDunAutreRefuseEtInconnu404(): void
    {
        $client = static::createClient();
        $personnage = PersonnageFactory::createOne();
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton(UserFactory::createOne())];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/triggers', [], [], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/joueur/personnage/999999999/triggers', [], [], $auth);
        static::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testGroupeVerrouilleIndiqueDansLaReponse(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['lock' => true]), 'gn' => GnFactory::createOne()]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $groupeGn->getGn(), 'groupeGn' => $groupeGn, 'personnage' => $personnage]);

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/triggers', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)]);

        static::assertTrue(json_decode((string) $client->getResponse()->getContent(), true)['verrouille']);
    }

    private function trigger(Personnage $personnage, TriggerType $type): PersonnageTrigger
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setTag($type->value);
        $trigger->setDone(false);
        $em->persist($trigger);
        $em->flush();
        $personnage->addPersonnageTrigger($trigger);

        return $trigger;
    }

    private function jeton(User $user): string
    {
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
