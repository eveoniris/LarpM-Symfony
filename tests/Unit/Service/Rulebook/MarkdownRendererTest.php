<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Rulebook;

use App\Service\Rulebook\MarkdownRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new MarkdownRenderer();
    }

    #[DataProvider('entreesVides')]
    public function testEntreeVideDonneUneChaineVide(?string $entree): void
    {
        $this->assertSame('', $this->renderer->rendre($entree));
    }

    /** @return array<string, array{?string}> */
    public static function entreesVides(): array
    {
        return ['null' => [null], 'vide' => [''], 'blanc' => ["  \n "]];
    }

    public function testMarkdownDeBase(): void
    {
        $html = $this->renderer->rendre("# Titre\n\nDu **gras** et une liste :\n\n- un\n- deux");

        $this->assertStringContainsString('<h1>Titre</h1>', $html);
        $this->assertStringContainsString('<strong>gras</strong>', $html);
        $this->assertStringContainsString('<li>deux</li>', $html);
    }

    public function testTableGithub(): void
    {
        $html = $this->renderer->rendre("| Niveau | Coût |\n|---|---|\n| 1 | 3 |");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Niveau</th>', $html);
    }

    /** @return array<string, array{string, list<string>}> */
    public static function injections(): array
    {
        return [
            'script' => ['<script>alert(1)</script>', ['<script']],
            'balise img avec onerror' => ['<img src=x onerror=alert(1)>', ['<img', 'onerror=alert']],
            'iframe' => ['<iframe src="https://exemple.test"></iframe>', ['<iframe']],
            'lien javascript' => ['[clic](javascript:alert(1))', ['href="javascript:']],
            'lien data' => ['[clic](data:text/html;base64,PHNjcmlwdD4=)', ['href="data:']],
            'lien vbscript' => ['[clic](vbscript:msgbox(1))', ['href="vbscript:']],
            'image javascript' => ['![x](javascript:alert(1))', ['src="javascript:']],
            'html dans un paragraphe' => ['Texte <b onclick="x()">gras</b>', ['onclick', '<b ']],
        ];
    }

    /** @param list<string> $interdits */
    #[DataProvider('injections')]
    public function testAucunHtmlOuLienDangereux(string $entree, array $interdits): void
    {
        $html = $this->renderer->rendre($entree);

        foreach ($interdits as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase($interdit, $html);
        }
    }

    public function testLienHttpsConserve(): void
    {
        $this->assertStringContainsString('href="https://exemple.test/regle"', $this->renderer->rendre('[règle](https://exemple.test/regle)'));
    }
}
