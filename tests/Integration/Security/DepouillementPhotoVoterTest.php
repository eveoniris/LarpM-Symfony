<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\DepouillementPhoto;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Security\Voter\DepouillementPhotoVoter;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Droits sur les photos : staff et dépouilleurs gèrent, le propriétaire de l'enveloppe ne voit que ce qui lui est partagé.
 */
#[Group('integration')]
class DepouillementPhotoVoterTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function photo(Depouillement $depouillement, bool $partage = false): DepouillementPhoto
    {
        $photo = (new DepouillementPhoto())
            ->setChemin('1/1/x.png')
            ->setNomOriginal('x.png')
            ->setMimeType('image/png')
            ->setTaille(10)
            ->setPartage($partage);
        $depouillement->addPhoto($photo);
        $this->em->persist($photo);
        $this->em->flush();

        return $photo;
    }

    private function depouillementJoueur(User $proprietaire, ?User $depouilleur = null): Depouillement
    {
        $participant = ParticipantFactory::createOne(['gn' => GnFactory::createOne(), 'user' => $proprietaire]);
        $depouillement = (new Depouillement())
            ->setGn($participant->getGn())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setParticipant($participant)
            ->setDepouilleur($depouilleur);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    private function peut(User $utilisateur, string $droit, DepouillementPhoto $photo): bool
    {
        static::getContainer()
            ->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($utilisateur, 'main', $utilisateur->getRoles()));

        return static::getContainer()->get('security.authorization_checker')->isGranted($droit, $photo);
    }

    /**
     * @return iterable<string, array{0: list<string>}>
     */
    public static function rolesDuStaff(): iterable
    {
        yield 'scénariste' => [['ROLE_SCENARISTE']];
        yield 'orga' => [['ROLE_ORGA']];
        yield 'admin' => [['ROLE_ADMIN']];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('rolesDuStaff')]
    public function testLeStaffVoitEtGere(array $roles): void
    {
        $photo = $this->photo($this->depouillementJoueur(UserFactory::createOne()));
        $staff = UserFactory::createOne(['roles' => ['ROLE_USER', ...$roles]]);

        static::assertTrue($this->peut($staff, DepouillementPhotoVoter::VIEW, $photo));
        static::assertTrue($this->peut($staff, DepouillementPhotoVoter::MANAGE, $photo));
    }

    public function testUnUtilisateurQuelconqueNeVoitRien(): void
    {
        $photo = $this->photo($this->depouillementJoueur(UserFactory::createOne()), partage: true);
        $autre = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);

        static::assertFalse($this->peut($autre, DepouillementPhotoVoter::VIEW, $photo));
        static::assertFalse($this->peut($autre, DepouillementPhotoVoter::MANAGE, $photo));
    }

    public function testLeDepouilleurActuelEtUnAncienDepouilleurGerent(): void
    {
        $actuel = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $ancien = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $depouillement = $this->depouillementJoueur(UserFactory::createOne(), $actuel);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($ancien)
                ->setActionType(DepouillementHistory::ACTION_CREATE),
        );
        $this->em->flush();
        $photo = $this->photo($depouillement);

        static::assertTrue($this->peut($actuel, DepouillementPhotoVoter::MANAGE, $photo));
        static::assertTrue($this->peut($ancien, DepouillementPhotoVoter::MANAGE, $photo));
    }

    public function testLeJoueurNeVoitLaPhotoQueSiElleLuiEstPartageeEtNeLaGerePas(): void
    {
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $depouillement = $this->depouillementJoueur($joueur);
        $nonPartagee = $this->photo($depouillement);
        $partagee = $this->photo($depouillement, partage: true);

        static::assertFalse($this->peut($joueur, DepouillementPhotoVoter::VIEW, $nonPartagee));
        static::assertTrue($this->peut($joueur, DepouillementPhotoVoter::VIEW, $partagee));
        static::assertFalse($this->peut($joueur, DepouillementPhotoVoter::MANAGE, $partagee));
    }

    public function testLeChefDeGroupeVoitLesPhotosPartageesDeLEnveloppeDeGroupe(): void
    {
        $chef = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $gn = GnFactory::createOne();
        $participantChef = ParticipantFactory::createOne(['gn' => $gn, 'user' => $chef]);
        /** @var GroupeGn $groupeGn */
        $groupeGn = GroupeGnFactory::createOne(['gn' => $gn, 'participant' => $participantChef]);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::GROUPE)
            ->setNumero('1')
            ->setGroupeGn($groupeGn);
        $this->em->persist($depouillement);
        $this->em->flush();

        static::assertInstanceOf(Participant::class, $groupeGn->getResponsable());
        static::assertFalse($this->peut($chef, DepouillementPhotoVoter::VIEW, $this->photo($depouillement)));
        static::assertTrue($this->peut($chef, DepouillementPhotoVoter::VIEW, $this->photo($depouillement, partage: true)));
    }
}
