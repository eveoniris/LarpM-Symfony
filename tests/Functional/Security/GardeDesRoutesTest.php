<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Toute action de contrôleur doit porter un #[IsGranted] (sur la méthode ou la classe), sauf les routes
 * listées ici, dont le contrôle est fait dans le code ou qui sont volontairement ouvertes.
 *
 * Une nouvelle route sans #[IsGranted] fait échouer ce test : ajouter la garde, ou l'inscrire ci-dessous
 * avec le contrôle qui la protège.
 */
#[Group('functional')]
class GardeDesRoutesTest extends KernelTestCase
{
    /**
     * Routes sans #[IsGranted], par raison.
     */
    private const array SANS_ATTRIBUT = [
        // Pages publiques (règle PUBLIC_ACCESS de security.yaml, voir MatriceAccesAnonymeTest).
        'publique' => [
            'access_denied',
            'app_login',
            'app_logout',
            'app_rule_detail',
            'classe.image',
            'classe.index',
            'classe.list',
            'competence.list',
            'discord.interactions',
            'homepage',
            'legal',
            'religion.blason',
            'religion.list',
            'rule.detail',
            'rule.document',
            'rule.list',
            'rules.list',
            'user.confirm-email',
            'user.forgot-password',
            'user.register',
            'user.register.resend',
            'user.reset-password',
            'world',
            'world.countries.json',
            'world.fiefs.json',
            'world.regions.json',
        ],
        // Compte de l'utilisateur connecté (agit sur getUser(), sans identifiant d'un autre compte).
        'compte' => [
            'user.etatCivil',
            'user.fedegn',
            'user.gn.participe',
            'user.gn.validationci',
            'user.new-step1',
            'user.new-step3',
            'user.new-step4',
            'user.restriction',
        ],
        // Contrôle de propriété dans l'action (hasAccess, checkHasAccess, canManageGroup, visibilité).
        'propriete' => [
            'background.detail',
            'competence.document',
            'debriefing.detail',
            'debriefing.document',
            'groupeGn.demande.accept',
            'groupeGn.demande.refuse',
            'groupeGn.joueur.add',
            'groupeGn.list',
            'groupeGn.participants.remove',
            'groupeGn.placeAvailable',
            'groupeGn.update',
            'groupeGn.wargame',
            'personnage.competence.detail',
            'personnage.competence.document',
            'personnage.connaissance.detail',
            'personnage.connaissance.document',
            'personnage.document.detail',
            'personnage.domaine',
            'personnage.export',
            'personnage.langue.document',
            'personnage.langueAncienne',
            'personnage.langueCommune',
            'personnage.langueCourante',
            'personnage.loi.document',
            'personnage.magie',
            'personnage.potion.detail',
            'personnage.potion.document',
            'personnage.priere.detail',
            'personnage.religion.add',
            'personnage.religion.description',
            'personnage.sort.choose',
            'personnage.sort.detail',
            'personnage.sort.document',
            'personnage.technologie',
            'personnage.technologie.document',
            'personnage.trombine',
            'technologie.detail',
            'technologie.document',
            'user.personnage.add',
            'user.personnage.list',
            'user.personnageDefault',
        ],
        // Fiche d'un utilisateur : visible des joueurs, l'adresse e-mail n'y est montrée qu'à lui et à l'équipe.
        'fiche' => ['user.detail', 'user.view'],
        // Page de développement, enregistrée seulement en environnement dev (#[When('dev')]).
        'dev' => ['dev'],
    ];

    public function testToutesLesRoutesSontGardeesOuListees(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get(RouterInterface::class);
        $listees = array_merge(...array_values(self::SANS_ATTRIBUT));

        $sansGarde = [];
        foreach ($router->getRouteCollection()->all() as $nom => $route) {
            $methode = $this->methode($route->getDefault('_controller'));
            if (null === $methode || str_starts_with($route->getPath(), '/api') || $this->estGardee($methode)) {
                continue;
            }

            $sansGarde[] = $nom;
        }

        $nouvelles = array_values(array_diff($sansGarde, $listees));
        static::assertSame([], $nouvelles, "Routes sans #[IsGranted] ni justification :\n" . implode("\n", $nouvelles));
    }

    private function methode(mixed $controleur): ?ReflectionMethod
    {
        if (!\is_string($controleur) || !str_starts_with($controleur, 'App\\') || !str_contains($controleur, '::')) {
            return null;
        }

        [$classe, $nom] = explode('::', $controleur, limit: 2);

        return method_exists($classe, $nom) ? new ReflectionMethod($classe, $nom) : null;
    }

    private function estGardee(ReflectionMethod $methode): bool
    {
        return [] !== $methode->getAttributes(IsGranted::class) || [] !== $methode->getDeclaringClass()->getAttributes(IsGranted::class);
    }
}
