<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\Ingredient;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageRessource;
use App\Entity\RenommeHistory;
use App\Entity\Ressource;
use App\Enum\ChronologyType;
use App\Enum\DepouillementType;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementFinOpusService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Actions de fin d'Opus acceptées appliquées à la fiche : fruits (chronologie), soie (renommée), gemme (richesse), sel, fourrure.
 */
#[Group('integration')]
class DepouillementFinOpusServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementFinOpusService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementFinOpusService::class);
    }

    private function ingredient(string $label): Ingredient
    {
        $ingredient = (new Ingredient())
            ->setLabel($label)
            ->setNiveau(1)
            ->setDose('1');
        $this->em->persist($ingredient);

        return $ingredient;
    }

    private function ressource(string $label): Ressource
    {
        $ressource = (new Ressource())
            ->setLabel($label)
            ->setRarete($this->rarete());
        $this->em->persist($ressource);

        return $ressource;
    }

    private function rarete(): \App\Entity\Rarete
    {
        $rarete = $this->em->getRepository(\App\Entity\Rarete::class)->findOneBy([]) ?? (new \App\Entity\Rarete())
            ->setLabel('Commune')
            ->setValue(1);
        $this->em->persist($rarete);

        return $rarete;
    }

    /**
     * @return array{0: Depouillement, 1: Personnage}
     */
    private function enveloppe(ContenuJoueur $contenu, bool $simulation = false): array
    {
        $gn = GnFactory::createOne(['date_jeu' => 1300]);
        $personnage = PersonnageFactory::createOne(['richesse' => 5]);
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $personnage->getId())
            ->setParticipant($this->em->getRepository(Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $personnage]))
            ->setSimulation($simulation);
        $this->em->persist($depouillement);
        $this->em->flush();
        static::getContainer()->get(DepouillementContenuService::class)->enregistrerContenuJoueur($depouillement, $contenu, UserFactory::createOne());

        return [$depouillement, $personnage];
    }

    public function testFruitsEtSoieEcriventChronologieEtRenommee(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->fruitsUtilise = true;
        $contenu->fruitsJointes = true;
        $contenu->soieUtilise = true;
        $contenu->soieJointes = true;
        [$depouillement, $personnage] = $this->enveloppe($contenu);

        $resultat = $this->service->appliquer($depouillement);
        $this->em->flush();
        $this->em->clear();

        static::assertTrue($resultat->applique);
        $chronologie = $this->em->getRepository(PersonnageChronologie::class)->findOneBy(['personnage' => $personnage->getId(), 'evenement' => ChronologyType::FRUITS_AND_VEGETABLES->value]);
        static::assertSame(1300, $chronologie?->getAnnee());
        $renommes = $this->em->getRepository(RenommeHistory::class)->findBy(['personnage' => $personnage->getId()]);
        static::assertCount(1, $renommes);
        static::assertSame(1, $renommes[0]->getRenomme());
        static::assertStringEndsWith('- Soie', (string) $renommes[0]->getExplication());
    }

    public function testLaGemmeConvertitLesPiecesDOrEnArgentAvecPlafond(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->gemmeUtilise = true;
        $contenu->gemmeJointes = true;
        $contenu->gemmePieces = 5;
        [$depouillement, $personnage] = $this->enveloppe($contenu);

        $this->service->appliquer($depouillement);

        // Plafond de 3 pièces d'or, 10 pièces d'argent chacune, sur 5 déjà possédées.
        static::assertSame(35, $personnage->getRichesse());
    }

    public function testSelEtFourrureAjoutentOuIncrementent(): void
    {
        $mandragore = $this->ingredient('Mandragore');
        $peau = $this->ressource('Peau de loup');
        $contenu = new ContenuJoueur();
        $contenu->selUtilise = true;
        $contenu->selJointes = true;
        $contenu->selIngredients = [$mandragore];
        $contenu->fourrureUtilise = true;
        $contenu->fourrureJointes = true;
        $contenu->fourrureRessources = [$peau];
        [$depouillement, $personnage] = $this->enveloppe($contenu);
        $existant = (new PersonnageIngredient())
            ->setIngredient($mandragore)
            ->setNombre(2)
            ->setPersonnage($personnage);
        $this->em->persist($existant);
        $this->em->flush();
        $personnage->getPersonnageIngredients()->add($existant);

        $this->service->appliquer($depouillement);
        $this->em->flush();

        static::assertSame(3, $existant->getNombre());
        static::assertCount(1, $this->em->getRepository(PersonnageRessource::class)->findBy(['personnage' => $personnage]));
    }

    public function testUneActionRefuseeOuNonUtiliseeNeFaitRien(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->soieUtilise = true; // ressources non jointes : refusée
        $contenu->fruitsJointes = true; // non cochée : non utilisée
        [$depouillement, $personnage] = $this->enveloppe($contenu);

        $resultat = $this->service->appliquer($depouillement);

        static::assertTrue($resultat->estVide());
        static::assertCount(0, $personnage->getRenommeHistories());
    }

    public function testUneSimulationNEcritRien(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->gemmeUtilise = true;
        $contenu->gemmeJointes = true;
        $contenu->gemmePieces = 2;
        $contenu->soieUtilise = true;
        $contenu->soieJointes = true;
        [$depouillement, $personnage] = $this->enveloppe($contenu, true);

        $resultat = $this->service->appliquer($depouillement);
        $this->em->flush();

        static::assertFalse($resultat->applique);
        static::assertCount(2, $resultat->ajoutes);
        static::assertSame(5, $personnage->getRichesse());
        static::assertSame([], $this->em->getRepository(RenommeHistory::class)->findBy(['personnage' => $personnage->getId()]));
    }
}
