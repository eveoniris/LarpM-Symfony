<?php

declare(strict_types=1);

namespace App\Tests\Functional\Participant;

use App\Entity\Participant;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * Choix des personnages alternatifs d'une participation : relève, substitution et
 * archétype de secours.
 *
 * Règles couvertes :
 * - la substitution n'est accessible que si l'opus active l'option ;
 * - billet + personnage principal sont obligatoires ;
 * - le verrouillage du groupe bloque les trois choix (l'admin passe outre) ;
 * - la liste de choix exclut les personnages déjà engagés sur le GN.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class PersonnageAlternatifTest extends WebTestCase
{
    #[TestWith(['personnageReleve'])]
    #[TestWith(['personnageSecondaire'])]
    public function testChoixRefuseSansBillet(string $route): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, avecBillet: false);

        $client->request('GET', sprintf('/participant/%d/%s', $contexte['participant']->getId(), $route));

        static::assertResponseRedirects('/gn/' . $contexte['gn']->getId());
    }

    #[TestWith(['personnageReleve'])]
    #[TestWith(['personnageSecondaire'])]
    public function testChoixRefuseSansPersonnagePrincipal(string $route): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, avecPersonnage: false);

        $client->request('GET', sprintf('/participant/%d/%s', $contexte['participant']->getId(), $route));

        static::assertResponseRedirects('/gn/' . $contexte['gn']->getId());
    }

    #[TestWith(['personnageReleve'])]
    #[TestWith(['personnageSecondaire'])]
    public function testChoixAccessibleAvecBilletEtPersonnage(string $route): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client);

        $client->request('GET', sprintf('/participant/%d/%s', $contexte['participant']->getId(), $route));

        static::assertResponseIsSuccessful();
    }

    public function testSubstitutionInaccessibleSiLOpusNeLaProposePas(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, substitutionActive: false);

        $client->request('GET', sprintf('/participant/%d/personnageSubstitution', $contexte['participant']->getId()));

        static::assertResponseRedirects('/participant/' . $contexte['participant']->getId() . '/index');
    }

    public function testSubstitutionAccessibleSiLOpusLaPropose(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, substitutionActive: true);

        $client->request('GET', sprintf('/participant/%d/personnageSubstitution', $contexte['participant']->getId()));

        static::assertResponseIsSuccessful();
    }

    #[TestWith(['personnageReleve'])]
    #[TestWith(['personnageSubstitution'])]
    #[TestWith(['personnageSecondaire'])]
    public function testGroupeVerrouilleBloqueLeChoix(string $route): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, substitutionActive: true, verrouille: true);

        $client->request('GET', sprintf('/participant/%d/%s', $contexte['participant']->getId(), $route));

        static::assertResponseRedirects('/participant/' . $contexte['participant']->getId() . '/index');
    }

    public function testAdminPasseOutreLeVerrouillage(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, verrouille: true);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));
        $client->request('GET', sprintf('/participant/%d/personnageReleve', $contexte['participant']->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testLaReleveEstModifiableApresUnPremierChoix(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client);

        $autre = PersonnageFactory::createOne(['user' => $contexte['user']]);
        $contexte['participant']->setPersonnageReleve($autre);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->request('GET', sprintf('/participant/%d/personnageReleve', $contexte['participant']->getId()));

        // L'ancienne règle « choix définitif » est remplacée par le verrouillage du groupe.
        static::assertResponseIsSuccessful();
    }

    public function testLaListeExclutLesPersonnagesDejaEngagesSurLeGn(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client);

        $libre = PersonnageFactory::createOne(['user' => $contexte['user'], 'nom' => 'Libre']);
        $pris = PersonnageFactory::createOne(['user' => $contexte['user'], 'nom' => 'DejaPris']);

        // Le personnage est engagé comme relève sur une autre participation du même GN.
        $autreParticipation = ParticipantFactory::createOne([
            'gn' => $contexte['gn'],
            'user' => $contexte['user'],
        ]);
        $autreParticipation->setPersonnageReleve($pris);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $crawler = $client->request(
            'GET',
            sprintf('/participant/%d/personnageReleve', $contexte['participant']->getId()),
        );

        static::assertResponseIsSuccessful();

        $libelles = $crawler->filter('label')->each(static fn ($node) => $node->text());
        $libelles = implode(' | ', $libelles);

        static::assertStringContainsString('Libre', $libelles);
        static::assertStringNotContainsString('DejaPris', $libelles);
        // Le personnage principal ne peut pas non plus être sa propre relève.
        static::assertStringNotContainsString('Principal', $libelles);
    }

    /**
     * @return array{user: Proxy|object, gn: Proxy|object, participant: Participant}
     */
    private function contexte(
        KernelBrowser $client,
        bool $avecBillet = true,
        bool $avecPersonnage = true,
        bool $substitutionActive = false,
        bool $verrouille = false,
    ): array {
        $gn = GnFactory::createOne(['substitutionActive' => $substitutionActive]);
        $user = UserFactory::createOne();

        $groupe = GroupeFactory::createOne(['lock' => $verrouille]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);

        $attributs = ['gn' => $gn, 'user' => $user, 'groupeGn' => $groupeGn];

        if ($avecBillet) {
            $attributs['billet'] = BilletFactory::createOne(['gn' => $gn, 'user' => $user]);
        }

        if ($avecPersonnage) {
            $attributs['personnage'] = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Principal']);
        }

        $participant = ParticipantFactory::createOne($attributs);

        $client->loginUser($user);

        return ['user' => $user, 'gn' => $gn, 'participant' => $participant];
    }
}
