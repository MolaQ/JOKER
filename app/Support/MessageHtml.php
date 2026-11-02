<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Treść wiadomości z edytora: zostawia tylko proste formatowanie (pogrubienie, kursywa, listy, cytat, linki)
 * i wycina wszystko inne razem z atrybutami. Stare wiadomości (zwykły tekst) pokazuje z zachowaniem nowych linii.
 */
class MessageHtml
{
    /** @var list<string> znaczniki, które zostają (bez atrybutów, poza href w linkach) */
    private const ALLOWED = ['p', 'br', 'h2', 'h3', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'mark', 'ul', 'ol', 'li', 'blockquote', 'a'];

    /** @var list<string> znaczniki usuwane razem z zawartością */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'head', 'title'];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if (! self::isHtml($html)) {
            return nl2br(e($html), false);
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('root');

        if (! $root) {
            return e(strip_tags($html));
        }

        self::clean($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        // Puste akapity na końcu (Enter przed wysłaniem) nie są potrzebne.
        return trim((string) preg_replace('#(<p>(\s|<br>)*</p>\s*)+$#u', '', $out));
    }

    /**
     * Do wyświetlenia: HTML z edytora albo stary zwykły tekst.
     */
    public static function render(?string $body): string
    {
        return self::sanitize($body);
    }

    /**
     * Sam tekst (powiadomienia, e-maile, podgląd na liście rozmów).
     */
    public static function plain(?string $body): string
    {
        $body = (string) $body;

        if (! self::isHtml($body)) {
            return trim($body);
        }

        $text = preg_replace(['#<br\s*/?>#i', '#</(p|li|blockquote)>#i'], ["\n", "\n"], $body);

        return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function isHtml(string $body): bool
    {
        return (bool) preg_match('#<(p|br|strong|b|em|i|u|s|mark|ul|ol|li|blockquote|a)[\s>/]#i', $body);
    }

    private static function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            self::clean($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                // Nieznany znacznik: zostaje jego zawartość.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }

            if ($tag === 'a') {
                if (preg_match('#^(https?://|mailto:)#i', $href)) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('rel', 'nofollow noopener noreferrer');
                    $child->setAttribute('target', '_blank');
                } else {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
            }
        }
    }
}
