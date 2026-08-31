<?php

declare(strict_types=1);

namespace App\Tests\Functional\Groupe;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression test for GroupeGnType::personnageTitreFieldOptions (commit b34b576b
 * "New title rules for groupeGn Role").
 *
 * Key rule: the 6 titre fields (suzerain, connetable, intendant, navigateur,
 * camarilla, diplomate) must all be restricted to personnages belonging to
 * participants of the CURRENT groupeGn — not to every participant of the GN,
 * even though other groupeGn belong to the same GN.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class GroupeGnTitreFieldOptionsTest extends WebTestCase
{
    #[TestWith(['suzerain'])]
    #[TestWith(['connetable'])]
    #[TestWith(['intendant'])]
    #[TestWith(['navigateur'])]
    #[TestWith(['camarilla'])]
    #[TestWith(['diplomate'])]
    public function testTitreFieldOnlyOffersPersonnagesOfTheCurrentGroupeGn(string $field): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();

        // Groupe dont on édite le groupeGn : un membre éligible au titre.
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $memberUser = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $memberPersonnage = PersonnageFactory::createOne(['user' => $memberUser, 'vivant' => true]);
        ParticipantFactory::createOne([
            'user' => $memberUser,
            'gn' => $gn,
            'groupeGn' => $groupeGn,
            'personnage' => $memberPersonnage,
        ]);

        // Un autre groupe, participant au MEME GN : ne doit pas apparaitre dans les choix.
        $autreGroupe = GroupeFactory::createOne();
        $autreGroupeGn = GroupeGnFactory::createOne(['groupe' => $autreGroupe, 'gn' => $gn]);
        $autreUser = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $autrePersonnage = PersonnageFactory::createOne(['user' => $autreUser, 'vivant' => true]);
        ParticipantFactory::createOne([
            'user' => $autreUser,
            'gn' => $gn,
            'groupeGn' => $autreGroupeGn,
            'personnage' => $autrePersonnage,
        ]);

        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($admin);

        $crawler = $client->request('GET', '/groupeGn/' . $groupeGn->getId() . '/update');

        static::assertResponseIsSuccessful();

        $optionValues = $crawler
            ->filter(\sprintf('select[name="groupe_gn[%s]"] option', $field))
            ->each(static fn ($node) => $node->attr('value'));

        static::assertContains(
            (string) $memberPersonnage->getId(),
            $optionValues,
            \sprintf('Le personnage du groupeGn courant doit être proposé pour le titre "%s"', $field),
        );
        static::assertNotContains(
            (string) $autrePersonnage->getId(),
            $optionValues,
            \sprintf('Un personnage d\'un autre groupe du même GN ne doit pas être proposé pour le titre "%s"', $field),
        );
    }
}
