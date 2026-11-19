<?php

declare(strict_types=1);

namespace App\Tests\Functional\Messenger;

use App\Service\MessengerSurveillance;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Mime\Email;

#[Group('functional')]
class MessengerSurveillanceTest extends WebTestCase
{
    private function mettreEnEchec(): void
    {
        $failed = static::getContainer()->get('messenger.transport.failed');
        $email = (new Email())
            ->from('a@example.org')
            ->to('joueur@example.org')
            ->subject('Sujet')
            ->text('Corps');
        $failed->send(new Envelope(new SendEmailMessage($email), [new ErrorDetailsStamp(\RuntimeException::class, 0, 'SMTP indisponible')]));
    }

    public function testListeRejeuEtSuppression(): void
    {
        static::bootKernel();
        $surveillance = static::getContainer()->get(MessengerSurveillance::class);
        $avant = $surveillance->nombreEchecs();

        $this->mettreEnEchec();
        $this->mettreEnEchec();
        static::assertSame($avant + 2, $surveillance->nombreEchecs());

        $echecs = $surveillance->echecs();
        $dernier = end($echecs);
        static::assertSame(['joueur@example.org'], $dernier->destinataires);
        static::assertSame('SMTP indisponible', $dernier->erreur);

        static::assertTrue($surveillance->supprimer($dernier->id));
        static::assertSame($avant + 1, $surveillance->nombreEchecs());

        $premier = $surveillance->echecs()[array_key_last($surveillance->echecs())];
        static::assertTrue($surveillance->rejouer($premier->id));
        static::assertSame($avant, $surveillance->nombreEchecs());
        static::assertFalse($surveillance->supprimer($premier->id));
    }

    public function testPageAdminEtAccesRefuse(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        $client->request('GET', '/admin/messenger');
        static::assertResponseRedirects('/access_denied');

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $client->request('GET', '/admin/messenger');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', 'En échec');
    }

    public function testAlerteMailSeulementSiEchecsEtUneFoisParJour(): void
    {
        static::bootKernel();
        UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'], 'email' => 'super@example.org']);
        $application = new Application(static::$kernel);
        $commande = new CommandTester($application->find('app:messenger:alerte'));

        $commande->execute([]);
        static::assertStringContainsString('File saine', $commande->getDisplay());

        $this->mettreEnEchec();
        $commande->execute([]);
        static::assertStringContainsString('Alerte envoyée', $commande->getDisplay());
        static::assertEmailCount(1);

        $commande->execute([]);
        static::assertStringContainsString('déjà envoyée', $commande->getDisplay());
        static::assertEmailCount(1);
    }
}
