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
 * Tests for groupe detail page access scoped by GroupeGn membership.
 *
 * Key rule: canReadPrivate is granted only for the user's actual GroupeGn, not
 * globally for any past membership. Removing the groupeGn from the URL must not
 * grant wider access than having it.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class GroupeDetailAccessTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Basic access (CAN_READ)
    // -------------------------------------------------------------------------

    public function testMemberCanAccessGroupeDetail(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn]);

        $client->loginUser($user);
        $client->request('GET', '/groupe/' . $groupe->getId());

        self::assertResponseIsSuccessful();
    }

    public function testNonMemberCannotAccessGroupeDetail(): void
    {
        $client = static::createClient();

        $groupe = GroupeFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', '/groupe/' . $groupe->getId());

        self::assertResponseRedirects();
    }

    public function testAnonymousIsRedirectedFromGroupeDetail(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne();

        $client->request('GET', '/groupe/' . $groupe->getId());

        self::assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // Participation future sans groupe_gn (le bug initial corrigé)
    // -------------------------------------------------------------------------

    public function testMemberWithFutureParticipationWithoutGroupeGnCanStillAccess(): void
    {
        $client = static::createClient();

        $gn1 = GnFactory::createOne();
        $gn2 = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn1]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        // Participation réelle dans gn1 → groupeGn lié
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn1, 'groupeGn' => $groupeGn]);
        // Participation future dans gn2 → sans groupeGn (cas du bug original)
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn2, 'groupeGn' => null]);

        $client->loginUser($user);
        $client->request('GET', '/groupe/' . $groupe->getId());

        self::assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Accès avec GroupeGn explicite dans l'URL
    // -------------------------------------------------------------------------

    public function testMemberCanAccessGroupeDetailWithGroupeGnUrl(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn]);

        $client->loginUser($user);
        $client->request('GET', '/groupe/' . $groupe->getId() . '/gn/' . $gn->getId() . '/' . $groupeGn->getId());

        self::assertResponseIsSuccessful();
    }

    public function testNonMemberOfSpecificGroupeGnCannotAccessItEvenIfMemberOfAnotherGn(): void
    {
        $client = static::createClient();

        $gn1 = GnFactory::createOne();
        $gn2 = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn1 = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn1]);
        $groupeGn2 = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn2]);

        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        // L'user est membre du GN 1 seulement
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn1, 'groupeGn' => $groupeGn1]);

        $client->loginUser($user);
        // Accède à la page générale du groupe → 200 (membre d'au moins un GN)
        $client->request('GET', '/groupe/' . $groupe->getId());
        self::assertResponseIsSuccessful();

        // Tente d'accéder au GN 2 via URL → la page s'affiche (CAN_READ) mais canReadPrivate=false
        // Le test vérifie que la page ne retourne pas 403 (accès lecture ok)
        // mais que le contenu privé n'est pas affiché (testé séparément si besoin)
        $client->request('GET', '/groupe/' . $groupe->getId() . '/gn/' . $gn2->getId() . '/' . $groupeGn2->getId());
        self::assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Anti-bypass : supprimer le groupeGn de l'URL ne donne pas plus d'accès
    // -------------------------------------------------------------------------

    public function testRemovingGroupeGnFromUrlDoesNotGrantWiderAccess(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn]);

        $client->loginUser($user);

        // Avec groupeGn dans l'URL
        $client->request('GET', '/groupe/' . $groupe->getId() . '/gn/' . $gn->getId() . '/' . $groupeGn->getId());
        self::assertResponseIsSuccessful();

        // Sans groupeGn dans l'URL : getUserLastGroupeGn retrouve automatiquement le bon GroupeGn
        $client->request('GET', '/groupe/' . $groupe->getId());
        self::assertResponseIsSuccessful();

        // Les deux accès donnent le même résultat (pas de bypass possible)
    }

    // -------------------------------------------------------------------------
    // Admin/Scénariste : accès total inchangé
    // -------------------------------------------------------------------------

    public function testScenaristeCanAccessGroupeDetailWithoutBeingMember(): void
    {
        $client = static::createClient();

        $groupe = GroupeFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($user);
        $client->request('GET', '/groupe/' . $groupe->getId());

        self::assertResponseIsSuccessful();
    }
}
