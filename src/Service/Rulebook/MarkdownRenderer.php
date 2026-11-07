<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Rend le markdown du rulebook en HTML sans jamais laisser passer de HTML saisi : les balises sont retirées et les
 * liens dangereux (javascript:, data:, vbscript:) sont neutralisés. Tables et listes de tâches du markdown GitHub sont gérées.
 */
final class MarkdownRenderer
{
    private ?MarkdownConverter $convertisseur = null;

    public function rendre(?string $markdown): string
    {
        if (null === $markdown || '' === trim($markdown)) {
            return '';
        }

        return $this->convertisseur()->convert($markdown)->getContent();
    }

    private function convertisseur(): MarkdownConverter
    {
        if (null === $this->convertisseur) {
            $environnement = new Environment([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);
            $environnement->addExtension(new CommonMarkCoreExtension());
            $environnement->addExtension(new GithubFlavoredMarkdownExtension());

            $this->convertisseur = new MarkdownConverter($environnement);
        }

        return $this->convertisseur;
    }
}
