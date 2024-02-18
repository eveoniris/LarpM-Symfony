<?php

namespace App\Helper;

class StringHelper
{
    public static function highlightWord(string $content, string $word, array $options, mixed $key): string
    {
        if (empty($options)) {
            $options = ['class' => 'highlight'];
        }

        if ($css = static::getStringFrom($options['class'] ?? null, $key)) {
            $css = ' class="'.$css.'"';
        }
        if ($styles = static::getStringFrom($options['styles'] ?? null, $key)) {
            $styles = ' style="'.$styles.'"';
        }

        $replace = '<span'.$styles.$css.'>'.$word.'</span>';

        return (string) str_ireplace($word, $replace, $content);
    }

    public static function getStringFrom(null|string|array $content, int|string $key = null): string
    {
        if (null === $content) {
            return '';
        }

        if (\is_array($content[$key])) {
            $content = $content[$key];
        } elseif (!\is_array($content)) {
            $content = [$content];
        }

        return implode(', ', $content);
    }

    public static function highlightWords(string $content, array $words, array $options): string
    {
        foreach ($words as $key => $word) {
            $content = static::highlightWord($content, $word, $options, $key);
        }

        return (string) $content;
    }
}
