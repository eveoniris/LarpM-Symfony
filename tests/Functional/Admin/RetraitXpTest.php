<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\ExperienceGain;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Enum\LogActionType;
use App\Service\RetraitXpService;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class RetraitXpTest extends WebTestCase
{
    private function retirer(KernelBrowser $client, int $gainId, string $mode, string $motif): void
    {
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $crawler = $client->request('GET', '/admin/xp/gain');
        static::assertResponseIsSuccessful();
        $token = $crawler->filter(\sprintf('form[action$="/admin/xp/gain/%d/retirer"] input[name=_token]', $gainId))->attr('value');
        $client->request('POST', \sprintf('/admin/xp/gain/%d/retirer', $gainId), ['_token' => $token, 'mode' => $mode, 'motif' => $motif]);
    }

    public function testRetraitSoftGardeLaLigneEtCorrigeLeSolde(): void
    {
        $client = static::createClient();
        $personnage = PersonnageFactory::createOne(['xp' => 50]);
        $gain = ExperienceGainFactory::createOne(['personnage' => $personnage, 'xp_gain' => 20, 'explanation' => 'Gain à retirer']);

        $this->retirer($client, $gain->getId(), 'soft', 'Erreur de saisie');
        static::assertResponseRedirects('/admin/xp/gain');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertSame(30, $em->find(Personnage::class, $personnage->getId())->getXp());
        $garde = $em->find(ExperienceGain::class, $gain->getId());
        static::assertNotNull($garde);
        static::assertTrue($garde->isDeleted());
        static::assertCount(0, $em->find(Personnage::class, $personnage->getId())->getExperienceGains());

        $logs = $em->getRepository(LogAction::class)->findBy(['type' => LogActionType::XP_RETRAIT->value]);
        static::assertNotEmpty($logs);
        static::assertStringContainsString('Erreur de saisie', json_encode($logs[0]->getData(), \JSON_UNESCAPED_UNICODE));

        // Un gain déjà retiré ne peut pas l'être deux fois : le solde reste inchangé.
        $dejaRetire = $em->find(ExperienceGain::class, $gain->getId());
        static::assertFalse(static::getContainer()->get(RetraitXpService::class)->retirer($dejaRetire, 'soft', 'Doublon'));
        $em->clear();
        static::assertSame(30, $em->find(Personnage::class, $personnage->getId())->getXp());
    }

    public function testRetraitHardSupprimeLaLigne(): void
    {
        $client = static::createClient();
        $personnage = PersonnageFactory::createOne(['xp' => 40]);
        $gain = ExperienceGainFactory::createOne(['personnage' => $personnage, 'xp_gain' => 15]);
        $id = $gain->getId();

        $this->retirer($client, $id, 'hard', 'Personnage mal créé');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertNull($em->find(ExperienceGain::class, $id));
        static::assertSame(25, $em->find(Personnage::class, $personnage->getId())->getXp());
        static::assertNotEmpty($em->getRepository(LogAction::class)->findBy(['type' => LogActionType::XP_RETRAIT->value]));
    }

    public function testMotifObligatoire(): void
    {
        $client = static::createClient();
        $personnage = PersonnageFactory::createOne(['xp' => 40]);
        $gain = ExperienceGainFactory::createOne(['personnage' => $personnage, 'xp_gain' => 15]);

        $this->retirer($client, $gain->getId(), 'soft', '   ');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertSame(40, $em->find(Personnage::class, $personnage->getId())->getXp());
        static::assertFalse($em->find(ExperienceGain::class, $gain->getId())->isDeleted());
    }
}
