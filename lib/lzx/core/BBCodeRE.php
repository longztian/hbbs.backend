<?php

declare(strict_types=1);

namespace lzx\core;

class BBCodeRE
{
    const BBCODE = [
        '/\[\/?[b,u]\]/ms'
        => '**',
        '/\[\/?i\]/ms'
        => '*',
        '/\[\/?s\]/ms'
        => '~~',
        '/\[img\="?(.*?)"?\](.*?)\[\/img\]/ms'
        => '![\2](\1)',
        '/\[img\](.*?)\[\/img\]/ms'
        => '![\1](\1)',
        '/\[url\="?(.*?)"?\](.*?)\[\/url\]/ms'
        => '[\2](\1)',
        '/\[url\](.*?)\[\/url\]/ms'
        => '[\1](\1)',
        '/\[size\="?(.*?)"?\](.*?)\[\/size\]/ms'
        => '### \2',
        '/\[color\="?(.*?)"?\](.*?)\[\/color\]/ms'
        => '<span style="color:\1">\2</span>',
        '/\[bgcolor\="?(.*?)"?\](.*?)\[\/bgcolor\]/ms'
        => '<span style="background-color:\1">\2</span>',
        '/\[quote\="?(.*?)"?\]/ms'
        => '> **\1:**  ' . PHP_EOL,
        '/\[list\=(.*?)\](.*?)\[\/list\]/ms'
        => PHP_EOL . '\2' . PHP_EOL,
        '/\[\/?list\]/ms'
        => PHP_EOL,
        '/\[\*\]\s?/ms'
        => ' - ',
        '/\[youtube\](.*?)\[\/youtube\]/ms'
        => '<iframe class="youtube" src="//www.youtube.com/embed/\1" frameborder="0" allowfullscreen></iframe>',
    ];

    private static function escape(array $matches): string
    {
        $code = $matches[1];
        $code = str_replace(['[', ']'], ['&#91;', '&#93;'], $code);
        return '```' . PHP_EOL . $code . PHP_EOL . '```';
    }

    public static function parse(string $text): string
    {
        if (strpos($text, '[/') === false) { // if no colse tag, don't borther
            return $text;
        }

        // BBCode [code]
        $text = preg_replace_callback('/\[code\](.*?)\[\/code\]/ms', [__CLASS__, 'escape'], $text);

        $unclosed = ((int) preg_match_all('/\[quote\="?(.*?)"?\]/ms', $text, $matches)) + substr_count($text, '[quote]') - substr_count($text, '[/quote]');

        if ($unclosed < 0) {
            $text = str_repeat('[quote]', (-$unclosed)) . $text;
        }
        if ($unclosed > 0) {
            $text = $text . str_repeat('[/quote]', $unclosed);
        }

        $text = str_replace(['[quote]', '[/quote]'], ['> ', PHP_EOL . PHP_EOL], $text);

        $text = preg_replace(array_keys(self::BBCODE), array_values(self::BBCODE), $text);

        return $text;
    }
}
