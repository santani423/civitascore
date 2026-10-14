<?php

namespace Modules\Admission\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitasi HTML konten CMS/profil prodi di server (03 FR-WEB-10, 13 §B.2):
 * allowlist tag & atribut, URL hanya http/https/mailto/relatif, tanpa
 * script/style/event handler. Tanpa dependensi tambahan (DOMDocument).
 */
final class HtmlSanitizer
{
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [], 'hr' => [],
        'a' => ['href', 'title', 'target'], 'img' => ['src', 'alt', 'width', 'height'], 'span' => [], 'div' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'figure' => [], 'figcaption' => [], 'code' => [], 'pre' => [],
    ];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'base'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('__root');

        if ($root === null) {
            return strip_tags($html);
        }

        self::walk($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            self::walk($child);

            if (! array_key_exists($tag, self::ALLOWED)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);

                if (! in_array($name, self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attribute->nodeName);

                    continue;
                }

                if (in_array($name, ['href', 'src'], true) && ! self::safeUrl($attribute->nodeValue ?? '', $name === 'src')) {
                    $child->removeAttribute($attribute->nodeName);
                }
            }

            if ($tag === 'a') {
                $child->setAttribute('rel', 'noopener noreferrer nofollow');

                if ($child->getAttribute('target') !== '_blank') {
                    $child->removeAttribute('target');
                }
            }
        }
    }

    private static function safeUrl(string $url, bool $isImage): bool
    {
        $url = trim(preg_replace('/[\x00-\x20]+/', '', $url) ?? '');

        if ($url === '' || str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $isImage ? $scheme === 'https' : in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
