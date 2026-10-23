<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Entity\Ressource;
use App\Enum\ActionFinOpus;
use App\Enum\ActionStatut;
use App\Service\ActionsFinOpusRegles;
use App\Service\CompteursEnveloppe;
use App\Service\ContenuJoueur;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// @mago-expect lint:too-many-methods
#[Group('unit')]
class ActionsFinOpusReglesTest extends TestCase
{
    private ActionsFinOpusRegles $regles;

    protected function setUp(): void
    {
        $this->regles = new ActionsFinOpusRegles();
    }

    public function testRienDeCocheRienNEstUtilise(): void
    {
        $resultats = $this->regles->evaluer(new ContenuJoueur());

        static::assertCount(6, $resultats);
        foreach ($resultats as $resultat) {
            static::assertSame(ActionStatut::NON_UTILISEE, $resultat->statut);
            static::assertSame([], $resultat->anomalies);
            static::assertNull($resultat->effet());
        }
    }

    public function testLesSixActionsSontIndependantesEtLaMemeEnveloppePeutToutesLesFaire(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->fruitsUtilise = true;
        $contenu->fruitsJointes = true;
        $contenu->soieUtilise = true;
        $contenu->soieJointes = true;
        $contenu->gemmeUtilise = true;
        $contenu->gemmeJointes = true;
        $contenu->gemmePieces = 3;
        $contenu->selUtilise = true;
        $contenu->selJointes = true;
        $contenu->selIngredients = [new Ingredient()];
        $contenu->fourrureUtilise = true;
        $contenu->fourrureJointes = true;
        $contenu->fourrureRessources = [new Ressource()];
        $contenu->ivoireUtilise = true;
        $contenu->ivoireJointes = true;
        $contenu->ivoirePotions = [new Potion()];

        foreach ($this->regles->evaluer($contenu) as $resultat) {
            static::assertSame(ActionStatut::ACCEPTEE, $resultat->statut, $resultat->action->value);
            static::assertSame([], $resultat->anomalies, $resultat->action->value);
            static::assertNotNull($resultat->effet());
        }
    }

    public function testActionCocheeSansLesRessourcesEstRefuseeEtSansEffet(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->selUtilise = true;
        $contenu->selIngredients = [new Ingredient(), new Ingredient()];

        $resultat = $this->regles->evaluer($contenu)[ActionFinOpus::SEL->value];

        static::assertSame(ActionStatut::REFUSEE, $resultat->statut);
        static::assertSame(0, $resultat->elementsConserves);
        static::assertNull($resultat->effet());
        static::assertStringContainsString('l\'action est refusée', $resultat->anomalies[0]);
        static::assertStringContainsString('reliquat', $resultat->anomalies[1]);
    }

    public function testRefusDeFruitsEtLegumesNeParleDePasDeReliquat(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->fruitsUtilise = true;

        $resultat = $this->regles->evaluer($contenu)[ActionFinOpus::FRUITS_LEGUMES->value];

        static::assertSame(ActionStatut::REFUSEE, $resultat->statut);
        static::assertCount(1, $resultat->anomalies);
        static::assertStringContainsString('5 ressource(s)', $resultat->anomalies[0]);
    }

    public function testAuDelaDuPlafondEstSignale(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->gemmeUtilise = true;
        $contenu->gemmeJointes = true;
        $contenu->gemmePieces = 5;

        $resultat = $this->regles->evaluer($contenu)[ActionFinOpus::GEMME->value];

        static::assertSame(ActionStatut::ACCEPTEE, $resultat->statut);
        static::assertStringContainsString('le maximum est 3', $resultat->anomalies[0]);
    }

    public function testActionAccepteeSansAucunElementAConserverEstSignalee(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->ivoireUtilise = true;
        $contenu->ivoireJointes = true;

        $resultat = $this->regles->evaluer($contenu)[ActionFinOpus::IVOIRE->value];

        static::assertSame(ActionStatut::ACCEPTEE, $resultat->statut);
        static::assertStringContainsString('aucun élément à conserver', $resultat->anomalies[0]);
    }

    public function testElementsListesSansCocheSontIgnoresEtSignales(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->fourrureRessources = [new Ressource()];

        $resultat = $this->regles->evaluer($contenu)[ActionFinOpus::FOURRURE->value];

        static::assertSame(ActionStatut::NON_UTILISEE, $resultat->statut);
        static::assertSame(0, $resultat->elementsConserves);
        static::assertStringContainsString('ignorés', $resultat->anomalies[0]);
    }

    public function testPlafondsEtCoutsDeLaFiche(): void
    {
        static::assertSame(5, ActionFinOpus::FRUITS_LEGUMES->quantiteRessource());
        static::assertSame(3, ActionFinOpus::SOIE->quantiteRessource());
        static::assertSame(1, ActionFinOpus::SEL->quantiteRessource());
        static::assertNull(ActionFinOpus::FRUITS_LEGUMES->plafond());
        static::assertNull(ActionFinOpus::SOIE->plafond());
        static::assertSame(3, ActionFinOpus::GEMME->plafond());
        static::assertSame(6, ActionFinOpus::SEL->plafond());
        static::assertSame(3, ActionFinOpus::FOURRURE->plafond());
        static::assertSame(3, ActionFinOpus::IVOIRE->plafond());
    }

    public function testLesCompteursGardentLes24CasesDeLaFiche(): void
    {
        static::assertCount(24, CompteursEnveloppe::CHAMPS);
        static::assertArrayHasKey('teinture', CompteursEnveloppe::CHAMPS);
    }

    public function testNormaliserIgnoreLesCasesInconnuesEtLesNegatifs(): void
    {
        $normalisees = CompteursEnveloppe::normaliser(['bois' => '4', 'sel' => -2, 'inconnu' => 9, 'vin' => 'abc']);

        static::assertCount(24, $normalisees);
        static::assertSame(4, $normalisees['bois']);
        static::assertSame(0, $normalisees['sel']);
        static::assertSame(0, $normalisees['vin']);
        static::assertArrayNotHasKey('inconnu', $normalisees);
        static::assertSame(['Bois' => 4], CompteursEnveloppe::nonNulles(['bois' => 4, 'sel' => 0]));
    }

    public function testLeSurplusNetRetireCeQueLesActionsAccepteesConsomment(): void
    {
        $trouve = ['soie' => 5, 'fruits_legumes' => 5, 'gemmes' => 0, 'bois' => 3];
        $statuts = ['soie' => 'acceptee', 'fruits_legumes' => 'refusee', 'gemme' => 'acceptee', 'sel' => 'non_utilisee', 'inconnue' => 'acceptee'];

        $net = ActionsFinOpusRegles::surplusNet($trouve, $statuts);

        // Soie acceptée : -3. Fruits refusés : rien n'est consommé. Gemme acceptée sans gemme trouvée : jamais négatif.
        static::assertSame(2, $net['soie']);
        static::assertSame(5, $net['fruits_legumes']);
        static::assertSame(0, $net['gemmes']);
        static::assertSame(3, $net['bois']);
    }

    public function testLaPreselectionDesActionsSuitLesRessourcesTrouvees(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->surplus = ['soie' => 3, 'fruits_legumes' => 2, 'sel' => 0, 'gemmes' => 1];

        $contenu->preselectionnerActions();

        static::assertSame(ContenuJoueur::DECISION_VALIDEE, $contenu->getSoieDecision());
        static::assertSame(ContenuJoueur::DECISION_INSUFFISANT, $contenu->getFruitsDecision());
        static::assertSame(ContenuJoueur::DECISION_AUCUNE, $contenu->getSelDecision());
        static::assertSame(ContenuJoueur::DECISION_VALIDEE, $contenu->getGemmeDecision());
        static::assertTrue($contenu->soieUtilise && $contenu->soieJointes);
        static::assertTrue($contenu->fruitsUtilise && !$contenu->fruitsJointes);
    }

    public function testUneDecisionSeRangeDansLesDeuxCasesHistoriques(): void
    {
        $contenu = new ContenuJoueur();

        $contenu->setIvoireDecision(ContenuJoueur::DECISION_INSUFFISANT);
        static::assertTrue($contenu->ivoireUtilise);
        static::assertFalse($contenu->ivoireJointes);

        $contenu->setIvoireDecision(ContenuJoueur::DECISION_AUCUNE);
        static::assertFalse($contenu->ivoireUtilise);
        static::assertSame(ContenuJoueur::DECISION_AUCUNE, $contenu->getIvoireDecision());
    }
}
