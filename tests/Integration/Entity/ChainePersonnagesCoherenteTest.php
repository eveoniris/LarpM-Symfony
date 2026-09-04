<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Garde-fou d'unicité : un personnage ne peut tenir qu'un seul rôle (principal,
 * relève ou substitution) sur un GN donné, toutes participations confondues.
 *
 * La réutilisation d'un GN à l'autre reste libre.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('integration')]
class ChainePersonnagesCoherenteTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testMemePersonnageEnPrincipalEtEnReleveEstRefuse(): void
    {
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $participant = ParticipantFactory::createOne(['user' => $user]);
        $participant->setPersonnage($personnage);
        $participant->setPersonnageReleve($personnage);

        $violations = $this->validator->validate($participant);

        static::assertCount(1, $violations);
        static::assertSame('personnageReleve', $violations->get(0)->getPropertyPath());
    }

    public function testPersonnageDejaPrincipalAilleursSurLeMemeGnEstRefuseEnReleve(): void
    {
        $gn = GnFactory::createOne();

        $autreUser = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $autreUser]);
        ParticipantFactory::createOne([
            'gn' => $gn,
            'user' => $autreUser,
            'personnage' => $personnage,
        ]);

        // Un second joueur tente de prendre ce personnage comme relève sur le même GN.
        $user = UserFactory::createOne();
        $participant = ParticipantFactory::createOne(['gn' => $gn, 'user' => $user]);
        $participant->setPersonnageReleve($personnage);

        $violations = $this->validator->validate($participant);

        // Le personnage appartient à un autre joueur ET est déjà engagé : 2 violations.
        static::assertGreaterThanOrEqual(1, $violations->count());
        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }
        static::assertContains('personnageReleve', $paths);
    }

    public function testPersonnageDeSonPropreStockDejaEngageSurLeGnEstRefuse(): void
    {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        // Le joueur l'a déjà engagé comme substitution sur une autre participation du même GN.
        $premiere = ParticipantFactory::createOne(['gn' => $gn, 'user' => $user]);
        $premiere->setPersonnageSubstitution($personnage);
        $this->em->flush();

        $seconde = ParticipantFactory::createOne(['gn' => $gn, 'user' => $user]);
        $seconde->setPersonnage($personnage);

        $violations = $this->validator->validate($seconde);

        static::assertCount(1, $violations);
        static::assertSame('personnage', $violations->get(0)->getPropertyPath());
    }

    public function testMemePersonnageSurDeuxGnDifferentsEstAutorise(): void
    {
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $participantA = ParticipantFactory::createOne(['gn' => GnFactory::createOne(), 'user' => $user]);
        $participantA->setPersonnageReleve($personnage);
        $this->em->flush();

        $participantB = ParticipantFactory::createOne(['gn' => GnFactory::createOne(), 'user' => $user]);
        $participantB->setPersonnageSubstitution($personnage);

        static::assertCount(0, $this->validator->validate($participantB));
    }

    public function testPersonnageDUnAutreJoueurEstRefuse(): void
    {
        $personnage = PersonnageFactory::createOne(['user' => UserFactory::createOne()]);

        $participant = ParticipantFactory::createOne(['user' => UserFactory::createOne()]);
        $participant->setPersonnageSubstitution($personnage);

        $violations = $this->validator->validate($participant);

        static::assertCount(1, $violations);
        static::assertSame('personnageSubstitution', $violations->get(0)->getPropertyPath());
    }

    public function testRepositoryTrouveLaParticipationEngageante(): void
    {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $participant = ParticipantFactory::createOne(['gn' => $gn, 'user' => $user]);
        $participant->setPersonnageReleve($personnage);
        $this->em->flush();

        /** @var ParticipantRepository $repository */
        $repository = $this->em->getRepository(Participant::class);

        static::assertSame($participant->getId(), $repository->findParticipationEngageantPersonnage($gn, $personnage)?->getId());

        // La participation en cours d'édition est ignorée.
        static::assertNull($repository->findParticipationEngageantPersonnage($gn, $personnage, $participant));
    }

    public function testRepositoryListeLesPersonnagesEngagesSurLeGn(): void
    {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne();
        $principal = PersonnageFactory::createOne(['user' => $user]);
        $releve = PersonnageFactory::createOne(['user' => $user]);

        $participant = ParticipantFactory::createOne(['gn' => $gn, 'user' => $user]);
        $participant->setPersonnage($principal);
        $participant->setPersonnageReleve($releve);
        $this->em->flush();

        /** @var ParticipantRepository $repository */
        $repository = $this->em->getRepository(Participant::class);

        $ids = $repository->findPersonnageIdsEngagesSurGn($gn);
        sort($ids);

        $attendus = [$principal->getId(), $releve->getId()];
        sort($attendus);

        static::assertSame($attendus, $ids);
        static::assertSame([], $repository->findPersonnageIdsEngagesSurGn($gn, $participant));
    }
}
