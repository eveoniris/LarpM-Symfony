<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Régression : `list_find[type]=*` est une valeur légitime du champ « Tout critère » de
 * ListFindType (pas un scan malveillant), propagée via le paramètre `t` généré par
 * l'appli elle-même pour les liens de tri. Un bug de shadowing de variable dans
 * `PagerService::getForm()` réutilisait ce paramètre pour choisir la classe de FormType
 * à instancier, provoquant `Could not load type "*"`.
 */
#[Group('functional')]
class PersonnageUpdateSortTest extends WebTestCase
{
    public function testUpdateSortWithWildcardSearchTypeDoesNotCrash(): void
    {
        $client = static::createClient();

        EspeceFactory::createOne(['nom' => 'Humain']);
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $personnage = PersonnageFactory::createOne();

        $client->loginUser($scenariste);
        $client->request('GET', '/personnage/' . $personnage->getId() . '/updateSort?list_find%5Btype%5D=*&t=*');

        static::assertResponseIsSuccessful();
    }
}
