<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Aides des listes (interface bêta) : numéro d'ordre des pagineurs d'une même page.
 */
final class ListExtension extends AbstractExtension
{
    /** @var \WeakMap<object, int> */
    private \WeakMap $slots;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
        $this->slots = new \WeakMap();
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('lm_pager_slot', $this->pagerSlot(...)),
        ];
    }

    /**
     * Numéro du pagineur dans la requête courante (1 = premier affiché, en haut de liste).
     */
    public function pagerSlot(): int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return 1;
        }

        $this->slots[$request] = ($this->slots[$request] ?? 0) + 1;

        return $this->slots[$request];
    }
}
