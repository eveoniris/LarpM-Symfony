<?php

declare(strict_types=1);

namespace App\Tests\Functional\Rulebook;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Consultation du rulebook : bêta + rôle scénariste (version claire des règles publiées) ou rôle règle (tout).
 * DAMA annule chaque test à la fin.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class RulebookAccesTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $domaine = (new RulebookDomaine())
            ->setCode('xp')
            ->setTitre('Expérience')
            ->setPosition(10);
        $entityManager->persist($domaine);
        $this->regle($entityManager, $domaine, 'XP-01', 'Coût public', RulebookStatut::VALIDE, publiee: true, clair: 'Texte **clair** public.', technique: 'Formule secrète.');
        $this->regle($entityManager, $domaine, 'XP-02', 'Brouillon interne', RulebookStatut::VALIDE, publiee: false, clair: 'Pas encore publiée.', technique: 'Détail du brouillon.');
        $this->regle($entityManager, $domaine, 'XP-03', 'Règle buguée', RulebookStatut::BUGUE, publiee: true, clair: 'Texte de la règle buguée.', technique: 'Bug connu.');
        $entityManager->flush();
    }

    // @mago-expect lint:excessive-parameter-list
    private function regle(EntityManagerInterface $entityManager, RulebookDomaine $domaine, string $code, string $titre, RulebookStatut $statut, bool $publiee, string $clair, string $technique): void
    {
        $entityManager->persist(
            (new RulebookRegle())
                ->setDomaine($domaine)
                ->setCode($code)
                ->setTitre($titre)
                ->setStatut($statut)
                ->setPubliee($publiee)
                ->setTexteClair($clair)
                ->setDetailTechnique($technique)
                ->setTestsRefs(['tests/Unit/XpTest.php']),
        );
    }

    /** @param list<string> $roles */
    private function connecter(array $roles): void
    {
        $this->client->loginUser(UserFactory::createOne(['roles' => $roles]));
    }

    public function testAnonymeEstRedirigeVersLaConnexion(): void
    {
        $this->client->request('GET', '/rulebook');

        $this->assertResponseRedirects();
    }

    /** @return array<string, array{list<string>}> */
    public static function rolesSansAcces(): array
    {
        return [
            'scenariste sans beta' => [['ROLE_USER', 'ROLE_SCENARISTE']],
            'regle sans beta' => [['ROLE_USER', 'ROLE_REGLE']],
            'beta sans role scenariste ni regle' => [['ROLE_USER', 'ROLE_BETA_TEST']],
        ];
    }

    /** @param list<string> $roles */
    #[DataProvider('rolesSansAcces')]
    public function testRefuseSansBetaOuSansRoleMetier(array $roles): void
    {
        $this->connecter($roles);
        $this->client->request('GET', '/rulebook');

        // L'application redirige les accès refusés vers sa page dédiée au lieu de répondre 403.
        $this->assertResponseRedirects('/access_denied');
    }

    public function testScenaristeVoitSeulementLesReglesPubliquesEtPubliees(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']);
        $this->client->request('GET', '/rulebook');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Coût public');
        $this->assertSelectorTextNotContains('body', 'Brouillon interne');
        $this->assertSelectorTextNotContains('body', 'Règle buguée');
        $this->assertSelectorNotExists('select[name="statut"]');
    }

    public function testScenaristeLitLaVersionClaireSansLeDetailTechnique(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']);
        $this->client->request('GET', '/rulebook/regle/XP-01');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('strong');
        $this->assertSelectorTextContains('body', 'Texte clair public.');
        $this->assertSelectorTextNotContains('body', 'Formule secrète.');
        $this->assertSelectorTextNotContains('body', 'tests/Unit/XpTest.php');
    }

    public function testScenaristeVoitUneRegleReserveeCommeInexistante(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']);

        foreach (['XP-02', 'XP-03', 'XP-99'] as $code) {
            $this->client->request('GET', '/rulebook/regle/' . $code);
            $this->assertResponseStatusCodeSame(404);
        }
    }

    public function testRechercheDuScenaristeNeFouillePasLeDetailTechnique(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']);
        $this->client->request('GET', '/rulebook', ['q' => 'secrète']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('body', 'Coût public');
    }

    public function testEquipeRegleVoitToutAvecStatutsEtDetailTechnique(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->client->request('GET', '/rulebook');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Coût public');
        $this->assertSelectorTextContains('body', 'Brouillon interne');
        $this->assertSelectorTextContains('body', 'Règle buguée');
        $this->assertSelectorExists('select[name="statut"]');

        $this->client->request('GET', '/rulebook/regle/XP-03');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Bug connu.');
        $this->assertSelectorTextContains('body', 'tests/Unit/XpTest.php');
    }

    public function testEquipeRegleFiltreParStatutEtRechercheDansLeDetail(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);

        $this->client->request('GET', '/rulebook', ['statut' => 'bugue']);
        $this->assertSelectorTextContains('body', 'Règle buguée');
        $this->assertSelectorTextNotContains('body', 'Coût public');

        $this->client->request('GET', '/rulebook', ['q' => 'secrète']);
        $this->assertSelectorTextContains('body', 'Coût public');
        $this->assertSelectorTextNotContains('body', 'Brouillon interne');
    }

    public function testRechercheAvecCaracteresJokersNeRenvoieRien(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->client->request('GET', '/rulebook', ['q' => '%']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Aucune règle ne correspond');
    }

    public function testAdminAvecBetaVoitToutCommeLEquipeRegle(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_ADMIN']);
        $this->client->request('GET', '/rulebook/regle/XP-02');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Détail du brouillon.');
    }
}
