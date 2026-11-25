<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\EventListener\ParametresTriSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ParametresTriTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function valeurs(): iterable
    {
        yield 'champ simple' => ['label', true];
        yield 'champ avec alias' => ['p.nom', true];
        yield 'liste OrderBy' => ['-nom,id', true];
        yield 'sous-requête' => ['id, (SELECT u.id FROM App\Entity\User u)', false];
        yield 'expression' => ['id+1', false];
        yield 'espace' => ['id DESC', false];
        yield 'vide' => ['', false];
    }

    #[DataProvider('valeurs')]
    public function testSeulsLesNomsDeChampsSontAcceptes(string $valeur, bool $attendu): void
    {
        static::assertSame($attendu, ParametresTriSubscriber::estChampValide($valeur));
    }

    public function testUnTriInjecteNeCassePasLaListePublique(): void
    {
        $client = static::createClient();
        $client->request('GET', '/religion?order_by=' . rawurlencode('id, (SELECT 1)') . '&order_dir=' . rawurlencode('ASC;'));

        static::assertResponseIsSuccessful();
    }
}
