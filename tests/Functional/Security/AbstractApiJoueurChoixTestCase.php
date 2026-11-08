<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Domaine;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\Sort;
use App\Entity\User;
use App\Enum\TriggerType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\ReligionFactory;
use App\Tests\Factory\TechnologieFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Outils communs aux tests des choix joueur (triggers) : scénario, jeton, appel JSON.
 */
abstract class AbstractApiJoueurChoixTestCase extends WebTestCase
{
    /**
     * @return array{0: User, 1: Personnage}
     */
    protected function scenario(TriggerType $tag): array
    {
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setTag($tag->value);
        $trigger->setDone(false);
        $em->persist($trigger);
        $em->flush();
        $personnage->addPersonnageTrigger($trigger);

        return [$user, $personnage];
    }

    protected function domaine(): Domaine
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $domaine = new Domaine();
        $domaine->setLabel('Domaine test');
        $domaine->setDescription('');
        $em->persist($domaine);
        $em->flush();

        return $domaine;
    }

    protected function sort(int $niveau): Sort
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $sort = new Sort();
        $sort->setLabel('Sort test ' . $niveau);
        $sort->setNiveau($niveau);
        $sort->setDomaine($this->domaine());
        $sort->setDocumentUrl('');
        $em->persist($sort);
        $em->flush();

        return $sort;
    }

    /**
     * @param array<string, int>|null $corps
     * @param array<string, string>   $auth
     *
     * @return array<string, list<array<string, mixed>>>
     */
    protected function appel(string $methode, Personnage $personnage, string $suffixe, ?array $corps, array $auth): array
    {
        static::getClient()
            ->request(
                $methode,
                '/api/joueur/personnage/' . $personnage->getId() . '/' . $suffixe,
                [],
                [],
                $auth + ['CONTENT_TYPE' => 'application/json'],
                null === $corps ? null : (string) json_encode($corps === [] ? new \stdClass() : $corps),
            );

        $data = json_decode((string) static::getClient()->getResponse()->getContent(), true);

        return \is_array($data) ? $data : [];
    }

    /** @return array<string, string> */
    protected function auth(User $user): array
    {
        static::getContainer()->get(RequestStack::class)->push(Request::create('/'));

        return ['HTTP_AUTHORIZATION' => 'Bearer ' . static::getContainer()->get(JWTTokenManagerInterface::class)->create($user)];
    }
}
