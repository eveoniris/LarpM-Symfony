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
 * Choix de sort et de domaine de magie : propriétaire seulement.
 */
#[Group('functional')]
class ApiJoueurMagieTest extends AbstractApiJoueurChoixTestCase
{
    public function testSortListeEtChoix(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(TriggerType::SORT_APPRENTI);
        $sort = $this->sort(1);
        $auth = $this->auth($user);

        $reponse = $this->appel('GET', $personnage, 'sorts/1', null, $auth);
        static::assertContains($sort->getId(), array_column($reponse['sorts'], 'id'));

        $this->appel('POST', $personnage, 'sorts/1', ['sort_id' => $sort->getId()], $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $frais = $em->find(Personnage::class, $personnage->getId());
        static::assertTrue($frais->isKnownSort($em->find(Sort::class, $sort->getId())));
        static::assertFalse($frais->hasTrigger(TriggerType::SORT_APPRENTI));

        $this->appel('POST', $personnage, 'sorts/1', ['sort_id' => $sort->getId()], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSortDunAutreNiveauOuSansTrigger(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(TriggerType::SORT_APPRENTI);
        $niveau2 = $this->sort(2);
        $auth = $this->auth($user);

        // Le trigger Apprenti ne donne pas accès au niveau 2.
        $this->appel('POST', $personnage, 'sorts/2', ['sort_id' => $niveau2->getId()], $auth);
        static::assertSame(403, $client->getResponse()->getStatusCode());

        // Un sort de niveau 2 n'est pas proposé au niveau 1.
        $this->appel('POST', $personnage, 'sorts/1', ['sort_id' => $niveau2->getId()], $auth);
        static::assertSame(422, $client->getResponse()->getStatusCode());

        $this->appel('POST', $personnage, 'sorts/1', ['sort_id' => 999999999], $auth);
        static::assertSame(404, $client->getResponse()->getStatusCode());

        $this->appel('POST', $personnage, 'sorts/1', [], $auth);
        static::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testDomaineListeEtChoix(): void
    {
        $client = static::createClient();
        [$user, $personnage] = $this->scenario(TriggerType::MAGIE_EXPERT);
        $domaine = $this->domaine();
        $auth = $this->auth($user);

        $reponse = $this->appel('GET', $personnage, 'domaines', null, $auth);
        static::assertContains($domaine->getId(), array_column($reponse['domaines'], 'id'));

        $this->appel('POST', $personnage, 'domaines', ['domaine_id' => $domaine->getId()], $auth);
        static::assertSame(201, $client->getResponse()->getStatusCode());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $frais = $em->find(Personnage::class, $personnage->getId());
        static::assertTrue($frais->isKnownDomaine($em->find(Domaine::class, $domaine->getId())));
        static::assertFalse($frais->hasTrigger(TriggerType::MAGIE_EXPERT));
    }
}
