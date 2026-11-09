<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Connaissance;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Service\DecisionsDeclaration;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\LangueFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TechnologieFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Déclaration du joueur : langues, connaissances et technologies apprises, choisies dans des listes (les secrètes ne sont pas proposées).
 */
#[Group('functional')]
class DepouillementDeclarationListesTest extends WebTestCase
{
    /**
     * @return array{0: Gn, 1: User, 2: Depouillement}
     */
    private function dossierDeJoueur(): array
    {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => $personnage]);

        $dossier = static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($gn, DepouillementType::JOUEUR, (string) $personnage->getId(), $user);

        return [$gn, $user, $dossier];
    }

    private function connaissance(string $label, bool $secret): Connaissance
    {
        $connaissance = (new Connaissance())
            ->setLabel($label)
            ->setSecret($secret);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($connaissance);
        $em->flush();

        return $connaissance;
    }

    public function testLeJoueurDeclareLanguesConnaissancesEtTechnologies(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $langue = LangueFactory::createOne(['secret' => false]);
        $technologie = TechnologieFactory::createOne(['secret' => false]);
        $connaissance = $this->connaissance('Herboristerie rare', false);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/etape/declaration');
        $form = $crawler->selectButton('Enregistrer ce que j\'ai appris')->form();
        $form->setValues([
            'contenu_joueur[langues]' => [(string) $langue->getId()],
            'contenu_joueur[connaissances]' => [(string) $connaissance->getId()],
            'contenu_joueur[technologies]' => [(string) $technologie->getId()],
            'contenu_joueur[enseignantTechnologieNumero]' => '4242',
        ]);
        $client->submit($form);

        static::assertResponseRedirects();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $recharge = $em->find(Depouillement::class, $dossier->getId());
        static::assertInstanceOf(Depouillement::class, $recharge);
        $contenu = static::getContainer()->get(DepouillementContenuService::class)->lireContenuJoueur($recharge);
        static::assertSame([$langue->getId()], array_map(static fn ($element): ?int => $element->getId(), $contenu->langues));
        static::assertSame([$connaissance->getId()], array_map(static fn ($element): ?int => $element->getId(), $contenu->connaissances));
        static::assertSame([$technologie->getId()], array_map(static fn ($element): ?int => $element->getId(), $contenu->technologies));
        static::assertSame(4242, $contenu->enseignantTechnologieNumero);

        $decisions = DecisionsDeclaration::depuis($recharge->getDonnees());
        static::assertSame(['source' => 'joueur', 'etat' => 'en_attente'], DecisionsDeclaration::lire($decisions, 'langues', (int) $langue->getId()));
        static::assertSame('en_attente', DecisionsDeclaration::lire($decisions, 'connaissances', (int) $connaissance->getId())['etat']);
        static::assertSame('en_attente', DecisionsDeclaration::lire($decisions, 'technologies', (int) $technologie->getId())['etat']);
    }

    public function testLesElementsSecretsNeSontPasProposes(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $publique = LangueFactory::createOne(['secret' => false, 'label' => 'Langue publique']);
        $secrete = LangueFactory::createOne(['secret' => true, 'label' => 'Langue secrete']);
        $connaissanceSecrete = $this->connaissance('Savoir interdit', true);
        $client->loginUser($user);

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/etape/declaration');

        static::assertSelectorExists('select[name="contenu_joueur[langues][]"] option[value="' . $publique->getId() . '"]');
        static::assertSelectorNotExists('select[name="contenu_joueur[langues][]"] option[value="' . $secrete->getId() . '"]');
        static::assertSelectorNotExists('select[name="contenu_joueur[connaissances][]"] option[value="' . $connaissanceSecrete->getId() . '"]');
    }
}
