<?php

declare(strict_types=1);

namespace App\Tests\Functional\InterJeu;

use App\Entity\InterJeu;
use App\Entity\Personnage;
use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\InterJeuFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Impression en flux des fiches des personnages d'un inter-jeu.
 *
 * Les personnages créés par PersonnageFactory n'ont aucune participation :
 * ce test couvre donc explicitement le rendu d'un personnage « sans participation ».
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class InterJeuPrintTest extends WebTestCase
{
    public function testPrintMaterielStreamsAllPersonnages(): void
    {
        $content = $this->renderPrintRoute('materiel');

        static::assertStringContainsString('Matériel supplémentaire', $content);
    }

    public function testPrintFicheStreamsAllPersonnages(): void
    {
        $content = $this->renderPrintRoute('fiche');

        // Bloc « MORT » présent sur chaque fiche complète.
        static::assertStringContainsString('MORT', $content);
    }

    private function renderPrintRoute(string $type): string
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        // Espèce « Humain » : référence utilisée comme défaut pour un perso sans espèce.
        EspeceFactory::createOne(['nom' => 'Humain']);
        $p1 = PersonnageFactory::createOne(['nom' => 'AlphaPrint']);
        $p2 = PersonnageFactory::createOne(['nom' => 'BetaPrint']);
        $interJeu = InterJeuFactory::createOne();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $interJeuEntity = $em->find(InterJeu::class, $interJeu->getId());
        $interJeuEntity->addPersonnage($em->find(Personnage::class, $p1->getId()));
        $interJeuEntity->addPersonnage($em->find(Personnage::class, $p2->getId()));
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnages/print/' . $type);

        static::assertResponseIsSuccessful();

        // Le BrowserKit a déjà drainé la StreamedResponse dans la réponse interne.
        $content = (string) $client->getInternalResponse()->getContent();

        static::assertStringContainsString('AlphaPrint', $content);
        static::assertStringContainsString('BetaPrint', $content);

        return $content;
    }
}
