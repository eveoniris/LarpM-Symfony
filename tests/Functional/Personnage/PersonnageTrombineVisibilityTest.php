<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Entity\Membre;
use App\Entity\RenommeHistory;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Entity\User;
use App\Service\PersonnageVisibilityService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Photo d'un personnage : propriétaire, staff, renommée publique, groupe en commun (principal ou transverse).
 */
#[Group('functional')]
class PersonnageTrombineVisibilityTest extends WebTestCase
{
    public function testAnonymeNeVoitRien(): void
    {
        static::bootKernel();
        $cible = PersonnageFactory::createOne(['renomme' => 0]);

        static::assertFalse($this->service()->canViewTrombine($cible));
    }

    public function testEtrangerRefuseProprietaireEtStaffAcceptes(): void
    {
        static::bootKernel();
        $proprietaire = UserFactory::createOne();
        $cible = PersonnageFactory::createOne(['user' => $proprietaire, 'renomme' => 0]);

        $this->connecter(UserFactory::createOne());
        static::assertFalse($this->service()->canViewTrombine($cible));

        $this->connecter($proprietaire);
        static::assertTrue($this->service()->canViewTrombine($cible));

        $this->connecter(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));
        static::assertTrue($this->service()->canViewTrombine($cible));
    }

    public function testRenommeePubliqueEstVisibleParTousLesUtilisateurs(): void
    {
        static::bootKernel();
        $cible = PersonnageFactory::createOne();
        $histoire = (new RenommeHistory())
            ->setRenomme(PersonnageVisibilityService::RENOMME_PUBLIQUE + 1)
            ->setPersonnage($cible)
            ->setDate(new DateTime());
        static::getContainer()->get(EntityManagerInterface::class)->persist($histoire);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        static::getContainer()->get(EntityManagerInterface::class)->refresh($cible);

        $this->connecter(UserFactory::createOne());

        static::assertTrue($this->service()->canViewTrombine($cible));
    }

    public function testMembresDuMemeGroupePrincipalMemeAncien(): void
    {
        static::bootKernel();
        $groupe = GroupeFactory::createOne();
        $ancienGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => GnFactory::createOne()]);
        $cible = PersonnageFactory::createOne(['renomme' => 0]);
        ParticipantFactory::createOne(['user' => UserFactory::createOne(), 'gn' => $ancienGn->getGn(), 'groupeGn' => $ancienGn, 'personnage' => $cible]);

        // Un ancien membre du même groupe (autre GN) voit la photo.
        $autreGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => GnFactory::createOne()]);
        $ancien = UserFactory::createOne();
        ParticipantFactory::createOne(['user' => $ancien, 'gn' => $autreGn->getGn(), 'groupeGn' => $autreGn, 'personnage' => PersonnageFactory::createOne()]);
        $this->connecter($ancien);
        static::assertTrue($this->service()->canViewTrombine($cible));

        // Un joueur d'un autre groupe ne la voit pas.
        $autre = UserFactory::createOne();
        $ailleurs = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => GnFactory::createOne()]);
        ParticipantFactory::createOne(['user' => $autre, 'gn' => $ailleurs->getGn(), 'groupeGn' => $ailleurs, 'personnage' => PersonnageFactory::createOne()]);
        $this->connecter($autre);
        static::assertFalse($this->service()->canViewTrombine($cible));
    }

    public function testMembresDuMemeGroupeTransverse(): void
    {
        static::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $type = (new SecondaryGroupType())->setLabel('Type test');
        $em->persist($type);
        $groupe = (new SecondaryGroup())
            ->setLabel('Guilde test')
            ->setSecondaryGroupType($type);
        $em->persist($groupe);

        $cible = PersonnageFactory::createOne(['renomme' => 0]);
        $voisin = UserFactory::createOne();
        $persoVoisin = PersonnageFactory::createOne(['user' => $voisin, 'renomme' => 0]);
        foreach ([$cible, $persoVoisin] as $personnage) {
            $em->persist(
                (new Membre())
                    ->setPersonnage($personnage)
                    ->setSecondaryGroup($groupe)
                    ->setSecret(false),
            );
        }
        $em->flush();

        $this->connecter($voisin);
        static::assertTrue($this->service()->canViewTrombine($cible));

        $this->connecter(UserFactory::createOne());
        static::assertFalse($this->service()->canViewTrombine($cible));
    }

    private function service(): PersonnageVisibilityService
    {
        return static::getContainer()->get(PersonnageVisibilityService::class);
    }

    private function connecter(User $user): void
    {
        static::getContainer()
            ->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }
}
