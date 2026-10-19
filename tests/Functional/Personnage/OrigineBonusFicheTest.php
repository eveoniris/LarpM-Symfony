<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Entity\GroupeGn;
use App\Entity\OrigineBonus;
use App\Entity\Participant;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Enum\Status;
use App\Tests\Factory\BonusFactory;
use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La fiche (vue scénariste) liste le bonus d'origine seulement s'il a été acquis à la première participation.
 *
 * DAMA bundle annule chaque test.
 */
#[Group('functional')]
class OrigineBonusFicheTest extends WebTestCase
{
    private const string TITRE = 'Bonus-Origine-Fiche';

    /**
     * Crée un PJ d'origine avec bonus, dont le premier groupe possède (ou non) cette origine, et ouvre sa fiche.
     */
    private function ouvrirLaFiche(KernelBrowser $client, int $fiefsCommuns): void
    {
        EspeceFactory::createOne(['nom' => 'Humain']);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $origine = TerritoireFactory::createOne();
        $bonus = BonusFactory::createOne([
            'titre' => self::TITRE,
            'type' => BonusType::RENOMME->value,
            'periode' => BonusPeriode::NATIVE->value,
        ]);
        $em->persist(
            (new OrigineBonus())
                ->setTerritoire($origine)
                ->setBonus($bonus)
                ->setStatus(Status::ACTIVE),
        );
        $em->flush();
        $em->refresh($origine);

        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user, 'territoire' => $origine]);
        $gn = GnFactory::createOne(['dateDebut' => new DateTime('2020-01-01')]);
        $groupe = GroupeFactory::createOne();
        if ($fiefsCommuns > 0) {
            $origine->setGroupe($groupe);
            $groupe->addTerritoire($origine);
        }
        $groupeGn = (new GroupeGn())
            ->setGn($gn)
            ->setGroupe($groupe);
        $em->persist($groupeGn);
        $em->persist(
            (new Participant())
                ->setGn($gn)
                ->setUser($user)
                ->setPersonnage($personnage)
                ->setGroupeGn($groupeGn),
        );
        $em->flush();
        $em->clear();

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));
        $client->request('GET', '/personnage/' . (int) $personnage->getId());
    }

    public function testLaFicheListeLeBonusAcquisAvecSaSource(): void
    {
        $client = static::createClient();

        $this->ouvrirLaFiche($client, 1);

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', self::TITRE);
        static::assertMatchesRegularExpression('/>\s*ORIGINE\s*</', (string) $client->getResponse()->getContent());
    }

    public function testLaFicheNeListePasLeBonusRefuse(): void
    {
        $client = static::createClient();

        $this->ouvrirLaFiche($client, 0);

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString(self::TITRE, (string) $client->getResponse()->getContent());
    }
}
