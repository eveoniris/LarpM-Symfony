<?php

declare(strict_types=1);

namespace App\Tests\Functional\Ressource;

use App\Entity\Ressource;
use App\Entity\Rarete;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Liste des ressources : recherche et pagination (comme les autres listes).
 */
#[Group('functional')]
class RessourceListTest extends WebTestCase
{
    private function creer(string $label, Rarete $rarete): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $ressource = new Ressource();
        $ressource->setLabel($label)->setRarete($rarete);
        $entityManager->persist($ressource);
        $entityManager->flush();
    }

    public function testRechercheParLibelle(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $rarete = new Rarete();
        $rarete->setLabel('Commun')->setValue(1);
        $entityManager->persist($rarete);
        $entityManager->flush();
        $this->creer('Armement', $rarete);
        $this->creer('Bois', $rarete);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN'], 'uiTheme' => 'beta']));

        $tout = $client->request('GET', '/ressource');
        static::assertResponseIsSuccessful();
        static::assertCount(2, $tout->filter('li.list-group-item'));
        // le champ de recherche est présent
        static::assertGreaterThanOrEqual(1, $tout->filter('input[name="list_find[value]"]')->count());

        $filtre = $client->request('GET', '/ressource?list_find[value]=Bois&list_find[type]=*');
        static::assertResponseIsSuccessful();
        static::assertCount(1, $filtre->filter('li.list-group-item'));
        static::assertStringContainsString('Bois', $filtre->filter('li.list-group-item')->text());
    }
}
