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
 * Les types de log ajoutés avec les messages en masse, les ajouts en masse, les refus d'accès et le retrait d'XP
 * sont proposés au filtre et leurs données (listes, tableaux imbriqués) s'affichent sans erreur.
 */
#[Group('functional')]
class LogActionNouveauxTypesTest extends WebTestCase
{
    public function testNouveauxTypesFiltrablesEtAffiches(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $logs = [
            [LogActionType::MESSAGE_MASSE, ['type' => 'personnage', 'envoyes' => 2, 'sans_user' => [4], 'introuvables' => [], 'titre' => 'marqueur-message-masse']],
            [LogActionType::AJOUT_MASSE, ['data' => ['masse' => true, 'type' => 'chronologie', 'valeur' => 1450, 'motif' => 'marqueur-ajout-masse', 'personnage_ids' => [1, 2, 3]]]],
            [LogActionType::NO_ACCESS, ['data' => ['route' => 'message.response', 'url' => '/messagerie/9/response', 'message' => 'marqueur-no-access']]],
            [LogActionType::XP_RETRAIT, ['mode' => 'soft', 'motif' => 'marqueur-retrait', 'xp_retire' => 5, 'personnage_id' => 7]],
        ];
        foreach ($logs as [$type, $data]) {
            $log = new LogAction();
            $log->setType($type);
            $log->setDate(new DateTime());
            $log->setData($data);
            $em->persist($log);
        }
        $em->flush();

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $attendus = [
            'message_masse' => 'marqueur-message-masse',
            'ajout_masse' => 'marqueur-ajout-masse',
            'no_access' => 'marqueur-no-access',
            'xp_retrait' => 'marqueur-retrait',
        ];
        foreach ($attendus as $valeur => $marqueur) {
            $crawler = $client->request('GET', '/admin/action/logs?typeAction=' . $valeur);
            static::assertResponseIsSuccessful($valeur);
            static::assertStringContainsString($marqueur, $crawler->text(), $valeur);
            static::assertGreaterThanOrEqual(1, $crawler->filter('select[name="typeAction"] option[value="' . $valeur . '"]')->count(), $valeur);
        }
    }
}
