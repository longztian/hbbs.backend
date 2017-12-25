<?php declare(strict_types=1);

namespace lzx\core;

// from drupal bbcode module: 6.x-1.2     tar.gz (40.06 KB) | zip (52.83 KB)     2008-Nov-30
// $Id: bbcode-filter.inc,v 1.66 2008/11/30 08:50:08 naudefj Exp $

class BBCodeRE
{
    private static function escape(string $s): string
    {
        $code = $s[1];
        $code = str_replace(['[', ']'], ['&#91;', '&#93;'], $code);
        return '<code class="code">' . $code . '</code>';
    }

    private static function removeBr(string $s): string
    {
        return str_replace('<br />', '', $s[0]);
    }

    public static function parse(string $text): string
    {
        if (strpos($text, '[/') === false) {// if no colse tag, don't borther
            $text = preg_replace('#(?<=^|[\t\r\n >\(\[\]\|])(https?://[\w\-]+\.([\w\-]+\.)*\w+(:[0-9]+)?(/[^ "\'\(\n\r\t<\)\[\]\|]*)?)((?<![,\.])|(?!\s))#i', '<a href="\1">\1</a>', $text);
            return nl2br($text);
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

        $text = str_replace(['[quote]', '[/quote]'], ['<blockquote>', '</blockquote>'], $text);

        // BBCode to find...
        $bbcode = [
            '/\[b\](.*?)\[\/b\]/ms'
            => '<strong>\1</strong>',
            '/\[i\](.*?)\[\/i\]/ms'
            => '<em>\1</em>',
            '/\[u\](.*?)\[\/u\]/ms'
            => '<span style="text-decoration:underline">\1</span>',
            '/\[s\](.*?)\[\/s\]/ms'
            => '<del>\1</del>',
            '/\[img\="?(.*?)"?\](.*?)\[\/img\]/ms'
            => '<div class="bb-image"><img src="\2" alt="\2" /></div>',
            '/\[img\](.*?)\[\/img\]/ms'
            => '<div class="bb-image"><img src="\1" alt="\1" /></div>',
            '/\[url\="?(.*?)"?\](.*?)\[\/url\]/ms'
            => '<a href="\1">\2</a>',
            '/\[url](.*?)\[\/url\]/ms'
            => '<a href="\1">\1</a>',
            '/\[size\="?(.*?)"?\](.*?)\[\/size\]/ms'
            => '<span style="font-size:\1">\2</span>',
            '/\[color\="?(.*?)"?\](.*?)\[\/color\]/ms'
            => '<span style="color:\1">\2</span>',
            '/\[bgcolor\="?(.*?)"?\](.*?)\[\/bgcolor\]/ms'
            => '<span style="background-color:\1">\2</span>',
            '/\[quote\="?(.*?)"?\]/ms'
            => '<blockquote data-author="\1 :">',
            '/\[list\=(.*?)\](.*?)\[\/list\]/ms'
            => '<ol start="\1">\2</ol>',
            '/\[list\](.*?)\[\/list\]/ms'
            => '<ul>\1</ul>',
            '/\[\*\]\s?(.*?)\n/ms'
            => '<li>\1</li>',
            '/\[youtube\](.*?)\[\/youtube\]/ms'
            => '<iframe class="youtube" src="//www.youtube.com/embed/\1" frameborder="0" allowfullscreen></iframe>'
        ];

        $text = preg_replace(array_keys($bbcode), array_values($bbcode), $text);

        // paragraphs
        //$text = str_replace("\r", "", $text);
        //$text = "<p>" . preg_replace("/(\n){2,}/", "</p><p>", $text) . "</p>";
        $text = nl2br($text);

        $text = preg_replace_callback('/<pre>(.*?)<\/pre>/ms', [__CLASS__, 'removeBr'], $text);
        //$text = preg_replace('/<p><pre>(.*?)<\/pre><\/p>/ms', "<pre>\\1</pre>", $text);

        $text = preg_replace_callback('/<ul>(.*?)<\/ul>/ms', [__CLASS__, 'removeBr'], $text);
        //$text = preg_replace('/<p><ul>(.*?)<\/ul><\/p>/ms', "<ul>\\1</ul>", $text);
        // matches an "xxxx://yyyy" URL at the start of a line, or after a space.
        // xxxx can only be alpha characters.
        // yyyy is anything up to the first space, newline, comma, double quote or <
        $text = preg_replace('#(?<=^|[\t\r\n >\(\[\]\|])([a-z]+?://[\w\-]+\.([\w\-]+\.)*\w+(:[0-9]+)?(/[^ "\'\(\n\r\t<\)\[\]\|]*)?)((?<![,\.])|(?!\s))#i', '<a href="\1">\1</a>', $text);

        return $text;
    }
}
