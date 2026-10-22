<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteSituation;
use App\Service\EtiquetteRegles;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// @mago-expect lint:too-many-methods
#[Group('unit')]
class EtiquetteReglesTest extends TestCase
{
    private EtiquetteRegles $regles;

    protected function setUp(): void
    {
        $this->regles = new EtiquetteRegles();
    }

    /**
     * @return iterable<string, array{0: string, 1: EtiquetteDestination}>
     */
    public static function specialesOuGeneriques(): iterable
    {
        yield 'usuelle sans rien de spécial (061)' => ['061', EtiquetteDestination::STOCK];
        yield 'usuelle rien de spécial (599)' => ['599', EtiquetteDestination::STOCK];
        yield 'usuelle, nature 81 (valeur présente en base)' => ['181', EtiquetteDestination::STOCK];
        yield 'usuelle destinée particulière (101)' => ['101', EtiquetteDestination::ENVELOPPE];
        yield 'usuelle, destinée particulière 01 (010)' => ['010', EtiquetteDestination::ENVELOPPE];
        yield 'exceptionnelle sans rien de spécial (661)' => ['661', EtiquetteDestination::ENVELOPPE];
        yield 'chef-d\'oeuvre sans rien de spécial (899)' => ['899', EtiquetteDestination::ENVELOPPE];
        yield 'magie exceptionnelle (611)' => ['611', EtiquetteDestination::ENVELOPPE];
        yield 'prêtrise exceptionnelle (721)' => ['721', EtiquetteDestination::ENVELOPPE];
        yield 'chaudrons (757)' => ['757', EtiquetteDestination::ENVELOPPE];
    }

    #[DataProvider('specialesOuGeneriques')]
    public function testSpecialeOuGenerique(string $qualite, EtiquetteDestination $destination): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, $qualite, EtiquetteSituation::SANS_OBJET);

        $speciale = EtiquetteDestination::ENVELOPPE === $destination;
        static::assertSame($speciale, $decision->speciale);
        static::assertSame($destination, $decision->destination);
        static::assertSame($speciale, $decision->aPrevoirProchainOpus);
    }

    public function testFactureEtNatureSontLues(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, '621', EtiquetteSituation::SANS_OBJET);

        static::assertSame('exceptionnelle', $decision->facture);
        static::assertSame('enchanté par la prêtrise (culte)', $decision->nature);
        static::assertSame([], $decision->anomalies);
        static::assertSame('chef-d\'oeuvre', $this->regles->evaluer(EtiquetteCouleur::ORANGE, '812', EtiquetteSituation::SANS_OBJET)->facture);
        static::assertSame('enchanté par la magie', $this->regles->evaluer(EtiquetteCouleur::ORANGE, '812', EtiquetteSituation::SANS_OBJET)->nature);
        static::assertSame('lié aux chaudrons', $this->regles->evaluer(EtiquetteCouleur::ORANGE, '657', EtiquetteSituation::SANS_OBJET)->nature);
        static::assertSame('usuelle', $this->regles->evaluer(EtiquetteCouleur::ORANGE, '263', EtiquetteSituation::SANS_OBJET)->facture);
    }

    public function testEtiquetteOrangeSansQualiteEstGenerique(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, null, EtiquetteSituation::SANS_OBJET);

        static::assertSame(EtiquetteDestination::STOCK, $decision->destination);
        static::assertFalse($decision->speciale);
        static::assertSame([], $decision->anomalies);
    }

    public function testOrangeAvecObjetEstUneAnomalieEtObjetTrouve(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, '263', EtiquetteSituation::AVEC_OBJET);

        static::assertSame(EtiquetteActionObjet::OBJET_TROUVE, $decision->actionObjet);
        static::assertSame(EtiquetteDestination::STOCK, $decision->destination);
        static::assertCount(1, $decision->anomalies);
    }

    public function testOrangeSpecialeAvecObjetGardeSonEtiquetteDansLEnveloppe(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, '721', EtiquetteSituation::AVEC_OBJET);

        static::assertSame(EtiquetteActionObjet::OBJET_TROUVE, $decision->actionObjet);
        static::assertSame(EtiquetteDestination::ENVELOPPE, $decision->destination);
        static::assertTrue($decision->aPrevoirProchainOpus);
    }

    public function testBleueAvecObjetVaAuPcJoueurEtEstAPrevoir(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::BLEUE, '263', EtiquetteSituation::AVEC_OBJET);

        static::assertSame(EtiquetteDestination::AVEC_OBJET_AU_PC, $decision->destination);
        static::assertSame(EtiquetteActionObjet::REMETTRE_AU_PC, $decision->actionObjet);
        static::assertTrue($decision->aPrevoirProchainOpus);
        static::assertSame([], $decision->anomalies);
    }

    public function testBleueAvecObjetDejaRemisAuPc(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::BLEUE, '263', EtiquetteSituation::OBJET_REMIS_AU_PC);

        static::assertSame(EtiquetteDestination::AVEC_OBJET_AU_PC, $decision->destination);
        static::assertSame(EtiquetteActionObjet::DEJA_AU_PC, $decision->actionObjet);
        static::assertTrue($decision->aPrevoirProchainOpus);
    }

    public function testBleueSansObjetEstAuJoueurAvecUneAnomalie(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::BLEUE, '263', EtiquetteSituation::SANS_OBJET);

        static::assertSame(EtiquetteDestination::AU_JOUEUR, $decision->destination);
        static::assertNull($decision->actionObjet);
        static::assertFalse($decision->aPrevoirProchainOpus);
        static::assertStringContainsString('objet est manquant', $decision->anomalies[0]);
    }

    public function testNatureHorsTableEstConserveeAvecAnomalie(): void
    {
        foreach (['058', '059', '060', '100'] as $qualite) {
            $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, $qualite, EtiquetteSituation::SANS_OBJET);

            static::assertTrue($decision->speciale, $qualite);
            static::assertSame(EtiquetteDestination::ENVELOPPE, $decision->destination, $qualite);
            static::assertNotSame([], $decision->anomalies, $qualite);
        }
    }

    public function testEnchantementSurUneFactureUsuelleEstSignale(): void
    {
        $decision = $this->regles->evaluer(EtiquetteCouleur::ORANGE, '321', EtiquetteSituation::SANS_OBJET);

        static::assertCount(1, $decision->anomalies);
        static::assertStringContainsString('au moins de facture exceptionnelle', $decision->anomalies[0]);
        static::assertSame([], $this->regles->evaluer(EtiquetteCouleur::ORANGE, '621', EtiquetteSituation::SANS_OBJET)->anomalies);
        static::assertSame([], $this->regles->evaluer(EtiquetteCouleur::ORANGE, '305', EtiquetteSituation::SANS_OBJET)->anomalies);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function qualitesInvalides(): iterable
    {
        yield 'deux chiffres' => ['62'];
        yield 'quatre chiffres' => ['6211'];
        yield 'lettres' => ['abc'];
        yield 'avec espace' => ['6 21'];
    }

    #[DataProvider('qualitesInvalides')]
    public function testQualiteInvalideEstRefusee(string $qualite): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->regles->evaluer(EtiquetteCouleur::ORANGE, $qualite, EtiquetteSituation::SANS_OBJET);
    }
}
