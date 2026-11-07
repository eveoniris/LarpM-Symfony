<?php

declare(strict_types=1);

namespace App\Tests\Functional\Rulebook;

use App\Entity\LogAction;
use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Entity\RulebookRevision;
use App\Enum\LogActionType;
use App\Enum\RulebookStatut;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Édition en ligne du rulebook : réservée à l'équipe règle, nettoyage du markdown, versions, historique, restauration.
 * DAMA annule chaque test à la fin.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class RulebookEditionTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $domaine = (new RulebookDomaine())
            ->setCode('xp')
            ->setTitre('Expérience')
            ->setPosition(10);
        $regle = (new RulebookRegle())
            ->setDomaine($domaine)
            ->setCode('XP-01')
            ->setTitre('Coût')
            ->setStatut(RulebookStatut::VALIDE)
            ->setTexteClair('Texte initial.')
            ->setDetailTechnique('Détail initial.')
            ->setPosition(1);
        $this->entityManager->persist($domaine);
        $this->entityManager->persist($regle);
        $this->entityManager->persist(RulebookRevision::depuis($regle, null, 'Import initial'));
        $this->entityManager->flush();
    }

    /** @param list<string> $roles */
    private function connecter(array $roles): void
    {
        $this->client->loginUser(UserFactory::createOne(['roles' => $roles]));
    }

    private function regle(): RulebookRegle
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => 'XP-01']);
    }

    /** @param array<string, mixed> $valeurs */
    private function soumettreEdition(array $valeurs): void
    {
        $crawler = $this->client->request('GET', '/rulebook/regle/XP-01/editer');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer')->form();
        foreach ($valeurs as $nom => $valeur) {
            $form['rulebook_regle[' . $nom . ']'] = $valeur;
        }
        $this->client->submit($form);
    }

    public function testScenaristeNePeutPasEditer(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']);
        $this->client->request('GET', '/rulebook/regle/XP-01/editer');

        $this->assertResponseRedirects('/access_denied');
    }

    public function testEquipeRegleModifieEtCreeUneVersionAvecTrace(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->soumettreEdition(['texteClair' => "Nouveau\u{200B} texte\u{00A0}propre.", 'motif' => 'Précision']);

        $this->assertResponseRedirects('/rulebook/regle/XP-01');
        $regle = $this->regle();
        $this->assertSame(2, $regle->getVersion());
        $this->assertSame('Nouveau texte propre.', $regle->getTexteClair());
        $this->assertCount(2, $regle->getRevisions());
        $this->assertSame('Précision', $regle->getRevisions()->first()->getMotif());

        $logs = $this->entityManager->getRepository(LogAction::class)->findBy(['type' => LogActionType::RULEBOOK->value]);
        $this->assertCount(1, $logs);
        $this->assertSame('XP-01', $logs[0]->getData()['regle']);
        $this->assertSame(2, $logs[0]->getData()['version']);
    }

    public function testSansChangementPasDeNouvelleVersion(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->soumettreEdition([]);

        $this->assertSame(1, $this->regle()->getVersion());
    }

    public function testPublierSansVersionClaireEstRefuse(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->soumettreEdition(['texteClair' => '', 'publiee' => true]);

        $this->assertSelectorTextContains('body', 'Rédigez la version claire');
        $regle = $this->regle();
        $this->assertFalse($regle->isPubliee());
        $this->assertSame(1, $regle->getVersion());
    }

    public function testCreationRefuseUnCodeExistant(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $crawler = $this->client->request('GET', '/rulebook/nouvelle?domaine=xp');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['rulebook_regle[code]'] = 'XP-01';
        $form['rulebook_regle[titre]'] = 'Doublon';
        $this->client->submit($form);

        $this->assertSelectorTextContains('body', 'Ce code existe déjà');
    }

    public function testCreationEnregistreLaRegleEtSaPremiereRevision(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $crawler = $this->client->request('GET', '/rulebook/nouvelle?domaine=xp');
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['rulebook_regle[code]'] = 'XP-02';
        $form['rulebook_regle[titre]'] = 'Nouvelle règle';
        $form['rulebook_regle[testsRefs]'] = "tests/A.php\ntests/A.php\n tests/B.php ";
        $this->client->submit($form);

        $this->assertResponseRedirects('/rulebook/regle/XP-02');
        $this->entityManager->clear();
        $regle = $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => 'XP-02']);
        $this->assertSame(['tests/A.php', 'tests/B.php'], $regle->getTestsRefs());
        $this->assertCount(1, $regle->getRevisions());
    }

    public function testEquipeRegleVoitLHistoriqueMaisNePeutPasRestaurer(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->soumettreEdition(['texteClair' => 'Deuxième texte.']);
        $this->client->request('GET', '/rulebook/regle/XP-01/historique');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('form[action*="restaurer"]');

        $this->client->request('POST', '/rulebook/regle/XP-01/restaurer/1', ['_token' => 'x']);
        $this->assertResponseRedirects('/access_denied');
    }

    public function testAdminRestaureUneAncienneVersion(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_ADMIN']);
        $this->soumettreEdition(['texteClair' => 'Deuxième texte.']);
        $this->assertSame(2, $this->regle()->getVersion());

        $crawler = $this->client->request('GET', '/rulebook/regle/XP-01/historique');
        $this->client->submit($crawler->filter('form[action*="restaurer/1"]')->selectButton('Restaurer')->form());

        $this->assertResponseRedirects('/rulebook/regle/XP-01');
        $regle = $this->regle();
        $this->assertSame(3, $regle->getVersion());
        $this->assertSame('Texte initial.', $regle->getTexteClair());
        $this->assertCount(3, $regle->getRevisions());
    }

    public function testApercuRefuseSansJeton(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $this->client->request('POST', '/rulebook/apercu', [], [], [], '**gras**');

        $this->assertResponseRedirects('/access_denied');
    }

    public function testApercuRendLeMarkdownEnHtmlSur(): void
    {
        $this->connecter(['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_REGLE']);
        $crawler = $this->client->request('GET', '/rulebook/regle/XP-01/editer');
        $jeton = $crawler->filter('textarea.js-markdown')->first()->attr('data-apercu-token');

        $this->client->request('POST', '/rulebook/apercu', [], [], ['HTTP_X-CSRF-Token' => $jeton], '**gras** <script>alert(1)</script>');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('<strong>gras</strong>', (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('<script', (string) $this->client->getResponse()->getContent());
    }
}
