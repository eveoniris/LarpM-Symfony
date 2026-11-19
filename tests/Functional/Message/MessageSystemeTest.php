<?php

declare(strict_types=1);

namespace App\Tests\Functional\Message;

use App\Entity\Message;
use App\Service\MailService;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class MessageSystemeTest extends WebTestCase
{
    public function testMessageSystemeSansAuteurEstAffichePuisIrrepondable(): void
    {
        $client = static::createClient();
        $destinataire = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        static::getContainer()->get(MailService::class)->newSystemMessage($destinataire, 'Votre XP a fait l\'objet d\'un ajout.', 'Ajout XP');

        $message = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Message::class)
            ->findOneBy(['title' => 'Ajout XP']);
        static::assertNotNull($message);
        static::assertTrue($message->isSystem());
        static::assertSame('Équipe de gestion', $message->getAuteurLabel());

        // L'utilisateur de la factory garde une collection de messages vide en mémoire : on repart de la base.
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $client->loginUser($destinataire);
        $client->request('GET', '/messagerie');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', 'Équipe de gestion');

        $client->request('GET', '/messagerie/' . $message->getId() . '/response');
        static::assertResponseRedirects('/messagerie');
    }

    public function testReponseInterditeAuNonDestinataire(): void
    {
        $client = static::createClient();
        $auteur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $destinataire = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $intrus = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        static::getContainer()->get(MailService::class)->newMessage($destinataire, 'Texte', 'Sujet privé', $auteur);
        $message = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Message::class)
            ->findOneBy(['title' => 'Sujet privé']);

        $client->loginUser($intrus);
        $client->request('GET', '/messagerie/' . $message->getId() . '/response');

        static::assertResponseRedirects('/access_denied');
    }
}
