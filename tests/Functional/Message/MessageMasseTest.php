<?php

declare(strict_types=1);

namespace App\Tests\Functional\Message;

use App\Entity\Message;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class MessageMasseTest extends WebTestCase
{
    public function testNonAdminRefuse(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        $client->request('GET', '/admin/messages-masse');

        static::assertResponseRedirects('/access_denied');
    }

    public function testUnMessageParJoueurAvecPlusieursPersonnagesEtRapportDesEchecs(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $p1 = PersonnageFactory::createOne(['user' => $joueur, 'nom' => 'Conan']);
        $p2 = PersonnageFactory::createOne(['user' => $joueur, 'nom' => 'Valeria']);
        $orphelin = PersonnageFactory::createOne(['user' => null]);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]);

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/admin/messages-masse');
        static::assertResponseIsSuccessful();

        $donnees = [
            'type' => 'personnage',
            'ids' => \sprintf("%d\n%d,%d, 999999", $p1->getId(), $p2->getId(), $orphelin->getId()),
            'titre' => 'Test masse',
            'texte' => '<p>Bonjour {personnages}</p>',
            'auteur' => 'systeme',
            'notify' => '1',
            '_token' => $crawler->filter('input[name=_token]')->attr('value'),
        ];

        $client->request('POST', '/admin/messages-masse', $donnees + ['action' => 'simuler']);
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', '1 joueur(s) recevraient le message');
        static::assertSelectorTextContains('body', (string) $orphelin->getId());
        static::assertSelectorTextContains('body', '999999');

        $client->request('POST', '/admin/messages-masse', $donnees + ['action' => 'envoyer']);
        static::assertResponseRedirects('/admin/messages-masse');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $messages = $em->getRepository(Message::class)->findBy(['title' => 'Test masse']);
        static::assertCount(1, $messages);
        static::assertTrue($messages[0]->isSystem());
        static::assertStringContainsString('Conan', $messages[0]->getText());
        static::assertStringContainsString('Valeria', $messages[0]->getText());
        static::assertStringContainsString('n° ' . $p1->getId(), $messages[0]->getText());
    }

    public function testEnvoiParIdentifiantsUtilisateurEnMonNom(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]);

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/admin/messages-masse');
        $client->request('POST', '/admin/messages-masse', [
            'type' => 'user',
            'ids' => (string) $cible->getId(),
            'titre' => 'Par user',
            'texte' => '<p>Salut</p>',
            'auteur' => 'moi',
            '_token' => $crawler->filter('input[name=_token]')->attr('value'),
            'action' => 'envoyer',
        ]);

        $message = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Message::class)->findOneBy(['title' => 'Par user']);
        static::assertNotNull($message);
        static::assertFalse($message->isSystem());
        static::assertSame($admin->getId(), $message->getUserRelatedByAuteur()->getId());
    }
}
