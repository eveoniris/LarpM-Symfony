<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Gn;
use App\Entity\Rarete;
use App\Entity\Ressource;
use App\Service\Api\ApiClientManager;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * /api/admin/groupes/personnages et /api/admin/ressources : lecture seule pour ROLE_API_READ.
 */
#[Group('functional')]
class ApiAdminPersonnagesRessourcesTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        foreach (['/api/admin/groupes/personnages', '/api/admin/ressources'] as $url) {
            $client->request('GET', $url);
            static::assertSame(401, $client->getResponse()->getStatusCode(), $url);
        }
    }

    public function testPersonnagesParGroupeSansDonneesJoueur(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne(['numero' => 9301, 'nom' => 'Les Loups']);
        $vide = GroupeFactory::createOne(['numero' => 9302, 'nom' => 'Sans personnage']);
        $autre = GroupeFactory::createOne(['numero' => 9303, 'nom' => 'Autre GN']);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        GroupeGnFactory::createOne(['groupe' => $vide, 'gn' => $gn]);
        $autreGroupeGn = GroupeGnFactory::createOne(['groupe' => $autre, 'gn' => $autreGn]);
        $zed = PersonnageFactory::createOne(['nom' => 'Zed']);
        $ayla = PersonnageFactory::createOne(['nom' => 'Ayla']);
        $hors = PersonnageFactory::createOne(['nom' => 'Hors GN']);
        ParticipantFactory::createOne(['user' => UserFactory::createOne(), 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => $zed]);
        ParticipantFactory::createOne(['user' => UserFactory::createOne(), 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => $ayla]);
        ParticipantFactory::createOne(['user' => UserFactory::createOne(), 'gn' => $autreGn, 'groupeGn' => $autreGroupeGn, 'personnage' => $hors]);
        $token = $this->jeton($client, 'app-perso', $gn);

        $client->request('GET', '/api/admin/groupes/personnages', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        $contenu = (string) $client->getResponse()->getContent();
        /** @var array{gn: array{id: int}, items: list<array{numero: int, personnages: list<array{id: int, nom: string}>}>} $data */
        $data = json_decode($contenu, true);
        static::assertSame($gn->getId(), $data['gn']['id']);
        static::assertSame([9301, 9302], array_column($data['items'], 'numero'));
        static::assertSame(['Ayla', 'Zed'], array_column($data['items'][0]['personnages'], 'nom'));
        static::assertSame([], $data['items'][1]['personnages']);
        static::assertStringNotContainsString('Hors GN', $contenu);
        static::assertStringNotContainsString('email', $contenu);
        static::assertStringNotContainsString('user', $contenu);

        // Un autre GN reste hors du périmètre de l'application.
        $client->request('GET', '/api/admin/groupes/personnages?gn=' . $autreGn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testRessourcesCommercialesAvecRarete(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $rarete = (new Rarete())
            ->setLabel('Rare test')
            ->setValue(3);
        $em->persist($rarete);
        $ressource = (new Ressource())
            ->setLabel('Ressource API test')
            ->setRarete($rarete);
        $em->persist($ressource);
        $em->flush();
        $token = $this->jeton($client, 'app-ressources', null);

        $client->request('GET', '/api/admin/ressources', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{items: list<array{label: string, rarete: array{label: string, value: int}}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $trouvees = array_values(array_filter($data['items'], static fn (array $i): bool => 'Ressource API test' === $i['label']));
        static::assertCount(1, $trouvees);
        static::assertSame('Rare test', $trouvees[0]['rarete']['label']);
        static::assertSame(3, $trouvees[0]['rarete']['value']);
    }

    private function jeton(KernelBrowser $client, string $name, ?Gn $gn): string
    {
        ['client' => $app, 'secret' => $secret] = static::getContainer()->get(ApiClientManager::class)->create($name, ['ROLE_API_READ'], $gn);
        $client->request('POST', '/api/client/token', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['client_id' => $app->getClientId(), 'client_secret' => $secret]));
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data['token'];
    }
}
