<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\EnveloppeLigne;
use App\Entity\Participant;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Commande app:depouillement:importer : reporte les dépouillements en attente quand le personnage est inscrit au GN suivant.
 */
#[Group('integration')]
class ImporterDepouillementsTest extends KernelTestCase
{
    private function commande(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:depouillement:importer'));
    }

    public function testImporteLesDepouillementsEnAttenteEtIgnoreLesSimulations(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $gn = GnFactory::createOne(['actif' => true, 'date_debut' => new DateTime('+1 month'), 'date_fin' => new DateTime('+1 month +3 days')]);
        $suivant = GnFactory::createOne(['actif' => true, 'date_debut' => new DateTime('+6 month'), 'date_fin' => new DateTime('+6 month +3 days')]);
        $reel = $this->enAttente($em, $gn, $suivant, false);
        $simulation = $this->enAttente($em, $gn, $suivant, true);

        $commande->execute([]);

        static::assertStringContainsString('1 dépouillement(s) importé(s), 0 toujours en attente', $commande->getDisplay());
        static::assertSame(DepouillementStatut::TERMINE, $reel->getStatut());
        static::assertSame(DepouillementStatut::EN_ATTENTE_IMPORT, $simulation->getStatut());
        static::assertCount(1, $em->getRepository(EnveloppeLigne::class)->findAll());
    }

    public function testSansGnSuivantLeDepouillementResteEnAttente(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $gn = GnFactory::createOne(['actif' => true, 'date_debut' => new DateTime('+1 month'), 'date_fin' => new DateTime('+1 month +3 days')]);
        $depouillement = $this->enAttente($em, $gn, null, false);

        $commande->execute(['--gn' => (string) $gn->getId()]);

        static::assertStringContainsString('0 dépouillement(s) importé(s), 1 toujours en attente', $commande->getDisplay());
        static::assertSame(DepouillementStatut::EN_ATTENTE_IMPORT, $depouillement->getStatut());
    }

    public function testUnGnInconnuEstRefuse(): void
    {
        $commande = $this->commande();

        static::assertSame(1, $commande->execute(['--gn' => '99999999']));
        static::assertStringContainsString('introuvable', $commande->getDisplay());
    }

    private function enAttente(EntityManagerInterface $em, object $gn, ?object $suivant, bool $simulation): Depouillement
    {
        $personnage = PersonnageFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
        if (null !== $suivant) {
            ParticipantFactory::createOne(['gn' => $suivant, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
        }

        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $personnage->getId())
            ->setParticipant($em->getRepository(Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $personnage]))
            ->setSimulation($simulation)
            ->setStatut(DepouillementStatut::EN_ATTENTE_IMPORT);
        $etiquette = (new DepouillementEtiquette())
            ->setNature(EtiquetteNature::OBJET)
            ->setCouleur(EtiquetteCouleur::ORANGE)
            ->setNumero(5)
            ->setQualite('812')
            ->setQuantite(1)
            ->setSituation(EtiquetteSituation::SANS_OBJET)
            ->setDestination(EtiquetteDestination::ENVELOPPE)
            ->setSpeciale(true)
            ->setAPrevoirProchainOpus(true);
        $depouillement->addEtiquette($etiquette);
        $em->persist($depouillement);
        $em->persist($etiquette);
        $em->flush();

        return $depouillement;
    }
}
