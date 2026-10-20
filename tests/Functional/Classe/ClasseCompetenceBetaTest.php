<?php

declare(strict_types=1);

namespace App\Tests\Functional\Classe;

use App\Entity\Level;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Pages /classe et /competence en interface bêta (filtre par type, matrice, grille par famille).
 * DAMA enveloppe chaque test dans une transaction annulée à la fin.
 */
#[Group('functional')]
class ClasseCompetenceBetaTest extends WebTestCase
{
    private function creerClasse(string $label, bool $creation = true): object
    {
        return ClasseFactory::createOne(['label_masculin' => $label, 'label_feminin' => $label, 'creation' => $creation]);
    }

    public function testFiltreParTypeEnBeta(): void
    {
        $client = static::createClient();
        $this->creerClasse('Chevalier');
        $this->creerClasse('Espion');
        $this->creerClasse('Prêtre');
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']));

        $crawler = $client->request('GET', '/classe?type=social');

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('article.lm-classe'));
        static::assertStringContainsString('Espion', $crawler->filter('article.lm-classe')->text());
        // les colonnes de la matrice (onglet Affinités) suivent le filtre
        $affinites = $client->request('GET', '/classe?type=social&vue=affinites');
        static::assertCount(1, $affinites->filter('th.lm-matrice-classe'));
        static::assertCount(0, $affinites->filter('article.lm-classe'));
        // les trois types restent proposés avec leur effectif
        static::assertCount(3, $crawler->filter('nav.lm-filtre-type a.lm-type'));
    }

    public function testSansFiltreToutesLesClassesEnBeta(): void
    {
        $client = static::createClient();
        $this->creerClasse('Chevalier');
        $this->creerClasse('Espion');
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']));

        $crawler = $client->request('GET', '/classe');

        static::assertResponseIsSuccessful();
        static::assertCount(2, $crawler->filter('article.lm-classe'));
    }

    public function testMatriceAffichesAffinites(): void
    {
        $client = static::createClient();
        $famille = CompetenceFamilyFactory::createOne(['label' => 'Herboristerie']);
        $classe = $this->creerClasse('Rebouteux');
        $classe->addCompetenceFamilyFavorite($famille);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']));
        CompetenceFactory::createOne(['competenceFamily' => $famille, 'level' => LevelFactory::createOne(['index' => 1, 'label' => 'Apprenti']), 'secret' => false]);

        $crawler = $client->request('GET', '/classe?vue=affinites');

        static::assertResponseIsSuccessful();
        static::assertGreaterThanOrEqual(1, $crawler->filter('.lm-matrice td[data-role="ok"] .lm-aff[data-role="ok"]')->count());
        // la vue par défaut (onglet Classes) ne contient pas la matrice
        static::assertCount(0, $client->request('GET', '/classe')->filter('.lm-matrice'));
    }

    public function testHorsBetaHtmlInchange(): void
    {
        $client = static::createClient();
        $this->creerClasse('Chevalier');
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $crawler = $client->request('GET', '/classe?type=social');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.lm-matrice, .lm-classe, .lm-filtre-type'));
        // le filtre n'est pas appliqué hors bêta
        static::assertStringContainsString('Chevalier', $crawler->text());
    }

    public function testGrilleCompetencesBetaMontreLesCoutsDesQuatreNiveauxMaisPasLesDescriptionsAuxJoueurs(): void
    {
        $client = static::createClient();
        $famille = CompetenceFamilyFactory::createOne(['label' => 'Forge']);
        // La visibilité joueur filtre sur l'identifiant du niveau Apprenti (1) : niveaux de référence insérés avec leur id.
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $connection->executeStatement('DELETE FROM level WHERE id IN (1, 2)');
        $connection->executeStatement(
            "INSERT INTO level (id, label, `index`, cout, cout_favori, cout_meconu, discr) VALUES (1, 'Apprenti', 1, 2, 1, 3, 'extended'), (2, 'Initié', 2, 4,  3, 5, 'extended')",
        );
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $apprenti = $entityManager->find(Level::class, 1);
        $initie = $entityManager->find(Level::class, 2);
        CompetenceFactory::createOne(['competenceFamily' => $famille, 'level' => $apprenti, 'secret' => false, 'description' => 'Description apprenti visible']);
        CompetenceFactory::createOne(['competenceFamily' => $famille, 'level' => $initie, 'secret' => false, 'description' => 'Description initie reservee']);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']));

        $crawler = $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('article.lm-famille'));
        static::assertGreaterThanOrEqual(2, $crawler->filter('li.lm-niveau')->count());
        static::assertStringContainsString('4 xp', $crawler->filter('article.lm-famille')->text());
        static::assertStringContainsString('Description apprenti visible', $crawler->text());
        static::assertStringNotContainsString('Description initie reservee', $crawler->text());
    }

    public function testListeCompetencesHorsBetaSansGrille(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $crawler = $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.lm-famille, .lm-familles'));
    }

    public function testDescriptionLongueRepliableAvecLireLaSuite(): void
    {
        $client = static::createClient();
        ClasseFactory::createOne(['label_masculin' => 'Chevalier', 'label_feminin' => 'Chevaleresse', 'creation' => true, 'description' => str_repeat('Une description longue. ', 11)]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']));

        $crawler = $client->request('GET', '/classe');

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('article.lm-classe a[data-gs-toggle]'));
    }
}
