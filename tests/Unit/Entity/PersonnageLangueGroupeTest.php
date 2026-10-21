<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Groupe;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Entity\Territoire;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class PersonnageLangueGroupeTest extends TestCase
{
    private function groupeAvecLangue(?Langue $langue): Groupe
    {
        $pays = (new Territoire())->setLangue($langue);

        return (new Groupe())->setTerritoire($pays);
    }

    public function testAjouteLaLangueDuPaysDuGroupeApresLaLangueDOrigine(): void
    {
        $iranistan = (new Langue())
            ->setId(5)
            ->setLabel('Iranistani');
        $cimmerien = (new Langue())
            ->setId(11)
            ->setLabel('Cimmérien');
        $personnage = new Personnage();
        $personnage->addLangueSource($iranistan, 'ORIGINE');

        $ajoutee = $personnage->addLangueGroupe($this->groupeAvecLangue($cimmerien));

        static::assertNotNull($ajoutee);
        static::assertSame('GROUPE', $ajoutee->getSource());
        static::assertCount(2, $personnage->getPersonnageLangues());
    }

    public function testNeFaitRienSiDejaConnueSansLangueOuSansGroupe(): void
    {
        $cimmerien = (new Langue())
            ->setId(11)
            ->setLabel('Cimmérien');
        $personnage = new Personnage();
        $personnage->addLangueSource($cimmerien, 'ORIGINE');

        static::assertNull($personnage->addLangueGroupe($this->groupeAvecLangue($cimmerien)));
        static::assertNull($personnage->addLangueGroupe($this->groupeAvecLangue(null)));
        static::assertNull($personnage->addLangueGroupe(null));
        static::assertCount(1, $personnage->getPersonnageLangues());
    }
}
