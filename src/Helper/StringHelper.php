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

        return static::splitTag($word, $replace, $content);
        // return (string)str_ireplace($word, $replace, $content);
    }

    public static function getStringFrom(string|array|null $content, int|string|null $key = null): string
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

    public static function splitTag($from, $to, $txt, bool $outterHtml = true)
    {
        return preg_replace_callback(
            '#((?:(?!<[/a-z]).)*)([^>]*>|$)#si',
            static fn ($capture) => $outterHtml
                ? str_ireplace($from, $to, $capture[1]).$capture[2]
                : $capture[1].str_ireplace($from, $to, $capture[2]),
            $txt
        );
    }
}
