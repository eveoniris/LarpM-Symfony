<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\Potion;
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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Choix de potion via un trigger alchimie : propriétaire seulement, mêmes règles que le site.
 */
#[Group('functional')]
class ApiJoueurPotionTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/joueur/personnage/1/potions/1');
        static::assertSame(401, $client->getResponse()->getStatusCode());
        $client->request('POST', '/api/joueur/personnage/1/potions/1');
        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testListeEtChoixConsommentLeTrigger(): void
    {
        $client = static::createClient();
        [$user, $personnage, $potion] = $this->scenario(1);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/potions/1', [], [], $auth);
        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{potions: list<array{id: int}>} $liste */
        $liste = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertContains($potion->getId(), array_column($liste['potions'], 'id'));

        $this->choisir($client, $personnage, 1, $potion->getId(), $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $frais = $em->find(Personnage::class, $personnage->getId());
        static::assertTrue($frais->isKnownPotion($em->find(Potion::class, $potion->getId())));
        static::assertFalse($frais->hasTrigger(TriggerType::ALCHIMIE_APPRENTI));

        // Plus de choix en attente : refusé.
        $this->choisir($client, $personnage, 1, $potion->getId(), $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
        static::assertStringContainsString('aucun_choix', (string) $client->getResponse()->getContent());
    }

    public function testSansTriggerDeCeNiveauRefuse(): void
    {
        $client = static::createClient();
        [$user, $personnage, $potion] = $this->scenario(1);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $this->choisir($client, $personnage, 2, $potion->getId(), $auth);

        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testPotionDunAutreNiveauOuInconnue(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(1);
        $autreNiveau = $this->potion(2);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $this->choisir($client, $personnage, 1, $autreNiveau->getId(), $auth);
        static::assertSame(422, $client->getResponse()->getStatusCode());

        $this->choisir($client, $personnage, 1, 999999999, $auth);
        static::assertSame(404, $client->getResponse()->getStatusCode());

        $client->request('POST', '/api/joueur/personnage/' . $personnage->getId() . '/potions/1', [], [], $auth + ['CONTENT_TYPE' => 'application/json'], '{}');
        static::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testPersonnageDunAutreRefuse(): void
    {
        $client = static::createClient();
        [, $personnage, $potion] = $this->scenario(1);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton(UserFactory::createOne())];

        $client->request('GET', '/api/joueur/personnage/' . $personnage->getId() . '/potions/1', [], [], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
        $this->choisir($client, $personnage, 1, $potion->getId(), $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testGroupeVerrouilleRefuse(): void
    {
        $client = static::createClient();
        [$user, $personnage, $potion] = $this->scenario(1);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['lock' => true]), 'gn' => GnFactory::createOne()]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $groupeGn->getGn(), 'groupeGn' => $groupeGn, 'personnage' => $personnage]);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];

        $this->choisir($client, $personnage, 1, $potion->getId(), $auth);

        static::assertSame(403, $client->getResponse()->getStatusCode());
        static::assertStringContainsString('groupe_verrouille', (string) $client->getResponse()->getContent());
    }

    /**
     * @return array{0: User, 1: Personnage, 2: Potion}
     */
    private function scenario(int $niveau): array
    {
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setTag(TriggerType::ALCHIMIE_APPRENTI->value);
        $trigger->setDone(false);
        $em->persist($trigger);
        $em->flush();
        $personnage->addPersonnageTrigger($trigger);

        return [$user, $personnage, $this->potion($niveau)];
    }

    private function potion(int $niveau): Potion
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $potion = new Potion();
        $potion->setLabel('Potion test ' . $niveau);
        $potion->setNiveau($niveau);
        $potion->setNumero('T' . $niveau);
        $potion->setDocumentUrl('');
        $em->persist($potion);
        $em->flush();

        return $potion;
    }

    /** @param array<string, string> $auth */
    private function choisir(KernelBrowser $client, Personnage $personnage, int $niveau, int $potionId, array $auth): void
    {
        $client->request(
            'POST',
            '/api/joueur/personnage/' . $personnage->getId() . '/potions/' . $niveau,
            [],
            [],
            $auth + ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['potion_id' => $potionId]),
        );
    }

    private function jeton(User $user): string
    {
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
