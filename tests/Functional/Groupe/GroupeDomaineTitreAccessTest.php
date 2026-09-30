<?php

declare(strict_types=1);

namespace App\Tests\Functional\Groupe;

use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for editing the "Jeu de domaine" titles (suzerain, chef de guerre,
 * intendant, navigateur, eminence grise, diplomate).
 *
 * Key rule: being the suzerain is a property of the *player* (the owner of the
 * personnage holding the title), not of the personnage currently active for that
 * player. A suzerain who owns several personnages, and whose active personnage is
 * therefore not the titled one, must still be able to edit the titles.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class GroupeDomaineTitreAccessTest extends WebTestCase
{
    private const TITRES = ['suzerain', 'connestable', 'intendant', 'navigateur', 'camarilla', 'diplomate'];

    // -------------------------------------------------------------------------
    // Bouton "Modifier" de l'onglet Jeu de domaine
    // -------------------------------------------------------------------------

    public function testSuzerainWhoIsNotChefDeGroupeCanEditTitres(): void
    {
        $client = static::createClient();
        $ctx = $this->creerContexte(secondPersoAuSuzerain: true);

        $client->loginUser($ctx['suzerainUser']);
        $crawler = $client->request('GET', $this->domaineUrl($ctx['groupe'], $ctx['gn'], $ctx['groupeGn']));

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter($this->updateSelector($ctx['groupeGn'])), 'Le suzerain doit avoir le bouton Modifier.');
    }

    public function testChefDeGroupeCanEditTitres(): void
    {
        $client = static::createClient();
        $ctx = $this->creerContexte();

        $client->loginUser($ctx['chefUser']);
        $crawler = $client->request('GET', $this->domaineUrl($ctx['groupe'], $ctx['gn'], $ctx['groupeGn']));

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter($this->updateSelector($ctx['groupeGn'])), 'Le chef de groupe doit avoir le bouton Modifier.');
    }

    public function testSimpleMemberCannotEditTitres(): void
    {
        $client = static::createClient();
        $ctx = $this->creerContexte(avecMembre: true);
        static::assertNotNull($ctx['membreUser']);

        $client->loginUser($ctx['membreUser']);
        $crawler = $client->request('GET', $this->domaineUrl($ctx['groupe'], $ctx['gn'], $ctx['groupeGn']));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter($this->updateSelector($ctx['groupeGn'])), 'Un simple membre ne doit pas avoir le bouton Modifier.');
    }

    // -------------------------------------------------------------------------
    // Formulaire de modification
    // -------------------------------------------------------------------------

    public function testSuzerainSeesTitreFieldsOnUpdatePage(): void
    {
        $client = static::createClient();
        $ctx = $this->creerContexte(secondPersoAuSuzerain: true);

        $client->loginUser($ctx['suzerainUser']);
        $crawler = $client->request('GET', '/groupeGn/' . $ctx['groupeGn']->getId() . '/update');

        static::assertResponseIsSuccessful();

        foreach (self::TITRES as $titre) {
            static::assertCount(1, $crawler->filter(\sprintf('select[name="groupe_gn[%s]"]', $titre)), \sprintf('Le champ "%s" doit être éditable par le suzerain.', $titre));
        }
    }

    /**
     * Un groupe ayant un chef de groupe (qui n'est pas le suzerain) et un suzerain
     * désigné. Les deux sont joueurs du groupeGn.
     *
     * @param bool $secondPersoAuSuzerain Ajoute un 2e personnage au suzerain et l'active :
     *                                     c'est le cas qui échoue si l'on compare le
     *                                     personnage actif au personnage suzerain
     *
     * @return array{
     *     groupe: Groupe,
     *     gn: Gn,
     *     groupeGn: GroupeGn,
     *     chefUser: User,
     *     chefParticipant: Participant,
     *     suzerainUser: User,
     *     suzerainPerso: Personnage,
     *     membreUser: ?User,
     *     membrePerso: ?Personnage
     * }
     */
    private function creerContexte(bool $avecMembre = false, bool $secondPersoAuSuzerain = false): array
    {
        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();

        $chefUser = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $chefPerso = PersonnageFactory::createOne(['user' => $chefUser, 'groupe' => $groupe, 'vivant' => true]);

        $suzerainUser = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $suzerainPerso = PersonnageFactory::createOne(['user' => $suzerainUser, 'groupe' => $groupe, 'vivant' => true]);

        $membreUser = $avecMembre ? UserFactory::createOne(['roles' => ['ROLE_USER']]) : null;
        $membrePerso = $membreUser ? PersonnageFactory::createOne(['user' => $membreUser, 'groupe' => $groupe, 'vivant' => true]) : null;

        $groupeGn = GroupeGnFactory::createOne([
            'groupe' => $groupe,
            'gn' => $gn,
            'suzerain' => $suzerainPerso,
        ]);

        $chefParticipant = ParticipantFactory::createOne([
            'user' => $chefUser,
            'gn' => $gn,
            'groupeGn' => $groupeGn,
            'personnage' => $chefPerso,
        ]);
        ParticipantFactory::createOne([
            'user' => $suzerainUser,
            'gn' => $gn,
            'groupeGn' => $groupeGn,
            'personnage' => $suzerainPerso,
        ]);

        if ($membreUser) {
            ParticipantFactory::createOne([
                'user' => $membreUser,
                'gn' => $gn,
                'groupeGn' => $groupeGn,
                'personnage' => $membrePerso,
            ]);
        }

        if ($secondPersoAuSuzerain) {
            $autrePerso = PersonnageFactory::createOne(['user' => $suzerainUser, 'groupe' => $groupe, 'vivant' => true]);
            $suzerainUser->setPersonnage($autrePerso);
        }

        $groupeGn->setParticipant($chefParticipant);
        $this->flush();

        return [
            'groupe' => $groupe,
            'gn' => $gn,
            'groupeGn' => $groupeGn,
            'chefUser' => $chefUser,
            'chefParticipant' => $chefParticipant,
            'suzerainUser' => $suzerainUser,
            'suzerainPerso' => $suzerainPerso,
            'membreUser' => $membreUser,
            'membrePerso' => $membrePerso,
        ];
    }

    private function domaineUrl(Groupe $groupe, Gn $gn, GroupeGn $groupeGn): string
    {
        return \sprintf('/groupe/%d/detail/domaine/gn/%d/groupeGn/%d', $groupe->getId(), $gn->getId(), $groupeGn->getId());
    }

    private function updateSelector(GroupeGn $groupeGn): string
    {
        return \sprintf('a[href*="/groupeGn/%d/update"]', $groupeGn->getId());
    }

    private function flush(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)->flush();
    }
}