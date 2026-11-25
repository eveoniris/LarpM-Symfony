<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Verrou des achats et choix du personnage (API joueur) : groupe verrouillé, ou sans groupe date de
 * verrouillage du GN passée.
 */
#[Group('unit')]
final class PersonnageVerrouTest extends TestCase
{
    public function testSansParticipationRienNEstVerrouille(): void
    {
        static::assertFalse((new Personnage())->isModificationVerrouillee());
    }

    public function testAvecGroupeSeulLeVerrouDuGroupeCompte(): void
    {
        $personnage = $this->personnage(new DateTime('-1 day'), groupeVerrouille: false);
        static::assertFalse($personnage->isModificationVerrouillee());

        $personnage = $this->personnage(null, groupeVerrouille: true);
        static::assertTrue($personnage->isModificationVerrouillee());
    }

    public function testSansGroupeLaDateDeVerrouillageDuGnCompte(): void
    {
        $maintenant = new DateTimeImmutable('2026-06-01 12:00');

        static::assertFalse($this->personnage(null)->isModificationVerrouillee($maintenant));
        static::assertFalse($this->personnage(new DateTime('2026-06-02'))->isModificationVerrouillee($maintenant));
        static::assertTrue($this->personnage(new DateTime('2026-06-01 12:00'))->isModificationVerrouillee($maintenant));
        static::assertTrue($this->personnage(new DateTime('2026-05-01'))->isModificationVerrouillee($maintenant));
    }

    private function personnage(?DateTime $dateVerrouillage, ?bool $groupeVerrouille = null): Personnage
    {
        $gn = new Gn();
        $gn->setDateVerrouillage($dateVerrouillage);

        $participant = new Participant();
        $participant->setGn($gn);

        if (null !== $groupeVerrouille) {
            $groupe = new Groupe();
            $groupe->setLock($groupeVerrouille);
            $groupeGn = new GroupeGn();
            $groupeGn->setGn($gn);
            $groupeGn->setGroupe($groupe);
            $participant->setGroupeGn($groupeGn);
        }

        $personnage = new Personnage();
        $personnage->addParticipant($participant);

        return $personnage;
    }
}
