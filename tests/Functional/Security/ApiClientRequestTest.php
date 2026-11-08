<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\ApiClientRequest;
use App\Entity\Message;
use App\Repository\ApiClientRepository;
use App\Repository\ApiClientRequestRepository;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ApiClientRequestTest extends WebTestCase
{
    public function testAnonymeEstRedirige(): void
    {
        $client = static::createClient();

        $client->request('GET', '/applications-api/demande');

        static::assertResponseRedirects();
    }

    public function testUtilisateurFaitUneDemandeEtLesAdminsSontPrevenus(): void
    {
        $client = static::createClient();
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $this->demander($client, 'app-comptoir');

        static::assertResponseRedirects('/applications-api/demande');
        $demande = static::getContainer()->get(ApiClientRequestRepository::class)->findOneBy(['name' => 'app-comptoir']);
        static::assertNotNull($demande);
        static::assertTrue($demande->isPending());
        $messages = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Message::class)->findBy(['userRelatedByDestinataire' => $admin->getId()]);
        static::assertNotEmpty($messages);
    }

    public function testUtilisateurSimpleNaPasAccesAuTraitement(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', '/admin/api-clients/demandes');

        static::assertResponseRedirects('/access_denied');
    }

    public function testAdminAccepteLaDemande(): void
    {
        $client = static::createClient();
        $demandeur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($demandeur);
        $this->demander($client, 'app-acceptee');

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $crawler = $client->request('GET', '/admin/api-clients/demandes');
        $client->submit($crawler->filter('form[action$="/accept"]')->form());

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('client_secret', (string) $client->getResponse()->getContent());
        $app = static::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-acceptee']);
        static::assertNotNull($app);
        static::assertContains('ROLE_API_READ', $app->getUser()->getRoles());
        $demande = static::getContainer()->get(ApiClientRequestRepository::class)->findOneBy(['name' => 'app-acceptee']);
        static::assertSame(ApiClientRequest::ACCEPTEE, $demande->getStatus());
    }

    public function testAdminRefuseLaDemande(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        $this->demander($client, 'app-refusee');

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $crawler = $client->request('GET', '/admin/api-clients/demandes');
        $form = $crawler->filter('form[action$="/refuse"]')->form(['note' => 'Pas ce GN']);
        $client->submit($form);

        static::assertResponseRedirects('/admin/api-clients/demandes');
        static::assertNull(static::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-refusee']));
        $demande = static::getContainer()->get(ApiClientRequestRepository::class)->findOneBy(['name' => 'app-refusee']);
        static::assertSame(ApiClientRequest::REFUSEE, $demande->getStatus());
        static::assertSame('Pas ce GN', $demande->getDecisionNote());
    }

    private function demander(KernelBrowser $client, string $name): void
    {
        $crawler = $client->request('GET', '/applications-api/demande');
        $token = $crawler->filter('form[action$="/applications-api/demande"] input[name="_token"]')->attr('value');
        $client->request('POST', '/applications-api/demande', [
            '_token' => $token,
            'name' => $name,
            'purpose' => 'Afficher les personnages de chaque groupe au comptoir.',
            'gn' => '',
        ]);
    }
}
