<?php

declare(strict_types=1);

namespace App\Tests\Functional\Participant;

use App\Entity\LogAction;
use App\Entity\Participant;
use App\Enum\LogActionType;
use App\Enum\PersonnageRoleType;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\EtatCivilFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Traçabilité : qui a modifié quoi sur la chaîne de personnages, et qui a
 * verrouillé ou déverrouillé un groupe.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class TracabiliteChainePersonnagesTest extends WebTestCase
{
    #[TestWith(['personnageReleve', PersonnageRoleType::RELEVE])]
    #[TestWith(['personnageSubstitution', PersonnageRoleType::SUBSTITUTION])]
    public function testLeChoixDUnPersonnageAlternatifEstJournalise(string $route, PersonnageRoleType $role): void
    {
        $client = static::createClient();
        [$participant, $user] = $this->contexte($client);

        $cible = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Cible']);

        $crawler = $client->request('GET', sprintf('/participant/%d/%s', $participant->getId(), $route));
        $form = $crawler->selectButton('Enregistrer')->form();
        $form[sprintf('participant_personnage_alternatif[%s]', $role->field())] = (string) $cible->getId();
        $client->submit($form);

        $log = $this->dernierLog(LogActionType::PERSONNAGE_ROLE_CHANGE);

        static::assertNotNull($log, 'Aucune entrée de journal pour le changement de rôle.');
        static::assertSame($user->getId(), $log->getUser()?->getId());
        static::assertSame($role->value, $log->getData()['data']['role']);
        static::assertNull($log->getData()['data']['avant']);
        static::assertSame($cible->getId(), $log->getData()['data']['apres']);
        static::assertSame($participant->getId(), $log->getData()['data']['participant']);
    }

    public function testUnEnregistrementSansChangementNeJournalisePas(): void
    {
        $client = static::createClient();
        [$participant] = $this->contexte($client);

        $crawler = $client->request('GET', sprintf('/participant/%d/personnageReleve', $participant->getId()));
        $client->submit($crawler->selectButton('Enregistrer')->form());

        static::assertNull($this->dernierLog(LogActionType::PERSONNAGE_ROLE_CHANGE));
    }

    public function testLeVerrouillageEtLeDeverrouillageSontJournalises(): void
    {
        $client = static::createClient();

        $groupe = GroupeFactory::createOne(['nom' => 'Les Corbeaux', 'lock' => false]);
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($scenariste);

        $client->request('GET', '/groupe/' . $groupe->getId() . '/lock');

        $log = $this->dernierLog(LogActionType::GROUPE_LOCK);
        static::assertNotNull($log, 'Le verrouillage du groupe n\'est pas journalisé.');
        static::assertSame($scenariste->getId(), $log->getUser()?->getId());
        static::assertSame($groupe->getId(), $log->getData()['data']['groupe']);
        static::assertSame('Les Corbeaux', $log->getData()['data']['nom']);

        $client->request('GET', '/groupe/' . $groupe->getId() . '/unlock');

        $logUnlock = $this->dernierLog(LogActionType::GROUPE_UNLOCK);
        static::assertNotNull($logUnlock, 'Le déverrouillage du groupe n\'est pas journalisé.');
        static::assertSame($scenariste->getId(), $logUnlock->getUser()?->getId());
    }

    private function dernierLog(LogActionType $type): ?LogAction
    {
        return static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(LogAction::class)
            ->findOneBy(['type' => $type->value], ['id' => 'DESC']);
    }

    /**
     * @return array{0: Participant, 1: object}
     */
    private function contexte(KernelBrowser $client): array
    {
        $gn = GnFactory::createOne(['substitutionActive' => true]);
        $user = UserFactory::createOne(['etatCivil' => EtatCivilFactory::createOne()]);

        $participant = ParticipantFactory::createOne([
            'gn' => $gn,
            'user' => $user,
            'groupeGn' => GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]),
            'billet' => BilletFactory::createOne(['gn' => $gn, 'user' => $user]),
            'personnage' => PersonnageFactory::createOne(['user' => $user, 'nom' => 'Principal']),
        ]);

        $client->loginUser($user);

        return [$participant, $user];
    }
}
