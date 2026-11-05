<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\LogAction;
use App\Enum\LogActionType;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La page des logs d'action filtre par type d'action et libelle ses critères de recherche.
 */
#[Group('functional')]
class LogActionFiltreTypeTest extends WebTestCase
{
    public function testFiltreParTypeNeGardeQueLesLogsDuType(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        foreach ([
            [LogActionType::XP_ADD,      'marqueur-xp-aaa'],
            [LogActionType::GROUPE_LOCK, 'marqueur-groupe-bbb'],
        ] as [$type, $marqueur]) {
            $log = new LogAction();
            $log->setType($type);
            $log->setDate(new DateTime());
            $log->setData(['note' => $marqueur]);
            $em->persist($log);
        }
        $em->flush();

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $crawler = $client->request('GET', '/admin/action/logs');
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('marqueur-xp-aaa', $crawler->text());
        static::assertStringContainsString('marqueur-groupe-bbb', $crawler->text());
        static::assertGreaterThanOrEqual(1, $crawler->filter('select[aria-label="Rechercher dans"]')->count());
        static::assertGreaterThanOrEqual(1, $crawler->filter('select[name="typeAction"] option[value="xp_add"]')->count());

        $crawler = $client->request('GET', '/admin/action/logs?typeAction=xp_add');
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('marqueur-xp-aaa', $crawler->text());
        static::assertStringNotContainsString('marqueur-groupe-bbb', $crawler->text());
    }

    public function testTriParTypeEtParUtilisateurEstAccepte(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        foreach (['type', '-username', 'date'] as $champ) {
            $client->request('GET', '/admin/action/logs?order_by=' . $champ);
            static::assertResponseIsSuccessful($champ);
        }
    }
}
