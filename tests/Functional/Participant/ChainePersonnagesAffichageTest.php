<?php

declare(strict_types=1);

namespace App\Tests\Functional\Participant;

use App\Entity\Participant;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\EtatCivilFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\PersonnageSecondaireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Affichage de la chaîne de personnages : détail de la participation (complet) et
 * listes (badges compacts).
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class ChainePersonnagesAffichageTest extends WebTestCase
{
    public function testLeDetailDeParticipationAfficheLesQuatreRoles(): void
    {
        $client = static::createClient();
        $participant = $this->participationComplete($client, substitutionActive: true);

        $crawler = $client->request('GET', '/participant/' . $participant->getId() . '/index');

        static::assertResponseIsSuccessful();

        $texte = $crawler->filter('body')->text();
        static::assertStringContainsString('Personnage principal', $texte);
        static::assertStringContainsString('Personnage de substitution', $texte);
        static::assertStringContainsString('Personnage de relève', $texte);
        static::assertStringContainsString('Archétype de secours', $texte);
    }

    public function testLeRoleSubstitutionEstMasqueSiLOpusNeLeProposePas(): void
    {
        $client = static::createClient();
        $participant = $this->participationComplete($client, substitutionActive: false);

        $crawler = $client->request('GET', '/participant/' . $participant->getId() . '/index');

        static::assertResponseIsSuccessful();

        $texte = $crawler->filter('body')->text();
        static::assertStringNotContainsString('Personnage de substitution', $texte);
        static::assertStringContainsString('Personnage de relève', $texte);
    }

    public function testSansSubstitutionChoisieLeMessageExpliqueLeDoubleRole(): void
    {
        $client = static::createClient();
        $participant = $this->participationComplete($client, substitutionActive: true, avecSubstitution: false);

        $crawler = $client->request('GET', '/participant/' . $participant->getId() . '/index');

        static::assertStringContainsString(
            'endosse les deux rôles',
            $crawler->filter('body')->text(),
        );
    }

    public function testLaListeDesParticipantsAfficheDesBadgesEtPasLaChaineComplete(): void
    {
        $client = static::createClient();
        $participant = $this->participationComplete($client, substitutionActive: true);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));
        $crawler = $client->request('GET', '/gn/' . $participant->getGn()->getId() . '/participants');

        static::assertResponseIsSuccessful();

        // Les libellés complets alourdiraient la liste : seuls les badges sont présents.
        static::assertStringNotContainsString('Archétype de secours :', $crawler->filter('body')->text());

        $badges = $crawler->filter('.badge[title^="Personnage de relève"], .badge[title^="Personnage de substitution"], .badge[title^="Archétype de secours"]');
        static::assertGreaterThanOrEqual(3, $badges->count());
    }

    public function testLesBadgesNApparaissentPasQuandAucunRoleNEstPourvu(): void
    {
        $client = static::createClient();
        $participant = $this->participationComplete(
            $client,
            substitutionActive: true,
            avecSubstitution: false,
            avecReleve: false,
            avecArchetype: false,
        );

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));
        $crawler = $client->request('GET', '/gn/' . $participant->getGn()->getId() . '/participants');

        static::assertCount(0, $crawler->filter('.badge[title^="Personnage de relève"]'));
    }

    private function participationComplete(
        KernelBrowser $client,
        bool $substitutionActive,
        bool $avecSubstitution = true,
        bool $avecReleve = true,
        bool $avecArchetype = true,
    ): Participant {
        $gn = GnFactory::createOne(['substitutionActive' => $substitutionActive]);
        // ParticipantRepository::search() joint user.etatCivil en INNER JOIN : sans état
        // civil, le joueur n'apparaît pas dans la liste des participants du GN.
        $user = UserFactory::createOne(['etatCivil' => EtatCivilFactory::createOne(['nom' => 'Dupont'])]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]);

        $attributs = [
            'gn' => $gn,
            'user' => $user,
            'groupeGn' => $groupeGn,
            'billet' => BilletFactory::createOne(['gn' => $gn, 'user' => $user]),
            'personnage' => PersonnageFactory::createOne(['user' => $user, 'nom' => 'Principal']),
        ];

        if ($avecSubstitution) {
            $attributs['personnageSubstitution'] = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Substitut']);
        }

        if ($avecReleve) {
            $attributs['personnageReleve'] = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Releve']);
        }

        if ($avecArchetype) {
            $attributs['personnageSecondaire'] = PersonnageSecondaireFactory::createOne([
                'classe' => ClasseFactory::createOne(['label_masculin' => 'Soldat', 'label_feminin' => 'Soldate']),
            ]);
        }

        $participant = ParticipantFactory::createOne($attributs);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->loginUser($user);

        return $participant;
    }
}
