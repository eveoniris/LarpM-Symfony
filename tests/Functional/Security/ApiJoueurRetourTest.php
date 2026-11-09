<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\LangueFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Retour de jeu du joueur et de son groupe par l'API : propriétaire seulement, bêta-testeurs, mêmes règles que le site.
 */
#[Group('functional')]
// @mago-expect lint:too-many-methods
class ApiJoueurRetourTest extends WebTestCase
{
    private const array BETA = ['ROLE_USER', 'ROLE_BETA_TEST'];

    private KernelBrowser $client;

    private Gn $gn;

    private GroupeGn $groupeGn;

    /**
     * @return array{0: User, 1: Personnage}
     */
    private function joueur(array $roles = self::BETA): array
    {
        $user = UserFactory::createOne(['roles' => $roles]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => $personnage]);

        return [$user, $personnage];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->gn = GnFactory::createOne();
        $this->groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $this->gn]);
    }

    /**
     * @param array<string, mixed>|null $corps
     *
     * @return array<array-key, mixed>
     */
    private function appel(string $methode, string $chemin, ?User $user, ?array $corps = null, int $statut = 200): array
    {
        $client = $this->client;
        $serveur = null === $user ? [] : ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jeton($user)];
        $client->request(
            $methode,
            '/api/joueur/gn/' . $this->gn->getId() . $chemin,
            [],
            [],
            $serveur + ['CONTENT_TYPE' => 'application/json'],
            null === $corps ? null : json_encode($corps, JSON_THROW_ON_ERROR),
        );
        static::assertSame($statut, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $donnees = json_decode((string) $client->getResponse()->getContent(), true);

        return \is_array($donnees) ? $donnees : [];
    }

    public function testSansJetonRenvoie401(): void
    {
        foreach (['/retour', '/retour-groupe'] as $chemin) {
            $this->appel('GET', $chemin, null, statut: 401);
        }
    }

    public function testUnJoueurSansRoleBetaEstRefuse(): void
    {
        [$user] = $this->joueur(['ROLE_USER']);

        $this->appel('GET', '/retour', $user, statut: 403);
    }

    public function testLeJoueurOuvreEtEnregistreSonRetour(): void
    {
        [$user] = $this->joueur();

        static::assertFalse($this->v($this->appel('GET', '/retour', $user), 'ouvert'));

        $langue = LangueFactory::createOne();
        $retour = $this->appel('PUT', '/retour', $user, [
            'declaration' => ['precisions' => 'RAS', 'sorti_du_jeu' => true],
            'appris' => ['langues' => [$langue->getId()], 'personnage_mort' => false],
        ]);

        static::assertTrue($this->v($retour, 'ouvert'));
        static::assertTrue($this->v($retour, 'modifiable'));
        static::assertSame('RAS', $this->v($retour, 'declaration.precisions'));
        static::assertTrue($this->v($retour, 'declaration.sorti_du_jeu'));
        static::assertSame([$langue->getId()], $this->v($retour, 'appris.langues'));

        // Une clé absente du corps est conservée.
        $apres = $this->appel('PUT', '/retour', $user, ['declaration' => ['autres' => 'Merci']]);
        static::assertSame('RAS', $this->v($apres, 'declaration.precisions'));
        static::assertSame('Merci', $this->v($apres, 'declaration.autres'));
    }

    public function testUneLangueSecreteOuInconnueEstRefusee(): void
    {
        [$user] = $this->joueur();
        $secrete = LangueFactory::createOne(['secret' => true]);

        $this->appel('PUT', '/retour', $user, ['appris' => ['langues' => [$secrete->getId()]]], 422);
        $this->appel('PUT', '/retour', $user, ['appris' => ['langues' => [99_999_999]]], 422);
        $this->appel('PUT', '/retour', $user, ['appris' => ['sorts' => 'x']], 422);
    }

    public function testLeRetourEnvoyeNEstPlusModifiable(): void
    {
        [$user] = $this->joueur();
        $this->appel('PUT', '/retour', $user, ['declaration' => ['autres' => 'Un']]);

        $envoye = $this->appel('POST', '/retour/envoyer', $user);
        static::assertSame('a_valider', $this->v($envoye, 'statut'));
        $this->appel('PUT', '/retour', $user, ['declaration' => ['autres' => 'Deux']], 422);
        $this->appel('POST', '/retour/envoyer', $user, statut: 422);
    }

    public function testUnJoueurSansPersonnageRecoit404(): void
    {
        $user = UserFactory::createOne(['roles' => self::BETA]);

        $this->appel('GET', '/retour', $user, statut: 404);
    }

    public function testLeChefDeGroupeEnregistreLeRetourDuGroupe(): void
    {
        [$chef, $personnageChef] = $this->joueur();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $participant = $em->getRepository(Participant::class)->findOneBy(['user' => $chef, 'gn' => $this->gn]);
        $this->groupeGn->setParticipant($participant);
        $em->flush();

        $retour = $this->appel('PUT', '/retour-groupe', $chef, [
            'titres' => ['suzerain' => $personnageChef->getId()],
            'ordres' => ['1' => 'Conquérir la côte'],
        ]);

        static::assertSame($personnageChef->getId(), $this->v($retour, 'titres.suzerain'));
        static::assertSame('Conquérir la côte', $this->v($retour, 'ordres.0.texte'));
        static::assertFalse($this->v($retour, 'ordres.0.valide'));
        static::assertSame(1, $this->v($retour, 'suivi.attendus'));

        $this->appel('PUT', '/retour-groupe', $chef, ['titres' => ['roi' => 1]], 422);
        $this->appel('PUT', '/retour-groupe', $chef, ['titres' => ['diplomate' => 99_999_999]], 422);
    }

    public function testUnSimpleMembreNAPasAccesAuRetourDuGroupe(): void
    {
        [$membre] = $this->joueur();

        $this->appel('GET', '/retour-groupe', $membre, statut: 403);
        $this->appel('PUT', '/retour-groupe', $membre, ['precisions' => 'x'], 403);
    }

    /**
     * Valeur imbriquée d'une réponse JSON, par chemin « a.b.0 ».
     *
     * @param array<array-key, mixed> $donnees
     */
    private function v(array $donnees, string $chemin): mixed
    {
        $courant = $donnees;
        foreach (explode('.', $chemin) as $cle) {
            if (!is_array($courant) || !array_key_exists($cle, $courant)) {
                return null;
            }

            $courant = $courant[$cle];
        }

        return $courant;
    }

    private function jeton(User $user): string
    {
        // Le jeton embarque l'IP et l'agent de la requête courante : il en faut une.
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }
}
