<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Enum\Status;
use LogicException;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Reprise des personnages existants : simulation sans écriture, application idempotente, cas douteux isolables.
 */
#[Group('integration')]
class OrigineBonusRepriseTest extends OrigineBonusIntegrationTestCase
{
    /**
     * Quatre personnages « existants », donc sans décision enregistrée :
     * cohérent acquis, cohérent refusé, douteux (territoire de réf. du groupe = origine sans fief), différé.
     *
     * @return array{acquis: Personnage, refuse: Personnage, douteux: Personnage, differe: Personnage}
     */
    private function personnagesExistants(): array
    {
        $origine = $this->origineAvecBonus();

        $acquis = PersonnageFactory::createOne(['territoire' => $origine]);
        $groupeAcquis = $this->groupeGn([$origine]);
        $groupeAcquis->getGroupe()->setTerritoire($origine);
        $this->participant($acquis, $groupeAcquis);

        $refuse = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($refuse, $this->groupeGn([TerritoireFactory::createOne()]));

        $douteux = PersonnageFactory::createOne(['territoire' => $origine]);
        $groupeDouteux = $this->groupeGn([]);
        $groupeDouteux->getGroupe()->setTerritoire($origine);
        $this->participant($douteux, $groupeDouteux);

        $differe = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($differe, null);

        // L'écouteur a déjà décidé : on retire les décisions pour simuler des personnages antérieurs à la fonctionnalité.
        $this->em->createQuery('DELETE FROM ' . PersonnageBonus::class . ' pb')->execute();
        $this->em->clear();

        return compact('acquis', 'refuse', 'douteux', 'differe');
    }

    private function lancer(array $options = []): CommandTester
    {
        $application = new Application(self::$kernel ?? throw new LogicException('Kernel non démarré.'));
        $tester = new CommandTester($application->find('app:origine-bonus:reprise'));
        $tester->execute($options);

        return $tester;
    }

    /**
     * @return array<int, Status>
     */
    private function statuts(Personnage $personnage): array
    {
        $statuts = [];
        foreach ($this->rows($this->recharger(Personnage::class, $personnage->getId())) as $row) {
            $statuts[] = $row->getStatus();
        }

        return $statuts;
    }

    public function testLaSimulationNEcritRienEtListeLesCasDouteux(): void
    {
        $pj = $this->personnagesExistants();

        $tester = $this->lancer();

        static::assertSame(0, $tester->getStatusCode());
        $sortie = $tester->getDisplay();
        static::assertStringContainsString('Simulation', $sortie);
        static::assertStringContainsString('1 cas douteux', $sortie);
        static::assertStringContainsString((string) $pj['douteux']->getId(), $sortie);
        foreach ($pj as $personnage) {
            static::assertSame([], $this->statuts($personnage));
        }
    }

    public function testApplyEnregistreLesDecisionsEtLesDifferesRestentSansLigne(): void
    {
        $pj = $this->personnagesExistants();

        $this->lancer(['--apply' => true]);

        static::assertSame([Status::ACTIVE], $this->statuts($pj['acquis']));
        static::assertSame([Status::DELETED], $this->statuts($pj['refuse']));
        static::assertSame([Status::DELETED], $this->statuts($pj['douteux']));
        static::assertSame([], $this->statuts($pj['differe']));
    }

    public function testSansDouteuxLaisseLesCasDouteuxSurLAncienCalcul(): void
    {
        $pj = $this->personnagesExistants();

        $this->lancer(['--apply' => true, '--sans-douteux' => true]);

        static::assertSame([Status::ACTIVE], $this->statuts($pj['acquis']));
        static::assertSame([Status::DELETED], $this->statuts($pj['refuse']));
        static::assertSame([], $this->statuts($pj['douteux']));
    }

    public function testApplyEstIdempotent(): void
    {
        $pj = $this->personnagesExistants();

        $this->lancer(['--apply' => true]);
        $this->em->clear();
        $tester = $this->lancer(['--apply' => true]);

        static::assertSame([Status::ACTIVE], $this->statuts($pj['acquis']));
        static::assertStringContainsString('Décision déjà enregistrée', $tester->getDisplay());
    }
}
