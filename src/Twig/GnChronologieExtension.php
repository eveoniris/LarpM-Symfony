<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\GnChronologieService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GnChronologieExtension extends AbstractExtension
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly GnChronologieService $chronologieService,
    ) {
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gn_chronologie', $this->chronologie(...)),
        ];
    }

    /** @return array<string, mixed> */
    public function chronologie(): array
    {
        return $this->cache ??= $this->chronologieService->construire();
    }
}
