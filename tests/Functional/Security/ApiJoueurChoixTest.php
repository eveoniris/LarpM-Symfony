<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Domaine;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\Sort;
use App\Entity\User;
use App\Enum\TriggerType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\ReligionFactory;
use App\Tests\Factory\TechnologieFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Choix de sort, domaine de magie, technologie et descriptif de religion : propriétaire seulement.
 */
#[Group('functional')]
class ApiJoueurChoixTest extends AbstractApiJoueurChoixTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        foreach (['sorts/1', 'domaines', 'technologies', 'religions/descriptifs'] as $suffixe) {
            $client->request('GET', '/api/joueur/personnage/1/' . $suffixe);
            static::assertSame(401, $client->getResponse()->getStatusCode(), $suffixe);
            $client->request('POST', '/api/joueur/personnage/1/' . $suffixe);
            static::assertSame(401, $client->getResponse()->getStatusCode(), $suffixe);
        }
    }

    public function testTechnologieListeEtChoix(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(TriggerType::TECHNOLOGIE);
        $technologie = TechnologieFactory::createOne();
        $auth = $this->auth($user);

        $reponse = $this->appel('GET', $personnage, 'technologies', null, $auth);
        static::assertContains($technologie->getId(), array_column($reponse['technologies'], 'id'));

        $this->appel('POST', $personnage, 'technologies', ['technologie_id' => $technologie->getId()], $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertFalse($em->find(Personnage::class, $personnage->getId())->hasTrigger(TriggerType::TECHNOLOGIE));
    }

    public function testReligionListeEtChoix(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(TriggerType::PRETRISE_INITIE);
        $religion = ReligionFactory::createOne();
        $auth = $this->auth($user);

        $reponse = $this->appel('GET', $personnage, 'religions/descriptifs', null, $auth);
        static::assertContains($religion->getId(), array_column($reponse['religions'], 'id'));

        $this->appel('POST', $personnage, 'religions/descriptifs', ['religion_id' => $religion->getId()], $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertFalse($em->find(Personnage::class, $personnage->getId())->hasTrigger(TriggerType::PRETRISE_INITIE));
    }

    public function testPersonnageDunAutreRefuse(): void
    {
        $client = static::createClient();
        [, $personnage] = $this->scenario(TriggerType::TECHNOLOGIE);
        $technologie = TechnologieFactory::createOne();
        $auth = $this->auth(UserFactory::createOne());

        $this->appel('GET', $personnage, 'technologies', null, $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
        $this->appel('POST', $personnage, 'technologies', ['technologie_id' => $technologie->getId()], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testGroupeVerrouilleRefuse(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(TriggerType::TECHNOLOGIE);
        $technologie = TechnologieFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['lock' => true]), 'gn' => GnFactory::createOne()]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $groupeGn->getGn(), 'groupeGn' => $groupeGn, 'personnage' => $personnage]);

        $this->appel('POST', $personnage, 'technologies', ['technologie_id' => $technologie->getId()], $this->auth($user));

        static::assertSame(403, $client->getResponse()->getStatusCode());
        static::assertStringContainsString('groupe_verrouille', (string) $client->getResponse()->getContent());
    }
}
