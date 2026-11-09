<?php

declare(strict_types=1);

namespace App\Tests\Functional\Competence;

use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * SEC-01 : une compétence secrète n'est ni proposée ni lisible pour un joueur
 * qui ne la possède pas, mais reste accessible à l'organisation.
 */
#[Group('functional')]
#[Group('SEC-01')]
class CompetenceSecreteVisibiliteTest extends WebTestCase
{
    public function testJoueurNeLitPasUneCompetenceSecreteQuIlNePossedePas(): void
    {
        $client = static::createClient();
        $competence = $this->competence(true);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        PersonnageFactory::createOne(['user' => $user]);
        $this->viderIdentityMap();

        $client->loginUser($user);
        $client->request('GET', '/competence/' . $competence->getId());

        static::assertResponseRedirects('/access_denied');
    }

    public function testJoueurLitUneCompetenceNonSecreteProposee(): void
    {
        $client = static::createClient();
        $competence = $this->competence(false);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        PersonnageFactory::createOne(['user' => $user]);
        $this->viderIdentityMap();

        $client->loginUser($user);
        $client->request('GET', '/competence/' . $competence->getId());

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeLitUneCompetenceSecrete(): void
    {
        $client = static::createClient();
        $competence = $this->competence(true);
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        PersonnageFactory::createOne(['user' => $scenariste]);
        $this->viderIdentityMap();

        $client->loginUser($scenariste);
        $client->request('GET', '/competence/' . $competence->getId());

        static::assertResponseIsSuccessful();
    }

    private function competence(bool $secret): object
    {
        return CompetenceFactory::createOne([
            'competenceFamily' => CompetenceFamilyFactory::createOne(),
            'level' => LevelFactory::createOne(),
            'secret' => $secret,
        ]);
    }

    /**
     * Les collections inverses (famille -> compétences) doivent être rechargées depuis la base.
     */
    private function viderIdentityMap(): void
    {
        static::getContainer()->get('doctrine')->getManager()->clear();
    }
}
