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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * Restrictions de création de personnage (hotfix anti-abus du 2026-11-28).
 *
 * Règles couvertes :
 * - un joueur ne crée un personnage que depuis sa participation (groupe + billet) ;
 * - un joueur a au plus 3 personnages vivants, les morts ne comptent pas ;
 * - le staff (orga, scénariste) n'est pas limité et peut créer un personnage pour un user ;
 * - seul le staff crée une participation (sinon : billet gratuit et contournement du groupe) ;
 * - l'ancienne route /personnage/{x}/add n'existe plus.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class CreationPersonnageRestrictionTest extends WebTestCase
{
    /**
     * Création de participation et création depuis le compte : réservées au staff.
     */
    #[TestWith(['/participant/new/%d'])]
    #[TestWith(['/user/%d/personnage/add'])]
    public function testUnJoueurEstRefuse(string $url): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', sprintf($url, $user->getId()));

        // L'application redirige vers /access_denied au lieu de renvoyer un 403.
        static::assertResponseRedirects('/access_denied');
    }

    /**
     * Groupe + billet + moins de 3 vivants : ouvert. Les morts ne comptent pas.
     */
    #[TestWith([2, 0])]
    #[TestWith([2, 3])]
    public function testLaCreationDepuisLaParticipationEstOuverte(int $vivants, int $morts): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, vivants: $vivants, morts: $morts);

        $client->request('GET', sprintf('/participant/%d/personnageNew', $contexte['participant']->getId()));

        static::assertResponseIsSuccessful();
    }

    #[TestWith([false, true])]
    #[TestWith([true, false])]
    public function testLaCreationEstRefuseeSansGroupeOuSansBillet(bool $avecGroupe, bool $avecBillet): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, avecGroupe: $avecGroupe, avecBillet: $avecBillet);

        $client->request('GET', sprintf('/participant/%d/personnageNew', $contexte['participant']->getId()));

        static::assertResponseRedirects('/gn/' . $contexte['gn']->getId());
    }

    #[TestWith(['ROLE_ORGA'])]
    #[TestWith(['ROLE_SCENARISTE'])]
    #[TestWith(['ROLE_ADMIN'])]
    public function testLeStaffPeutCreerUneParticipation(string $role): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => [$role]]));

        $client->request('GET', sprintf('/participant/new/%d', $joueur->getId()));

        static::assertResponseIsSuccessful();
    }

    #[TestWith(['ROLE_ORGA'])]
    #[TestWith(['ROLE_SCENARISTE'])]
    public function testLeStaffPeutCreerUnPersonnagePourUnJoueurSansLimite(string $role): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        PersonnageFactory::createMany(3, ['user' => $joueur]);
        $client->loginUser(UserFactory::createOne(['roles' => [$role]]));

        $client->request('GET', sprintf('/user/%d/personnage/add', $joueur->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testUnQuatriemePersonnageVivantEstRefuse(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, vivants: 3);

        $client->request('GET', sprintf('/participant/%d/personnageNew', $contexte['participant']->getId()));

        static::assertResponseRedirects('/gn/' . $contexte['gn']->getId());
    }

    public function testLeStaffDepasseLaLimitePourUnJoueur(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte($client, vivants: 3);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));

        $client->request('GET', sprintf('/participant/%d/personnageNew', $contexte['participant']->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testLAncienneRouteDeCreationNExistePlus(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));
        $personnage = PersonnageFactory::createOne();

        $client->request('GET', sprintf('/personnage/%d/add', $personnage->getId()));

        static::assertResponseStatusCodeSame(404);
    }

    /**
     * @return array{user: Proxy|object, gn: Proxy|object, participant: Participant}
     */
    private function contexte(
        KernelBrowser $client,
        bool $avecGroupe = true,
        bool $avecBillet = true,
        int $vivants = 0,
        int $morts = 0,
    ): array {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne();

        $attributs = ['gn' => $gn, 'user' => $user];

        if ($avecGroupe) {
            $groupe = GroupeFactory::createOne(['lock' => false]);
            $attributs['groupeGn'] = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        }

        if ($avecBillet) {
            $attributs['billet'] = BilletFactory::createOne(['gn' => $gn, 'user' => $user]);
        }

        if ($vivants > 0) {
            PersonnageFactory::createMany($vivants, ['user' => $user, 'vivant' => true]);
        }

        if ($morts > 0) {
            PersonnageFactory::createMany($morts, ['user' => $user, 'vivant' => false]);
        }

        $participant = ParticipantFactory::createOne($attributs);

        $client->loginUser($user);

        return ['user' => $user, 'gn' => $gn, 'participant' => $participant];
    }
}
