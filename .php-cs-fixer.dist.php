<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->exclude('var')
    ->exclude('vendor')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return new Config()
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        // ── Base : Symfony (priorité 1) ──────────────────────────────
        '@Symfony' => true,
        '@Symfony:risky' => true,

        // ── Fallback : PSR-12 (priorité 2) ───────────────────────────
        // @Symfony est un sur-ensemble de @PSR12, on l'active quand même
        // pour être explicite et éviter toute régression si @Symfony recule
        '@PSR12' => true,

        // ── PHPUnit (priorité 3) ──────────────────────────────────────
        '@PHPUnit10x0Migration:risky' => true,

        // ── PHP 8.4 migration ─────────────────────────────────────────
        '@PHP8x4Migration' => true,
        '@PHP8x4Migration:risky' => true,

        // ── PHP 8.4 spécifique ────────────────────────────────────────
        // Keep (new Foo())->bar() parentheses: PHPStan's parser does not yet
        // support PHP 8.4 "new without parentheses" even at phpVersion=80400.
        'new_expression_parentheses' => ['use_parentheses' => true],
        'declare_strict_types' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'use_arrow_functions' => true,
        'modernize_types_casting' => true,
        'get_class_to_class_keyword' => true,

        // ── Overrides Symfony si besoin ───────────────────────────────
        'concat_space' => ['spacing' => 'one'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'no_multiline_whitespace_around_double_arrow' => false,
        'no_unneeded_control_parentheses' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => false,
            'import_constants' => false,
        ],
    ])
    ->setFinder($finder);
