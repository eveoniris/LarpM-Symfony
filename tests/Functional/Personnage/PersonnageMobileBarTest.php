<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Fiche personnage sur mobile : barre « Onglet » (gauche) et « Raccourcis » (droite) à la place de la rangée de boutons.
 * Valable pour tous les comptes, avec ou sans interface bêta.
 */
#[Group('functional')]
class PersonnageMobileBarTest extends WebTestCase
{
    public function testBarreMobileEtPanneauRaccourcisPourUnJoueurSansBeta(): void
    {
        $client = static::createClient();
        EspeceFactory::createOne(['nom' => 'Humain']);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseIsSuccessful();
        $bar = $crawler->filter('.personnage-mobile-bar.d-md-none');
        static::assertCount(1, $bar);
        static::assertStringContainsString('Onglet :', $bar->text());
        static::assertStringContainsString('Raccourcis', $bar->text());
        // la rangée de boutons d'origine n'est plus visible sur mobile
        static::assertCount(1, $crawler->filter('.personnage-actions.d-none.d-md-block'));
        // le panneau reprend les actions principales, avec libellés complets
        $panel = $crawler->filter('#offcanvasPersonnageActions');
        static::assertStringContainsString('Acheter une compétence', $panel->text());
        static::assertStringContainsString('Choisir une nouvelle religion', $panel->text());
        static::assertStringContainsString('Imprimer avec QR Code', $panel->text());
    }

    public function testMemeBarreEnInterfaceBeta(): void
    {
        $client = static::createClient();
        EspeceFactory::createOne(['nom' => 'Humain']);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER'], 'uiTheme' => 'beta']);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.personnage-mobile-bar .personnage-actions-btn'));
    }
}
