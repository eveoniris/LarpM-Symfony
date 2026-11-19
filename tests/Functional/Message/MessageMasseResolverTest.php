<?php

declare(strict_types=1);

namespace App\Tests\Functional\Message;

use App\Service\MessageMasseResolver;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
class MessageMasseResolverTest extends KernelTestCase
{
    public function testUnJoueurAvecDeuxPersonnagesEtCasLimites(): void
    {
        self::bootKernel();
        $joueur = UserFactory::createOne();
        $vivant = PersonnageFactory::createOne(['user' => $joueur]);
        $mort = PersonnageFactory::createOne(['user' => $joueur, 'vivant' => false]);
        $orphelin = PersonnageFactory::createOne(['user' => null]);

        $cibles = self::getContainer()
            ->get(MessageMasseResolver::class)
            ->resolve(MessageMasseResolver::TYPE_PERSONNAGE, [
                $vivant->getId(),
                $mort->getId(),
                $orphelin->getId(),
                999_999,
            ]);

        static::assertCount(1, $cibles->destinataires, 'un seul message par joueur, mort compris');
        static::assertCount(2, $cibles->destinataires[$joueur->getId()]->personnages);
        static::assertSame([$orphelin->getId()], $cibles->sansUser);
        static::assertSame([999_999], $cibles->introuvables);
    }

    public function testParIdentifiantsUtilisateur(): void
    {
        self::bootKernel();
        $joueur = UserFactory::createOne();

        $cibles = self::getContainer()
            ->get(MessageMasseResolver::class)
            ->resolve(MessageMasseResolver::TYPE_USER, [$joueur->getId(), 888_888]);

        static::assertSame([$joueur->getId()], array_keys($cibles->destinataires));
        static::assertSame([888_888], $cibles->introuvables);
    }
}
