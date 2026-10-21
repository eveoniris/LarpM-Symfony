<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Gn;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\Sort;
use App\Enum\TriggerType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class PersonnageTriggerSoftDeleteTest extends TestCase
{
    private function addTrigger(Personnage $personnage, TriggerType $tag, ?Gn $gn = null): PersonnageTrigger
    {
        $trigger = (new PersonnageTrigger())
            ->setPersonnage($personnage)
            ->setTag($tag)
            ->setGn($gn);
        $personnage->addPersonnageTrigger($trigger);

        return $trigger;
    }

    public function testConsumedTriggerIsHiddenButKeptInHistory(): void
    {
        $personnage = new Personnage();
        $trigger = $this->addTrigger($personnage, TriggerType::SORT_APPRENTI);
        $this->addTrigger($personnage, TriggerType::ALCHIMIE_APPRENTI);

        static::assertTrue($personnage->hasTrigger(TriggerType::SORT_APPRENTI));

        $trigger->consume('Boule de feu');

        static::assertFalse($personnage->hasTrigger(TriggerType::SORT_APPRENTI));
        static::assertNull($personnage->getTrigger(TriggerType::SORT_APPRENTI));
        static::assertTrue($personnage->hasTrigger(TriggerType::ALCHIMIE_APPRENTI));
        static::assertCount(1, $personnage->getActiveTriggers());
        static::assertCount(1, $personnage->getPersonnageTriggers());
        static::assertCount(2, $personnage->getAllPersonnageTriggers());
    }

    public function testConsumeKeepsChoiceAndDate(): void
    {
        $sort = (new Sort())->setLabel('Armure');
        $trigger = $this->addTrigger(new Personnage(), TriggerType::SORT_INITIE);

        static::assertFalse($trigger->isDeleted());

        $trigger->consume(sort: $sort);

        static::assertTrue($trigger->isDeleted());
        static::assertNotNull($trigger->getDeletedAt());
        static::assertSame($sort, $trigger->getSort());
        static::assertSame('Armure', $trigger->getChoiceLabel());
    }

    public function testConsumeKeepsFirstDeletionDate(): void
    {
        $trigger = $this->addTrigger(new Personnage(), TriggerType::SORT_EXPERT);
        $trigger->consume('premier');
        $date = $trigger->getDeletedAt();

        $trigger->consume('second');

        static::assertSame($date, $trigger->getDeletedAt());
    }
}
