<?php

declare(strict_types=1);

namespace App\Tests\Functional\FicheRetour;

use App\Entity\FicheRetourGroupeHistory;
use App\Tests\Factory\FicheRetourGroupeFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour la fiche retour de jeu de groupe.
 *
 * Couvre: accès, affichage, création, édition (motif), sécurité.
 * DAMA rollback automatique entre chaque test.
 */
#[Group('functional')]
class FicheRetourGroupeTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Accès à l'onglet fiche retour
    // -------------------------------------------------------------------------

    public function testWargameCanAccessFicheRetourTab(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_WARGAME', 'ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', sprintf('/groupe/%d/detail/fiche_retour/gn/%d/groupeGn/%d', $groupe->getId(), $gn->getId(), $groupeGn->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testUserWithoutRoleCannotAccessFicheRetourEdit(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', sprintf('/groupe/%d/detail/fiche-retour/gn/%d/groupeGn/%d/edit', $groupe->getId(), $gn->getId(), $groupeGn->getId()));

        // L'app redirige vers /access_denied au lieu de retourner 403 directement
        static::assertResponseRedirects('/access_denied');
    }

    // -------------------------------------------------------------------------
    // Création d'une fiche
    // -------------------------------------------------------------------------

    public function testWargameCanCreateFiche(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_WARGAME', 'ROLE_USER']]);

        $client->loginUser($user);
        $url = sprintf('/groupe/%d/detail/fiche-retour/gn/%d/groupeGn/%d/edit', $groupe->getId(), $gn->getId(), $groupeGn->getId());
        $crawler = $client->request('GET', $url);
        static::assertResponseIsSuccessful();

        $form = $crawler
            ->selectButton('Enregistrer')
            ->form([
                'fiche_retour_groupe[pieces_argent]' => 10,
                'fiche_retour_groupe[pieces_or]' => 5,
                'fiche_retour_groupe[nb_ingredients]' => 3,
                'fiche_retour_groupe[nb_potions]' => 1,
                'fiche_retour_groupe[armement]' => 0,
                'fiche_retour_groupe[chevaux]' => 2,
                'fiche_retour_groupe[fruits_legumes]' => 4,
                'fiche_retour_groupe[matieres_simples]' => 0,
                'fiche_retour_groupe[sel]' => 0,
                'fiche_retour_groupe[betail]' => 0,
                'fiche_retour_groupe[coton]' => 0,
                'fiche_retour_groupe[gemmes]' => 0,
                'fiche_retour_groupe[moutons]' => 0,
                'fiche_retour_groupe[soie]' => 0,
                'fiche_retour_groupe[bois]' => 0,
                'fiche_retour_groupe[esclaves]' => 0,
                'fiche_retour_groupe[ivoire]' => 0,
                'fiche_retour_groupe[pierre]' => 0,
                'fiche_retour_groupe[teinture]' => 0,
                'fiche_retour_groupe[cereales]' => 0,
                'fiche_retour_groupe[fourrures]' => 0,
                'fiche_retour_groupe[matieres_precieux]' => 0,
                'fiche_retour_groupe[poisson]' => 0,
                'fiche_retour_groupe[vin]' => 0,
                'fiche_retour_groupe[commentaire]' => 'Test création',
            ]);
        $client->submit($form);

        static::assertResponseRedirects();

        // Vérifie l'historique CREATE
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $fiche = $em->getRepository(\App\Entity\FicheRetourGroupe::class)->findOneBy(['groupeGn' => $groupeGn]);
        static::assertNotNull($fiche);
        static::assertSame(10, $fiche->getPiecesArgent());
        static::assertSame(5, $fiche->getPiecesOr());

        // Vérifie l'historique via requête directe (évite le cache EM)
        $historyRepo = $em->getRepository(FicheRetourGroupeHistory::class);
        $histories = $historyRepo->findBy(['ficheRetourGroupe' => $fiche]);
        static::assertCount(1, $histories);
        static::assertSame(FicheRetourGroupeHistory::ACTION_CREATE, $histories[0]->getActionType());
    }

    // -------------------------------------------------------------------------
    // Édition avec motif
    // -------------------------------------------------------------------------

    public function testWargameCanEditFicheWithMotif(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_WARGAME', 'ROLE_USER']]);
        FicheRetourGroupeFactory::createOne([
            'groupeGn' => $groupeGn,
            'pieces_argent' => 20,
            'createdBy' => $user,
            'updatedBy' => $user,
        ]);

        $client->loginUser($user);
        $url = sprintf('/groupe/%d/detail/fiche-retour/gn/%d/groupeGn/%d/edit', $groupe->getId(), $gn->getId(), $groupeGn->getId());
        $crawler = $client->request('GET', $url);
        static::assertResponseIsSuccessful();

        $form = $crawler
            ->selectButton('Enregistrer')
            ->form([
                'fiche_retour_groupe[pieces_argent]' => 15,
                'fiche_retour_groupe[pieces_or]' => 0,
                'fiche_retour_groupe[nb_ingredients]' => 0,
                'fiche_retour_groupe[nb_potions]' => 0,
                'fiche_retour_groupe[armement]' => 0,
                'fiche_retour_groupe[chevaux]' => 0,
                'fiche_retour_groupe[fruits_legumes]' => 0,
                'fiche_retour_groupe[matieres_simples]' => 0,
                'fiche_retour_groupe[sel]' => 0,
                'fiche_retour_groupe[betail]' => 0,
                'fiche_retour_groupe[coton]' => 0,
                'fiche_retour_groupe[gemmes]' => 0,
                'fiche_retour_groupe[moutons]' => 0,
                'fiche_retour_groupe[soie]' => 0,
                'fiche_retour_groupe[bois]' => 0,
                'fiche_retour_groupe[esclaves]' => 0,
                'fiche_retour_groupe[ivoire]' => 0,
                'fiche_retour_groupe[pierre]' => 0,
                'fiche_retour_groupe[teinture]' => 0,
                'fiche_retour_groupe[cereales]' => 0,
                'fiche_retour_groupe[fourrures]' => 0,
                'fiche_retour_groupe[matieres_precieux]' => 0,
                'fiche_retour_groupe[poisson]' => 0,
                'fiche_retour_groupe[vin]' => 0,
                'fiche_retour_groupe[commentaire]' => '',
                'fiche_retour_groupe[motif_type]' => FicheRetourGroupeHistory::ACTION_CONSOMMATION,
                'fiche_retour_groupe[motif]' => 'Consommation pour construction d\'un port',
            ]);
        $client->submit($form);

        static::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $fiche = $em->getRepository(\App\Entity\FicheRetourGroupe::class)->findOneBy(['groupeGn' => $groupeGn]);
        static::assertNotNull($fiche);
        static::assertSame(15, $fiche->getPiecesArgent());
    }

    // -------------------------------------------------------------------------
    // Historique
    // -------------------------------------------------------------------------

    public function testWargameCanAccessHistory(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_WARGAME', 'ROLE_USER']]);
        FicheRetourGroupeFactory::createOne(['groupeGn' => $groupeGn]);

        $client->loginUser($user);
        $client->request('GET', sprintf('/groupe/%d/detail/fiche-retour/gn/%d/groupeGn/%d/history', $groupe->getId(), $gn->getId(), $groupeGn->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testUserWithoutRoleCannotAccessHistory(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        FicheRetourGroupeFactory::createOne(['groupeGn' => $groupeGn]);

        $client->loginUser($user);
        $client->request('GET', sprintf('/groupe/%d/detail/fiche-retour/gn/%d/groupeGn/%d/history', $groupe->getId(), $gn->getId(), $groupeGn->getId()));

        static::assertResponseRedirects('/access_denied');
    }

    // -------------------------------------------------------------------------
    // Page import GN
    // -------------------------------------------------------------------------

    public function testWargameCanAccessImportPage(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_WARGAME', 'ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', sprintf('/gn/%d/fiche-retour/import', $gn->getId()));

        static::assertResponseIsSuccessful();
    }

    public function testUserWithoutRoleCannotAccessImportPage(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', sprintf('/gn/%d/fiche-retour/import', $gn->getId()));

        static::assertResponseRedirects('/access_denied');
    }
}
