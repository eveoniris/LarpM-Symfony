<?php

namespace App\Twig;

use App\Helper\StringHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class HighlightTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', [$this, 'highlightFilter'], ['is_safe' => ['html']]),
        ];
    }

    public function highlightFilter($text, $words, array $options = []): array|string|null
    {
        if (!$words || !$text) {
            return (string) $text;
        }

        if (\is_string($words) || \is_numeric($words)) {
            $words = [(string) $words];
        }

        return StringHelper::highlightWords($text, $words, $options);
    }

    public function getName(): string
    {
        return 'highlight_twig_extension';
    }
}
