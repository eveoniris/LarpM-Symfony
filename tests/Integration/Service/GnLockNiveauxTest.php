<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Groupe;
use App\Repository\GnRepository;
use App\Service\GnLockService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Niveaux de verrouillage des groupes d'un GN : souple, strict, assouplissement, déverrouillage.
 */
#[Group('integration')]
class GnLockNiveauxTest extends KernelTestCase
{
    public function testSoupleStrictAssouplirEtDeverrouiller(): void
    {
        self::bootKernel();
        $service = static::getContainer()->get(GnLockService::class);
        $repository = static::getContainer()->get(GnRepository::class);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne(['lock' => false]);
        GroupeGnFactory::createOne(['gn' => $gn, 'groupe' => $groupe]);

        static::assertSame(['total' => 1, 'verrouilles' => 0, 'stricts' => 0], $repository->compterVerrous($gn));

        $service->verrouillerSouple($gn);
        static::assertSame(['total' => 1, 'verrouilles' => 1, 'stricts' => 0], $repository->compterVerrous($gn));

        $service->verrouillerStrict($gn);
        static::assertSame(['total' => 1, 'verrouilles' => 1, 'stricts' => 1], $repository->compterVerrous($gn));

        // Un verrouillage souple planifié ne rétrograde pas un strict posé par un admin
        $service->verrouillerSouple($gn);
        static::assertSame(1, $repository->compterVerrous($gn)['stricts']);

        $service->assouplir($gn);
        static::assertSame(['total' => 1, 'verrouilles' => 1, 'stricts' => 0], $repository->compterVerrous($gn));

        $service->verrouillerStrict($gn);
        $service->deverrouiller($gn);
        static::assertSame(['total' => 1, 'verrouilles' => 0, 'stricts' => 0], $repository->compterVerrous($gn));

        $em->clear();
        $frais = $em->find(Groupe::class, $groupe->getId());
        static::assertFalse($frais->getLock());
        static::assertFalse($frais->isLockStrict());
    }
}
