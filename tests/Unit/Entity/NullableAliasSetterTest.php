<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Billet;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\SecondaryGroup;
use App\Entity\Territoire;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Régression : plusieurs entités concrètes définissent un setter "alias" (ex.
 * `Groupe::setScenariste()`) qui enveloppe un setter Base* nullable (ex.
 * `BaseGroupe::setUserRelatedByScenaristeId(?User $User = null)`) mais avec un
 * paramètre non-nullable. Quand un formulaire avec `required => false` laisse le
 * champ vide, le PropertyAccessor du composant Form résout le setter par le nom du
 * champ, appelle l'alias avec `null`, et lève un TypeError fatal avant toute
 * validation (ex. crash prod sur `/groupe/update/175` via `Groupe::setScenariste`).
 *
 * Note : `GroupeAllie`/`GroupeEnemy`/`Restriction` avaient le même mismatch apparent,
 * mais leur colonne DB est réellement `nullable: false` (relation obligatoire) et leur
 * propriété Base* est elle-même non-nullable — les rendre nullables n'aurait rien
 * corrigé (le crash se serait juste déplacé au niveau de l'assignation de propriété
 * dans le setter Base*), donc ces 3 cas n'ont volontairement pas été modifiés.
 */
#[Group('unit')]
class NullableAliasSetterTest extends TestCase
{
    public function testGroupeSetScenaristeAcceptsNull(): void
    {
        $groupe = new Groupe();
        $groupe->setScenariste(null);

        self::assertNull($groupe->getScenariste());
    }

    public function testTerritoireSetLanguePrincipaleAcceptsNull(): void
    {
        $territoire = new Territoire();
        $territoire->setLanguePrincipale(null);

        self::assertNull($territoire->getLangue());
    }

    public function testTerritoireSetReligionPrincipaleAcceptsNull(): void
    {
        $territoire = new Territoire();
        $territoire->setReligionPrincipale(null);

        self::assertNull($territoire->getReligion());
    }

    public function testBilletSetCreateurAcceptsNull(): void
    {
        $billet = new Billet();
        $billet->setCreateur(null);

        self::assertNull($billet->getCreateur());
    }

    public function testGroupeGnSetResponsableAcceptsNull(): void
    {
        $groupeGn = new GroupeGn();
        $groupeGn->setResponsable(null);

        self::assertNull($groupeGn->getParticipant());
    }

    public function testSecondaryGroupSetResponsableAcceptsNull(): void
    {
        $secondaryGroup = new SecondaryGroup();
        $secondaryGroup->setResponsable(null);

        self::assertNull($secondaryGroup->getPersonnage());
    }
}
