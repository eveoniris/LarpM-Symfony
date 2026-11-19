<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\LogAction;
use App\Enum\LogActionType;
use App\Security\AccesRefuseJournal;
use App\Service\MailService;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class AccesRefuseTest extends WebTestCase
{
    private function fichier(): string
    {
        return static::getContainer()->getParameter('kernel.logs_dir') . '/' . AccesRefuseJournal::FILE_NAME;
    }

    public function testRefusSurIdentifiantEstTraceEnFichierEtEnBase(): void
    {
        $client = static::createClient();
        $auteur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $destinataire = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $intrus = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        static::getContainer()->get(MailService::class)->newMessage($destinataire, 'Texte', 'Sujet no access', $auteur);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $message = $em->getRepository(\App\Entity\Message::class)->findOneBy(['title' => 'Sujet no access']);
        $url = '/messagerie/' . $message->getId() . '/response';

        $client->loginUser($intrus);
        $client->request('GET', $url);

        static::assertResponseRedirects('/access_denied');
        static::assertStringContainsString($url, (string) file_get_contents($this->fichier()));
        $logs = $em->getRepository(LogAction::class)->findBy(['type' => LogActionType::NO_ACCESS->value]);
        static::assertNotEmpty($logs);
        static::assertSame($intrus->getId(), $logs[0]->getUser()->getId());
    }

    public function testRefusSansIdentifiantSeulementEnFichier(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        $client->request('GET', '/admin/messages-masse');

        static::assertResponseRedirects('/access_denied');
        static::assertStringContainsString('/admin/messages-masse', (string) file_get_contents($this->fichier()));
        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertSame([], $em->getRepository(LogAction::class)->findBy(['type' => LogActionType::NO_ACCESS->value]));
    }
}
