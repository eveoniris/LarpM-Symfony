<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Routes /api/joueur : chaque joueur ne voit que ses propres données.
 */
#[Group('functional')]
class ApiJoueurTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        foreach (['/me', '/gn', '/groupe', '/participation/1', '/personnage/1', '/competences/1'] as $chemin) {
            $client->request('GET', '/api/joueur' . $chemin);
            static::assertSame(401, $client->getResponse()->getStatusCode(), $chemin);
        }
    }

    public function testPersonnageEtCompetencesDunAutreJoueurSontRefuses(): void
    {
        $client = static::createClient();
        $autre = PersonnageFactory::createOne(['nom' => 'Pas a moi']);
        $token = $this->jeton(UserFactory::createOne());

        foreach (['/personnage/', '/competences/'] as $chemin) {
            $client->request('GET', '/api/joueur' . $chemin . $autre->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
            static::assertSame(403, $client->getResponse()->getStatusCode(), $chemin);
            static::assertStringNotContainsString('Pas a moi', (string) $client->getResponse()->getContent());
        }
    }

    public function testMonGroupeAvecLesPersonnagesDesMembresSansDonneesJoueur(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['numero' => 9401, 'nom' => 'Les Loups']), 'gn' => $gn]);
        $moi = UserFactory::createOne(['email' => 'joueur-b@example.com']);
        $ami = UserFactory::createOne(['email' => 'ami-secret@example.com']);
        ParticipantFactory::createOne(['user' => $moi, 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => PersonnageFactory::createOne(['nom' => 'Zora'])]);
        ParticipantFactory::createOne(['user' => $ami, 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => PersonnageFactory::createOne(['nom' => 'Ayla'])]);
        $token = $this->jeton($moi);

        $client->request('GET', '/api/joueur/groupe?gn=' . $gn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        $contenu = (string) $client->getResponse()->getContent();
        /** @var array{groupe: array{numero: int}, membres: list<array{nom: string}>} $data */
        $data = json_decode($contenu, true);
        static::assertSame(9401, $data['groupe']['numero']);
        static::assertSame(['Ayla', 'Zora'], array_column($data['membres'], 'nom'));
        static::assertStringNotContainsString('ami-secret', $contenu);
    }

    public function testPasDeGroupePourCeGnRenvoie404(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $token = $this->jeton(UserFactory::createOne());

        $client->request('GET', '/api/joueur/groupe?gn=' . $gn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        static::assertSame(404, $client->getResponse()->getStatusCode());
    }

    private function jeton(User $user): string
    {
        // Le jeton embarque l'IP et l'agent de la requête courante : il en faut une.
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
