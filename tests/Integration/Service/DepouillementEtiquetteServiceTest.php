<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\Item;
use App\Entity\Localisation;
use App\Entity\Objet;
use App\Entity\Quality;
use App\Entity\Rangement;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Exception\EtiquetteInvalideException;
use App\Service\DepouillementEtiquetteService;
use App\Service\EtiquetteSaisie;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Saisie des étiquettes : règles appliquées, objet retrouvé par numéro et qualité, anomalies, validations.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class DepouillementEtiquetteServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementEtiquetteService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementEtiquetteService::class);
    }

    private function depouillement(): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    /**
     * Crée un objet connu : numéro (gauche), centaine de qualité et identification (droite).
     */
    private function objet(int $numero, int $centaine, string $identification, string $couleur = 'orange'): Item
    {
        $localisation = (new Localisation())
            ->setLabel('Local')
            ->setPrecision('Test');
        $rangement = (new Rangement())
            ->setLabel('Étagère')
            ->setPrecision('Test')
            ->setLocalisation($localisation);
        $objet = (new Objet())
            ->setNumero('T' . $numero)
            ->setNom('Stock ' . $numero)
            ->setRangement($rangement)
            ->setNombre(1)
            ->setCout(0.0)
            ->setBudget(0.0)
            ->setInvestissement(false)
            ->setCreationDate(new DateTime());
        $this->em->persist($localisation);
        $this->em->persist($rangement);
        $qualite = (new Quality())
            ->setLabel('Qualité ' . $centaine)
            ->setNumero($centaine);
        // Le constructeur ajoute une valeur vide (monnaie et nombre obligatoires) dont ce test n'a pas besoin.
        $qualite->getQualityValeurs()->clear();
        $item = (new Item())
            ->setLabel('Objet ' . $numero)
            ->setDescription('Description')
            ->setNumero($numero)
            ->setIdentification($identification)
            ->setCouleur($couleur)
            ->setQuantite(1)
            ->setDateCreation(new DateTime())
            ->setDateUpdate(new DateTime())
            ->setObjet($objet)
            ->setQuality($qualite);
        $this->em->persist($objet);
        $this->em->persist($qualite);
        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    private function saisie(EtiquetteNature $nature, EtiquetteCouleur $couleur, ?int $numero, ?string $qualite, EtiquetteSituation $situation = EtiquetteSituation::SANS_OBJET): EtiquetteSaisie
    {
        $saisie = new EtiquetteSaisie();
        $saisie->nature = $nature;
        $saisie->couleur = $couleur;
        $saisie->numero = $numero;
        $saisie->qualite = $qualite;
        $saisie->situation = $situation;

        return $saisie;
    }

    public function testObjetConnuEtSpecialEstRattacheEtAPrevoir(): void
    {
        $item = $this->objet(5001, 6, '21');
        $depouillement = $this->depouillement();

        $etiquette = $this->service->ajouter($depouillement, $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 5001, '621'), UserFactory::createOne());

        static::assertSame($item->getId(), $etiquette->getItem()?->getId());
        static::assertSame(EtiquetteDestination::ENVELOPPE, $etiquette->getDestination());
        static::assertTrue($etiquette->isSpeciale());
        static::assertTrue($etiquette->isAPrevoirProchainOpus());
        static::assertSame([], $etiquette->getAnomalies());
        static::assertCount(1, $depouillement->getHistories());
    }

    public function testObjetInconnuEstSignale(): void
    {
        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 987_654, '263'), UserFactory::createOne());

        static::assertNull($etiquette->getItem());
        static::assertStringContainsString('Aucun objet n°987654', $etiquette->getAnomalies()[0]);
        static::assertStringContainsString('orga expert', $etiquette->getAnomalies()[0]);
    }

    public function testQualiteDifferenteDeLObjetUniqueEstSignalee(): void
    {
        $this->objet(5002, 6, '21');

        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 5002, '711'), UserFactory::createOne());

        static::assertNull($etiquette->getItem());
        static::assertStringContainsString('a la qualité 621 en base, l\'étiquette indique 711', $etiquette->getAnomalies()[0]);
    }

    public function testLaQualiteDepartageLesObjetsQuiPartagentUnNumero(): void
    {
        $this->objet(5003, 3, '63');
        $special = $this->objet(5003, 6, '21');

        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 5003, '621'), UserFactory::createOne());

        static::assertSame($special->getId(), $etiquette->getItem()?->getId());
        static::assertSame([], $etiquette->getAnomalies());
    }

    public function testPlusieursObjetsIdentiquesRestentAmbigus(): void
    {
        $this->objet(5004, 6, '21');
        $this->objet(5004, 6, '21');

        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 5004, '621'), UserFactory::createOne());

        static::assertNull($etiquette->getItem());
        static::assertStringContainsString('Plusieurs objets portent le n°5004', $etiquette->getAnomalies()[0]);
    }

    public function testCouleurDifferenteDeCelleDeLObjetEstSignalee(): void
    {
        $this->objet(5005, 2, '63', 'bleu');

        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 5005, '263'), UserFactory::createOne());

        static::assertNotNull($etiquette->getItem());
        static::assertStringContainsString('de couleur bleu en base', $etiquette->getAnomalies()[0]);
    }

    public function testEtiquetteBleueAvecObjetEstRemiseAuPcJoueur(): void
    {
        $this->objet(5006, 2, '63', 'bleu');

        $etiquette = $this->service->ajouter(
            $this->depouillement(),
            $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::BLEUE, 5006, '263', EtiquetteSituation::AVEC_OBJET),
            UserFactory::createOne(),
        );

        static::assertSame(EtiquetteDestination::AVEC_OBJET_AU_PC, $etiquette->getDestination());
        static::assertSame(EtiquetteActionObjet::REMETTRE_AU_PC, $etiquette->getActionObjet());
        static::assertTrue($etiquette->isAPrevoirProchainOpus());
        static::assertSame([], $etiquette->getAnomalies());
    }

    public function testEtiquetteBleueSansObjetEstConsigneeCommeAnormale(): void
    {
        $this->objet(5007, 2, '63', 'bleu');

        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::BLEUE, 5007, '263'), UserFactory::createOne());

        static::assertSame(EtiquetteDestination::AU_JOUEUR, $etiquette->getDestination());
        static::assertStringContainsString('objet est manquant', $etiquette->getAnomalies()[0]);
    }

    public function testLotDEtiquettesDArmesGeneriquesEstCompteSansRechercheDObjet(): void
    {
        $saisie = $this->saisie(EtiquetteNature::ARME, EtiquetteCouleur::ORANGE, 77, null);
        $saisie->quantite = 7;

        $etiquette = $this->service->ajouter($this->depouillement(), $saisie, UserFactory::createOne());

        static::assertSame(7, $etiquette->getQuantite());
        static::assertSame(EtiquetteDestination::STOCK, $etiquette->getDestination());
        static::assertNull($etiquette->getQualite());
        static::assertNull($etiquette->getItem());
        static::assertSame([], $etiquette->getAnomalies());
    }

    public function testUneBanniereEstUneEtiquetteBleueGeneriqueComptee(): void
    {
        $saisie = $this->saisie(EtiquetteNature::BANNIERE, EtiquetteCouleur::ORANGE, 99, '123', EtiquetteSituation::AVEC_OBJET);
        $saisie->quantite = 3;

        $etiquette = $this->service->ajouter($this->depouillement(), $saisie, UserFactory::createOne());

        static::assertSame(EtiquetteNature::BANNIERE, $etiquette->getNature());
        static::assertSame(EtiquetteCouleur::BLEUE, $etiquette->getCouleur());
        static::assertSame(EtiquetteSituation::SANS_OBJET, $etiquette->getSituation());
        static::assertSame(3, $etiquette->getQuantite());
        static::assertNull($etiquette->getNumero());
        static::assertNull($etiquette->getQualite());
        static::assertNull($etiquette->getItem());
        static::assertSame(EtiquetteDestination::ENVELOPPE, $etiquette->getDestination());
        static::assertFalse($etiquette->isAPrevoirProchainOpus());
        static::assertSame([], $etiquette->getAnomalies());
    }

    public function testUneSeuleBanniereNeSignaleRien(): void
    {
        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::BANNIERE, EtiquetteCouleur::BLEUE, null, null), UserFactory::createOne());

        static::assertSame([], $etiquette->getAnomalies());
        static::assertSame(1, $etiquette->getQuantite());
    }

    public function testUneEtiquetteDObjetCompteToujoursPourUne(): void
    {
        $this->objet(5008, 2, '63');
        $saisie = $this->saisie(EtiquetteNature::OBJET, EtiquetteCouleur::ORANGE, 5008, '263');
        $saisie->quantite = 12;

        static::assertSame(1, $this->service->ajouter($this->depouillement(), $saisie, UserFactory::createOne())->getQuantite());
    }

    public function testEtiquetteDArmeSpecialeEstConserveeSansRechercheDObjet(): void
    {
        $etiquette = $this->service->ajouter($this->depouillement(), $this->saisie(EtiquetteNature::ARME, EtiquetteCouleur::ORANGE, 12, '721'), UserFactory::createOne());

        static::assertSame(EtiquetteDestination::ENVELOPPE, $etiquette->getDestination());
        static::assertNull($etiquette->getItem());
    }

    /**
     * @return iterable<string, array{0: EtiquetteSaisie, 1: string}>
     */
    public static function saisiesInvalides(): iterable
    {
        $sansNumero = new EtiquetteSaisie();
        $sansNumero->qualite = '263';
        yield 'objet sans numéro' => [$sansNumero, 'numéro'];

        $sansQualite = new EtiquetteSaisie();
        $sansQualite->numero = 5;
        yield 'objet sans qualité' => [$sansQualite, 'qualité'];

        $bleueSansQualite = new EtiquetteSaisie();
        $bleueSansQualite->nature = EtiquetteNature::ARME;
        $bleueSansQualite->couleur = EtiquetteCouleur::BLEUE;
        yield 'étiquette bleue sans qualité' => [$bleueSansQualite, 'qualité'];

        $quantiteNulle = new EtiquetteSaisie();
        $quantiteNulle->nature = EtiquetteNature::ARME;
        $quantiteNulle->quantite = 0;
        yield 'quantité nulle' => [$quantiteNulle, 'quantité'];

        $qualiteMalFormee = new EtiquetteSaisie();
        $qualiteMalFormee->nature = EtiquetteNature::ARME;
        $qualiteMalFormee->qualite = '62';
        yield 'qualité à deux chiffres' => [$qualiteMalFormee, 'trois chiffres'];
    }

    #[DataProvider('saisiesInvalides')]
    public function testSaisieInvalideEstRefusee(EtiquetteSaisie $saisie, string $messageAttendu): void
    {
        $depouillement = $this->depouillement();

        try {
            $this->service->ajouter($depouillement, $saisie, UserFactory::createOne());
            static::fail('Une exception était attendue.');
        } catch (EtiquetteInvalideException $exception) {
            static::assertStringContainsString($messageAttendu, $exception->getMessage());
        }

        static::assertCount(0, $depouillement->getEtiquettes());
    }

    public function testImpossibleDAjouterQuandLaSaisieEstTerminee(): void
    {
        $depouillement = $this->depouillement()->setStatut(DepouillementStatut::A_VALIDER);

        $this->expectException(EtiquetteInvalideException::class);
        $this->expectExceptionMessage('plus modifiable');

        $this->service->ajouter($depouillement, $this->saisie(EtiquetteNature::ARME, EtiquetteCouleur::ORANGE, null, null), UserFactory::createOne());
    }

    public function testSupprimerRetireLaLigneEtLaTrace(): void
    {
        $depouillement = $this->depouillement();
        $utilisateur = UserFactory::createOne();
        $etiquette = $this->service->ajouter($depouillement, $this->saisie(EtiquetteNature::ARME, EtiquetteCouleur::ORANGE, null, null), $utilisateur);
        $id = $etiquette->getId();

        $this->service->supprimer($etiquette, $utilisateur);

        $this->em->clear();
        static::assertNull($this->em->find(DepouillementEtiquette::class, $id));
        // Ajout et suppression.
        static::assertCount(2, $this->em->find(Depouillement::class, $depouillement->getId())->getHistories());
    }
}
