<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Le droit de responsable est porté par la session (groupe_gn) et par le groupe :
 * il doit suivre le nouveau responsable et être retiré à l'ancien.
 */
#[Group('unit')]
final class GroupeGnResponsableTest extends TestCase
{
    public function testChangerResponsableTransmetLeDroitAuGroupe(): void
    {
        [$groupeGn, $groupe] = $this->session();
        $ancien = $this->participant(1, 10);
        $nouveau = $this->participant(2, 20);

        $groupeGn->setResponsable($ancien);
        static::assertSame($ancien->getUser(), $groupe->getUserRelatedByResponsableId());

        $groupeGn->setResponsable($nouveau);
        static::assertSame($nouveau, $groupeGn->getResponsable());
        static::assertSame($nouveau->getUser(), $groupe->getUserRelatedByResponsableId());
    }

    public function testRetirerResponsableLuiRetireTousLesDroits(): void
    {
        [$groupeGn, $groupe] = $this->session();
        $responsable = $this->participant(1, 10);
        $groupeGn->setResponsable($responsable);

        static::assertTrue($groupeGn->retirerResponsableSi($responsable));
        static::assertNull($groupeGn->getResponsable());
        static::assertNull($groupe->getUserRelatedByResponsableId());
        static::assertFalse($groupeGn->hasResponsable());
    }

    public function testRetirerResponsableDuGroupeSeulementLaisseLaSession(): void
    {
        [$groupeGn, $groupe] = $this->session();
        $chefSession = $this->participant(1, 10);
        $ancienChefGroupe = $this->participant(2, 20);
        $groupeGn->setResponsable($chefSession);
        $groupe->setUserRelatedByResponsableId($ancienChefGroupe->getUser());

        static::assertTrue($groupeGn->retirerResponsableSi($ancienChefGroupe));
        static::assertSame($chefSession, $groupeGn->getResponsable());
        static::assertNull($groupe->getUserRelatedByResponsableId());
        static::assertTrue($groupeGn->hasResponsable());
    }

    public function testRetirerUnSimpleMembreNeChangeRien(): void
    {
        [$groupeGn, $groupe] = $this->session();
        $responsable = $this->participant(1, 10);
        $membre = $this->participant(2, 20);
        $groupeGn->setResponsable($responsable);

        static::assertFalse($groupeGn->retirerResponsableSi($membre));
        static::assertSame($responsable, $groupeGn->getResponsable());
        static::assertSame($responsable->getUser(), $groupe->getUserRelatedByResponsableId());
    }

    /**
     * @return array{GroupeGn, Groupe}
     */
    private function session(): array
    {
        $groupe = new Groupe();
        $groupeGn = new GroupeGn();
        $groupeGn->setGroupe($groupe);

        return [$groupeGn, $groupe];
    }

    private function participant(int $id, int $userId): Participant
    {
        $participant = new Participant();
        $participant->setId($id);
        $participant->setUser((new User())->setId($userId));

        return $participant;
    }
}
