<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AuteurDeclaration;
use App\Service\DecisionsDeclaration;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class DecisionsDeclarationTest extends TestCase
{
    public function testUnElementAjouteParLeJoueurAttendUneDecision(): void
    {
        $decisions = DecisionsDeclaration::suivre([], ['sorts' => []], ['sorts' => [3, 5]], new AuteurDeclaration(true, 10, new DateTimeImmutable('2026-11-09')));

        static::assertSame(['source' => 'joueur', 'etat' => 'en_attente'], DecisionsDeclaration::lire($decisions, 'sorts', 3));
        static::assertSame(['source' => 'joueur', 'etat' => 'en_attente'], DecisionsDeclaration::lire($decisions, 'sorts', 5));
        static::assertSame(['sorts' => ['joueur' => 2, 'en_attente' => 2]], DecisionsDeclaration::resume($decisions));
    }

    public function testCeQueLeStaffSaisitEstConfirme(): void
    {
        $decisions = DecisionsDeclaration::suivre([], ['potions' => []], ['potions' => [7]], new AuteurDeclaration(false, 11, new DateTimeImmutable()));

        static::assertSame(['source' => 'depouilleur', 'etat' => 'confirme'], DecisionsDeclaration::lire($decisions, 'potions', 7));
        static::assertSame(['potions' => ['joueur' => 0, 'en_attente' => 0]], DecisionsDeclaration::resume($decisions));
    }

    public function testUnElementRetireNeGardePasDeDecisionEtLesAutresRestent(): void
    {
        $maintenant = new DateTimeImmutable();
        $decisions = DecisionsDeclaration::suivre([], ['sorts' => []], ['sorts' => [1, 2]], new AuteurDeclaration(true, 10, $maintenant));
        $decisions = DecisionsDeclaration::suivre($decisions, ['sorts' => [1, 2]], ['sorts' => [2]], new AuteurDeclaration(true, 10, $maintenant));

        static::assertSame([2], array_keys($decisions['sorts']));
    }

    public function testLeStaffQuiAjouteNeChangePasLaProvenanceDeLExistant(): void
    {
        $maintenant = new DateTimeImmutable();
        $decisions = DecisionsDeclaration::suivre([], ['sorts' => []], ['sorts' => [1]], new AuteurDeclaration(true, 10, $maintenant));
        $decisions = DecisionsDeclaration::suivre($decisions, ['sorts' => [1]], ['sorts' => [1, 2]], new AuteurDeclaration(false, 11, $maintenant));

        static::assertSame('joueur', DecisionsDeclaration::lire($decisions, 'sorts', 1)['source']);
        static::assertSame('depouilleur', DecisionsDeclaration::lire($decisions, 'sorts', 2)['source']);
    }

    public function testUnChampSimpleSuitSaValeur(): void
    {
        $maintenant = new DateTimeImmutable();
        $decisions = DecisionsDeclaration::suivre([], ['renommee' => null], ['renommee' => '  +2 pour Untel '], new AuteurDeclaration(true, 10, $maintenant));
        static::assertSame('en_attente', DecisionsDeclaration::lire($decisions, 'renommee')['etat']);

        // Même valeur (aux espaces près) : rien ne change.
        $inchange = DecisionsDeclaration::suivre($decisions, ['renommee' => '+2 pour Untel'], ['renommee' => '+2 pour Untel'], new AuteurDeclaration(false, 11, $maintenant));
        static::assertSame($decisions, $inchange);

        // Champ vidé : la décision disparaît.
        $vide = DecisionsDeclaration::suivre($decisions, ['renommee' => '+2 pour Untel'], ['renommee' => ''], new AuteurDeclaration(true, 10, $maintenant));
        static::assertSame([], $vide);
    }

    public function testUnBooleenCoche(): void
    {
        $decisions = DecisionsDeclaration::suivre([], ['personnage_mort' => false], ['personnage_mort' => true], new AuteurDeclaration(true, 10, new DateTimeImmutable()));

        static::assertSame('en_attente', DecisionsDeclaration::lire($decisions, 'personnage_mort')['etat']);
    }

    public function testUnElementSansDecisionVientDuDepouillementEtEstConfirme(): void
    {
        static::assertSame(['source' => 'depouilleur', 'etat' => 'confirme'], DecisionsDeclaration::lire([], 'sorts', 99));
    }

    public function testUneDecisionGardeLaSourceEtNoteQuiADecide(): void
    {
        $maintenant = new DateTimeImmutable('2026-11-09');
        $decisions = DecisionsDeclaration::suivre([], ['sorts' => []], ['sorts' => [4]], new AuteurDeclaration(true, 10, $maintenant));
        $decisions = DecisionsDeclaration::decider($decisions, 'sorts', '4', DecisionsDeclaration::ETAT_REFUSE, new AuteurDeclaration(false, 11, $maintenant), ['libelle' => 'Boule de feu']);

        $entree = DecisionsDeclaration::entree($decisions, 'sorts', '4');
        static::assertSame('joueur', $entree['source'] ?? null);
        static::assertSame('refuse', $entree['etat'] ?? null);
        static::assertSame(11, $entree['decide_par'] ?? null);
        static::assertSame('Boule de feu', $entree['libelle'] ?? null);

        // Remis en attente : le libellé de secours disparaît.
        $decisions = DecisionsDeclaration::decider($decisions, 'sorts', '4', DecisionsDeclaration::ETAT_EN_ATTENTE, new AuteurDeclaration(false, 11, $maintenant));
        static::assertArrayNotHasKey('libelle', DecisionsDeclaration::entree($decisions, 'sorts', '4') ?? []);
    }

    public function testDeciderSurUnElementSansEntreeLeCreeCommeVenuDuDepouillement(): void
    {
        $decisions = DecisionsDeclaration::decider([], 'potions', '9', DecisionsDeclaration::ETAT_CONFIRME, new AuteurDeclaration(false, 11, new DateTimeImmutable()));

        static::assertSame(['source' => 'depouilleur', 'etat' => 'confirme'], DecisionsDeclaration::lire($decisions, 'potions', 9));
    }
}
