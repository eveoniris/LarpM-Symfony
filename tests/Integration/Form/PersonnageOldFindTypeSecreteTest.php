<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form;

use App\Entity\Classe;
use App\Entity\Participant;
use App\Form\PersonnageOldFindType;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Garde à la reprise : un personnage qui porte encore la classe secrète n'est pas proposé, il faut d'abord changer sa classe.
 */
#[Group('integration')]
class PersonnageOldFindTypeSecreteTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @return list<string> identifiants proposés dans la liste de reprise
     */
    private function propositions(Participant $participant): array
    {
        $form = static::getContainer()->get(FormFactoryInterface::class)->create(PersonnageOldFindType::class, $participant, ['csrf_protection' => false]);

        return array_values(array_map(static fn ($choix): string => (string) $choix->value, $form->get('personnage')->createView()->vars['choices']));
    }

    public function testUnPersonnageDeClasseSecreteNEstPasPropose(): void
    {
        $user = UserFactory::createOne();
        $ordinaire = PersonnageFactory::createOne(['user' => $user, 'classe' => ClasseFactory::createOne()]);
        $heritier = PersonnageFactory::createOne(['user' => $user, 'classe' => ClasseFactory::createOne(['secrete' => true])]);
        $participant = ParticipantFactory::createOne(['gn' => GnFactory::createOne(), 'user' => $user]);

        $propositions = $this->propositions($participant);

        static::assertContains((string) $ordinaire->getId(), $propositions);
        static::assertNotContains((string) $heritier->getId(), $propositions);
    }
}
