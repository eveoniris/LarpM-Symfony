<?php

declare(strict_types=1);

namespace App\Tests\Functional\Groupe;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Onglet « membres » : l'URL peut porter ?groupeGn= sans ?gn= (régression : 500 TypeError en production).
 */
#[Group('functional')]
class GroupeMembresOngletTest extends WebTestCase
{
    public function testOngletMembresAvecGroupeGnSansGnNePlantePas(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn]);

        $client->loginUser($user);
        $client->request('GET', \sprintf('/groupe/%d/detail/membres?groupeGn=%d', $groupe->getId(), $groupeGn->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testOngletMembresSansAucunParametreNePlantePas(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn]);

        $client->loginUser($user);
        $client->request('GET', '/groupe/' . $groupe->getId() . '/detail/membres');

        static::assertResponseIsSuccessful();
    }
}
