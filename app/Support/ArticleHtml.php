<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class ArticleHtml
{
    private const ALLOWED = [
        'a', 'abbr', 'aside', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'div', 'dl',
        'dt', 'em', 'figcaption', 'figure', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'li', 'ol',
        'p', 'pre', 's', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot',
        'th', 'thead', 'tr', 'u', 'ul',
    ];

    private const REMOVE = [
        'audio', 'canvas', 'embed', 'form', 'iframe', 'img', 'input', 'math', 'noscript', 'object',
        'picture', 'script', 'style', 'svg', 'template', 'video',
    ];

    public function sanitize(string $html): string
    {
        $document = new DOMDocument;
        $document->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body>'.
            str_replace("\0", '', $html).'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $document->getElementsByTagName('body')->item(0);
        $this->cleanChildren($body);

        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*[self::p or self::span or self::div or self::a or self::h2 or self::h3 or self::h4 or self::figure][not(normalize-space()) and not(.//hr)]') as $empty) {
            $empty->parentNode?->removeChild($empty);
        }

        $result = '';
        foreach ($body->childNodes as $node) {
            $result .= $document->saveHTML($node);
        }

        return trim($result);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                if ($node->nodeType !== XML_TEXT_NODE) {
                    $parent->removeChild($node);
                }

                continue;
            }

            $tag = $node->tagName;
            if (in_array($tag, self::REMOVE, true)) {
                $parent->removeChild($node);

                continue;
            }

            $this->cleanChildren($node);
            if (! in_array($tag, self::ALLOWED, true)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $keep = match ($attribute->name) {
                    'href' => $tag === 'a' && $this->cleanHref($attribute->value) !== null,
                    'colspan', 'rowspan' => in_array($tag, ['th', 'td'], true) && preg_match('/^[1-9][0-9]?$/D', $attribute->value),
                    'start' => $tag === 'ol' && preg_match('/^[0-9]{1,4}$/D', $attribute->value),
                    default => false,
                };
                if (! $keep) {
                    $node->removeAttributeNode($attribute);
                }
            }
            if ($node->hasAttribute('href')) {
                $node->setAttribute('href', $this->cleanHref($node->getAttribute('href')));
            }
        }
    }

    private function cleanHref(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/u', $value)) {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https', 'mailto'], true))) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $sameSite = ! isset($parts['host']) || in_array(strtolower($parts['host']), ['pisicata.ro', 'www.pisicata.ro'], true);
        if ($sameSite && preg_match('~^(?:\.?/)?(?:images|admin)/~i', $path)) {
            return null;
        }
        if ($sameSite && isset($parts['host'])) {
            return '/'.ltrim($path, '/').(isset($parts['query']) ? '?'.$parts['query'] : '').
                (isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
        }

        return $value;
    }
}
