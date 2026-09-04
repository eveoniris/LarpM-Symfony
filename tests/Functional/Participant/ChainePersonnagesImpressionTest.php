<?php

declare(strict_types=1);

namespace App\Tests\Functional\Participant;

use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\EtatCivilFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\PersonnageSecondaireFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Fiche imprimée : elle est lue sur table de jeu, elle doit dire quels
 * personnages le joueur peut incarner et à quel titre.
 *
 * Régression : le bloc « Personnage Secondaire » utilisait un elseif, l'archétype
 * masquait donc totalement la relève dès qu'il était renseigné.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class ChainePersonnagesImpressionTest extends WebTestCase
{
    public function testLaFicheListeLesQuatreRolesEtLeurType(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte(substitutionActive: true);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));
        $crawler = $client->request('GET', '/groupe/' . $contexte['groupe']->getId() . '/print/perso');

        static::assertResponseIsSuccessful();

        $texte = $crawler->filter('body')->text();

        // L'archétype ne doit plus masquer la relève : les deux figurent.
        static::assertStringContainsString('Personnage de relève', $texte);
        static::assertStringContainsString('Releve', $texte);
        static::assertStringContainsString('Archétype de secours', $texte);
        static::assertStringContainsString('Soldat', $texte);
        static::assertStringContainsString('Personnage de substitution', $texte);
        static::assertStringContainsString('Substitut', $texte);
    }

    public function testLaFicheMasqueLaSubstitutionSiLOpusNeLaProposePas(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte(substitutionActive: false);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));
        $crawler = $client->request('GET', '/groupe/' . $contexte['groupe']->getId() . '/print/perso');

        $texte = $crawler->filter('body')->text();
        static::assertStringNotContainsString('Personnage de substitution', $texte);
        static::assertStringContainsString('Personnage de relève', $texte);
    }

    public function testLaFicheSignaleUnRoleNonChoisi(): void
    {
        $client = static::createClient();
        $contexte = $this->contexte(substitutionActive: false, avecReleve: false, avecArchetype: false);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));
        $crawler = $client->request('GET', '/groupe/' . $contexte['groupe']->getId() . '/print/perso');

        static::assertStringContainsString('NON CHOISI', $crawler->filter('body')->text());
    }

    /**
     * @return array{groupe: object}
     */
    private function contexte(
        bool $substitutionActive,
        bool $avecReleve = true,
        bool $avecArchetype = true,
    ): array {
        EspeceFactory::createOne(['nom' => 'Humain']);

        $gn = GnFactory::createOne(['substitutionActive' => $substitutionActive, 'actif' => true]);
        $user = UserFactory::createOne(['etatCivil' => EtatCivilFactory::createOne()]);
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);

        $attributs = [
            'gn' => $gn,
            'user' => $user,
            'groupeGn' => $groupeGn,
            'billet' => BilletFactory::createOne(['gn' => $gn, 'user' => $user]),
            'personnage' => PersonnageFactory::createOne(['user' => $user, 'nom' => 'Principal', 'groupe' => $groupe]),
            'personnageSubstitution' => PersonnageFactory::createOne(['user' => $user, 'nom' => 'Substitut']),
        ];

        if ($avecReleve) {
            $attributs['personnageReleve'] = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Releve']);
        }

        if ($avecArchetype) {
            $attributs['personnageSecondaire'] = PersonnageSecondaireFactory::createOne([
                'classe' => ClasseFactory::createOne(['label_masculin' => 'Soldat', 'label_feminin' => 'Soldate']),
            ]);
        }

        ParticipantFactory::createOne($attributs);

        return ['groupe' => $groupe];
    }
}
