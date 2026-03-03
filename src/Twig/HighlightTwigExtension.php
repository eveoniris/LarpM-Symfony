<?php

declare(strict_types=1);

namespace App\Twig;

use App\Helper\StringHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class HighlightTwigExtension extends AbstractExtension
{
    /** @return list<TwigFilter> */
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', [$this, 'highlightFilter'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return array<int, string>|string|null
     */
    public function highlightFilter(mixed $text, array|string|null $words, array $options = []): array|string|null
    {
        if (!$words || !$text) {
            return (string) $text;
        }

        return StringHelper::highlightWords((string) $text, \is_array($words) ? $words : [(string) $words], $options);
    }

    public function getName(): string
    {
        return 'highlight_twig_extension';
    }
}
