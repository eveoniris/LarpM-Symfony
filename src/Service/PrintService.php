<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

/**
 * Génération en flux des fiches imprimables.
 *
 * Le rendu se fait élément par élément avec vidage de l'EntityManager entre
 * chaque fragment, afin de borner l'empreinte mémoire (les fragments hydratent
 * et créent de nombreuses entités transitoires via les services métier).
 * Indispensable pour imprimer un grand nombre de fiches sans dépasser la
 * limite mémoire de l'hébergement mutualisé.
 */
class PrintService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Génère en StreamedResponse les fiches imprimables d'une liste de personnages.
     *
     * @param list<int> $personnageIds liste d'identifiants (et non d'entités, pour permettre le clear de l'EM)
     * @param string    $fragment      template du fragment à rendre par personnage (ex. personnage/fragment/enveloppe.twig)
     */
    public function streamPersonnageFiches(array $personnageIds, string $fragment, string $title = ''): StreamedResponse
    {
        $repository = $this->entityManager->getRepository(Personnage::class);

        return $this->streamFragments($personnageIds, $repository->find(...), static fn (Personnage $personnage): array => ['personnage' => $personnage, 'extends' => false], $fragment, $title);
    }

    /**
     * Génère en StreamedResponse une suite de fragments, un par identifiant.
     *
     * Pour chaque id : (re)chargement frais de l'entité, rendu du fragment, puis
     * vidage de l'EntityManager. Le travail sur des identifiants (et non des
     * entités déjà hydratées) est ce qui permet ce clear sans effet de bord.
     *
     * @param list<int>                              $ids
     * @param Closure(int): ?object                  $load         recharge l'entité gérée à partir de son id
     * @param Closure(object): array<string, mixed>  $buildContext construit le contexte Twig du fragment
     */
    public function streamFragments(array $ids, Closure $load, Closure $buildContext, string $fragment, string $title = ''): StreamedResponse
    {
        return new StreamedResponse(function () use ($ids, $load, $buildContext, $fragment, $title): void {
            echo $this->twig->render('_partials/print_head.twig', ['printTitle' => $title]);

            $i = 0;
            foreach ($ids as $id) {
                $entity = $load($id);
                if (null === $entity) {
                    continue;
                }

                echo $this->twig->render($fragment, $buildContext($entity));
                echo '<hr class="print-page-break"/>';

                // Libère les entités hydratées et transitoires de ce fragment.
                $this->entityManager->clear();
                $this->flushOutput();
                if (0 === (++$i % 10)) {
                    gc_collect_cycles();
                }
            }

            echo $this->twig->render('_partials/print_foot.twig');
            $this->flushOutput();
        });
    }

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
