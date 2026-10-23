<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Tests\Factory\GenresDeReference;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Conceptions d'héritier dans l'étape Contenu : formulaire, résumé, retrait, garde-fous.
 */
#[Group('functional')]
class DepouillementConceptionTest extends WebTestCase
{
    /**
     * @return array{0: Depouillement, 1: int, 2: User}
     */
    private function scene(): array
    {
        $moi = UserFactory::createOne(['username' => 'moi', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        $gn = GnFactory::createOne();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $parent = PersonnageFactory::createOne(['genre' => GenresDeReference::genre($em, 1)]);
        $partenaire = PersonnageFactory::createOne(['genre' => GenresDeReference::genre($em, 2)]);
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $parent]);
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $partenaire]);

        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $parent->getId())
            ->setParticipant($em->getRepository(Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $parent]))
            ->setSimulation(true)
            ->setDepouilleur($moi);
        $em->persist($depouillement);
        $em->flush();

        return [$depouillement, $partenaire->getId(), $moi];
    }

    private function contenu(Depouillement $depouillement): string
    {
        return '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/etape/conceptions';
    }

    public function testEnregistrerUneConceptionPuisLaRetirer(): void
    {
        $client = static::createClient();
        [$depouillement, $numeroPartenaire, $moi] = $this->scene();
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        static::assertSelectorTextContains('body', 'Aucune conception signalée');
        $client->submit($crawler->selectButton('Enregistrer la conception')->form(['partenaire' => (string) $numeroPartenaire]));
        static::assertResponseRedirects();
        $crawler = $client->followRedirect();

        static::assertSelectorTextContains('body', 'Tirage :');
        static::assertCount(1, static::getContainer()->get(EntityManagerInterface::class)->getRepository(Conception::class)->findAll());

        $client->submit($crawler->selectButton('Retirer')->form());
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'Aucune conception signalée');
    }

    public function testLaNoteComplementaireDesJoueursEstEnregistreeEtAffichee(): void
    {
        $client = static::createClient();
        [$depouillement, $numeroPartenaire, $moi] = $this->scene();
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $client->submit($crawler->selectButton('Enregistrer la conception')->form(['partenaire' => (string) $numeroPartenaire, 'note' => '  Souhaite une fille  ']));
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Note des joueurs : Souhaite une fille');
        $conception = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Conception::class)->findOneBy([]);
        static::assertSame('Souhaite une fille', $conception?->getNote());
    }

    public function testUnPartenaireInconnuAfficheUneErreur(): void
    {
        $client = static::createClient();
        [$depouillement, , $moi] = $this->scene();
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $client->submit($crawler->selectButton('Enregistrer la conception')->form(['partenaire' => '99999999']));
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Aucun personnage');
        static::assertSame([], static::getContainer()->get(EntityManagerInterface::class)->getRepository(Conception::class)->findAll());
    }

    public function testSansJetonCsrfLaConceptionEstRefusee(): void
    {
        $client = static::createClient();
        [$depouillement, $numeroPartenaire, $moi] = $this->scene();
        $client->loginUser($moi);

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/conceptions', ['partenaire' => (string) $numeroPartenaire]);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Jeton de sécurité invalide');
        static::assertSame([], static::getContainer()->get(EntityManagerInterface::class)->getRepository(Conception::class)->findAll());
    }
}
