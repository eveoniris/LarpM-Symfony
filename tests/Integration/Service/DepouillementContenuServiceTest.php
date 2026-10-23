<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Enum\ActionStatut;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Contenu d'une enveloppe de joueur : enregistrement dans le brouillon, relecture, remarques "introuvable", garde-fous.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class DepouillementContenuServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementContenuService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementContenuService::class);
    }

    private function depouillement(DepouillementType $type = DepouillementType::JOUEUR): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType($type)
            ->setNumero('1')
            ->setSimulation(true);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    private function ingredient(): Ingredient
    {
        $ingredient = (new Ingredient())
            ->setLabel('Mandragore')
            ->setNiveau(1)
            ->setDose('1');
        $this->em->persist($ingredient);

        return $ingredient;
    }

    private function potion(string $numero): Potion
    {
        $potion = (new Potion())
            ->setLabel('Philtre')
            ->setNiveau(1)
            ->setNumero($numero)
            ->setSecret(false);
        $this->em->persist($potion);

        return $potion;
    }

    public function testEnregistrerPuisRelireConserveListesNombresEtSurplus(): void
    {
        $depouillement = $this->depouillement();
        $contenu = new ContenuJoueur();
        $contenu->fichePersonnage = true;
        $contenu->pincesALinge = 4;
        $contenu->documentsNumerotes = '12, 45';
        $contenu->potions = [$this->potion('P99')];
        $contenu->gemmeUtilise = true;
        $contenu->gemmeJointes = true;
        $contenu->gemmePieces = 2;
        $contenu->selUtilise = true;
        $contenu->selJointes = true;
        $contenu->selIngredients = [$this->ingredient()];
        $contenu->ivoireUtilise = true;
        $contenu->ivoireJointes = true;
        $contenu->ivoirePotions = [$this->potion('P12')];
        $contenu->surplus = ['bois' => 4, 'pieces_or' => 7, 'inconnu' => 9];
        $this->em->flush();

        $this->service->enregistrerContenuJoueur($depouillement, $contenu, UserFactory::createOne());
        $id = $depouillement->getId();
        $this->em->clear();

        $relu = $this->service->lireContenuJoueur($this->em->find(Depouillement::class, $id));

        static::assertTrue($relu->fichePersonnage);
        static::assertSame(4, $relu->pincesALinge);
        static::assertSame('12, 45', $relu->documentsNumerotes);
        static::assertCount(1, $relu->potions);
        static::assertSame(2, $relu->gemmePieces);
        static::assertCount(1, $relu->selIngredients);
        static::assertSame('Mandragore', $relu->selIngredients[0]->getLabel());
        static::assertCount(1, $relu->ivoirePotions);
        static::assertSame('P12', $relu->ivoirePotions[0]->getNumero());
        static::assertSame(4, $relu->surplus['bois']);
        static::assertSame(7, $relu->surplus['pieces_or']);
        static::assertArrayNotHasKey('inconnu', $relu->surplus);
        static::assertSame(3, $this->service->compterAcceptees($relu));
    }

    public function testLesStatutsDesActionsSontGardesDansLeBrouillon(): void
    {
        $depouillement = $this->depouillement();
        $contenu = new ContenuJoueur();
        $contenu->soieUtilise = true;
        $contenu->fruitsUtilise = true;
        $contenu->fruitsJointes = true;

        $this->service->enregistrerContenuJoueur($depouillement, $contenu, UserFactory::createOne());

        $statuts = $depouillement->getDonnees()['contenu_joueur']['statuts'];
        static::assertSame(ActionStatut::REFUSEE->value, $statuts['soie']);
        static::assertSame(ActionStatut::ACCEPTEE->value, $statuts['fruits_legumes']);
        static::assertSame(ActionStatut::NON_UTILISEE->value, $statuts['sel']);
        static::assertCount(1, $depouillement->getHistories());
        static::assertContains('Soie : les 3 ressource(s) exigée(s) ne sont pas dans l\'enveloppe, l\'action est refusée et sans effet.', $this->service->anomalies($contenu));
    }

    public function testIntrouvableAlimenteLesRemarquesSansDoublonNiPerteDeLExistant(): void
    {
        $depouillement = $this->depouillement();
        $depouillement->setRemarques("Il manque 2 ressources.\nAutre note.");
        $utilisateur = UserFactory::createOne();
        $contenu = new ContenuJoueur();
        $contenu->introuvables = "Sort de feu\ninconnu";

        $this->service->enregistrerContenuJoueur($depouillement, $contenu, $utilisateur);
        $this->service->enregistrerContenuJoueur($depouillement, $contenu, $utilisateur);

        static::assertSame("Il manque 2 ressources.\nAutre note.\n[Introuvable] Sort de feu inconnu", $depouillement->getRemarques());
        static::assertContains('Des éléments sont introuvables dans les listes : solliciter un orga (noté dans les remarques).', $this->service->anomalies($contenu));

        $contenu->introuvables = null;
        $this->service->enregistrerContenuJoueur($depouillement, $contenu, $utilisateur);

        static::assertSame("Il manque 2 ressources.\nAutre note.", $depouillement->getRemarques());
    }

    public function testRemarquesVidesRestentVidesSansIntrouvable(): void
    {
        $depouillement = $this->depouillement();

        $this->service->enregistrerContenuJoueur($depouillement, new ContenuJoueur(), UserFactory::createOne());

        static::assertNull($depouillement->getRemarques());
    }

    public function testRefuseUneEnveloppeDeGroupe(): void
    {
        $this->expectException(ContenuInvalideException::class);
        $this->expectExceptionMessage('enveloppes de joueur');

        $this->service->enregistrerContenuJoueur($this->depouillement(DepouillementType::GROUPE), new ContenuJoueur(), UserFactory::createOne());
    }

    public function testRefuseQuandLaSaisieEstTerminee(): void
    {
        $depouillement = $this->depouillement()->setStatut(DepouillementStatut::A_VALIDER);

        $this->expectException(ContenuInvalideException::class);
        $this->expectExceptionMessage('plus modifiable');

        $this->service->enregistrerContenuJoueur($depouillement, new ContenuJoueur(), UserFactory::createOne());
    }

    public function testLireUnDepouillementSansContenuDonneUnContenuVide(): void
    {
        $contenu = $this->service->lireContenuJoueur($this->depouillement());

        static::assertFalse($contenu->fichePersonnage);
        static::assertSame([], $contenu->sorts);
        static::assertSame([], $contenu->selIngredients);
        static::assertCount(24, $contenu->surplus);
        static::assertSame(0, $this->service->compterAcceptees($contenu));
    }
}
