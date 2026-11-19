<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\ExperienceGain;
use App\Entity\Message;
use App\Entity\Personnage;
use App\Entity\PersonnageBackground;
use App\Entity\PersonnageChronologie;
use App\Entity\RenommeHistory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class AjoutMasseTest extends WebTestCase
{
    public function testNonAdminRefuse(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ORGA']]));
        $client->request('GET', '/admin/masse');

        static::assertResponseRedirects('/access_denied');
    }

    public function testXpVivantsEtMortsEtMessage(): void
    {
        $client = static::createClient();
        GnFactory::createOne(['actif' => true]);
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $vivant = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10, 'vivant' => true]);
        $mortCoche = PersonnageFactory::createOne(['xp' => 5, 'vivant' => false]);
        $mortNonCoche = PersonnageFactory::createOne(['xp' => 5, 'vivant' => false, 'user' => $joueur]);

        $reponse = $this->post($client, [
            'type' => 'xp',
            'valeur' => '3',
            'motif' => 'Récompense test',
            'date' => '2026-11-01',
            'ids' => \sprintf('%d,%d,%d,987654', $vivant->getId(), $mortCoche->getId(), $mortNonCoche->getId()),
            'prevenir' => '1',
            'texte' => '<p>XP {valeur} pour {motif} {personnages}</p>',
            'morts' => [$mortCoche->getId()],
        ]);
        static::assertResponseRedirects('/admin/masse');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertSame(13, $em->find(Personnage::class, $vivant->getId())->getXp());
        static::assertSame(8, $em->find(Personnage::class, $mortCoche->getId())->getXp());
        static::assertSame(5, $em->find(Personnage::class, $mortNonCoche->getId())->getXp());

        $gains = $em->getRepository(ExperienceGain::class)->findBy(['explanation' => 'Récompense test']);
        static::assertCount(2, $gains);
        static::assertSame('2026-11-01', $gains[0]->getOperationDate()->format('Y-m-d'));

        $mentions = $em->getRepository(PersonnageBackground::class)->findBy(['personnage' => $mortNonCoche->getId()]);
        static::assertCount(1, $mentions);
        static::assertSame('PRIVATE', $mentions[0]->getVisibility());

        // Un message d'ajout (joueur du vivant) et un message de mention (joueur du mort non coché) : même joueur, deux messages.
        $messages = $em->getRepository(Message::class)->findBy(['userRelatedByDestinataire' => $joueur->getId()]);
        static::assertCount(2, $messages);
        static::assertTrue($messages[0]->isSystem());
        unset($reponse);
    }

    public function testRenommeEtChronologieSurMort(): void
    {
        $client = static::createClient();
        $mort = PersonnageFactory::createOne(['vivant' => false]);

        $this->post($client, ['type' => 'renomme', 'valeur' => '2', 'motif' => 'Hommage', 'ids' => (string) $mort->getId()]);
        $this->post($client, ['type' => 'chronologie', 'valeur' => '1450', 'motif' => 'Tombé au combat', 'ids' => (string) $mort->getId()]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertCount(1, $em->getRepository(RenommeHistory::class)->findBy(['explication' => 'Hommage']));
        static::assertSame(2, $em->find(Personnage::class, $mort->getId())->getRenomme());
        $chrono = $em->getRepository(PersonnageChronologie::class)->findOneBy(['evenement' => 'Tombé au combat']);
        static::assertSame(1450, $chrono->getAnnee());
    }

    public function testValeurNulleRefusee(): void
    {
        $client = static::createClient();
        $p = PersonnageFactory::createOne();

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $crawler = $client->request('GET', '/admin/masse');
        $client->request('POST', '/admin/masse', [
            'type' => 'xp',
            'valeur' => '0',
            'motif' => 'x',
            'ids' => (string) $p->getId(),
            'action' => 'appliquer',
            '_token' => $crawler->filter('input[name=_token]')->attr('value'),
        ]);

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('.alert-danger', 'entier non nul');
    }

    /** @param array<string, mixed> $donnees */
    private function post(KernelBrowser $client, array $donnees): KernelBrowser
    {
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $crawler = $client->request('GET', '/admin/masse');
        $client->request(
            'POST',
            '/admin/masse',
            $donnees
            + [
                'date' => '2026-11-01',
                'action' => 'appliquer',
                '_token' => $crawler->filter('input[name=_token]')->attr('value'),
            ],
        );

        return $client;
    }
}
