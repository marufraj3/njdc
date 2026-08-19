<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'abbr', 'b', 'blockquote', 'br', 'caption', 'code', 'col', 'colgroup', 'div', 'em', 'figcaption', 'figure',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'iframe', 'img', 'li', 'ol', 'p', 'pre', 'rt-renderer',
        'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul', 'wbr',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'alt', 'aria-label', 'border', 'cellpadding', 'cellspacing', 'class', 'colspan', 'frameborder', 'height', 'href',
        'loading', 'rel', 'role', 'rowspan', 'scope', 'src', 'style', 'target', 'title', 'width',
    ];

    public function clean(?string $html): ?string
    {
        if (! filled($html)) return $html;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="jdpc-sanitizer-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $nodes = iterator_to_array($xpath->query('//*'));
        foreach (array_reverse($nodes) as $node) {
            if (! $node instanceof DOMElement || $node->getAttribute('id') === 'jdpc-sanitizer-root') continue;
            if (! in_array(strtolower($node->tagName), self::ALLOWED_TAGS, true)) {
                $this->unwrapOrRemove($node);
                continue;
            }
            $this->cleanAttributes($node);
            if (strtolower($node->tagName) === 'iframe' && ! $this->allowedFrame($node->getAttribute('src'))) {
                $node->parentNode?->removeChild($node);
            }
        }

        $root = $dom->getElementById('jdpc-sanitizer-root');
        if (! $root) return '';
        $clean = '';
        foreach ($root->childNodes as $child) $clean .= $dom->saveHTML($child);
        return trim($clean);
    }

    private function cleanAttributes(DOMElement $node): void
    {
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            if (! in_array($name, self::ALLOWED_ATTRIBUTES, true) || str_starts_with($name, 'on')) {
                $node->removeAttribute($attribute->name);
                continue;
            }
            if (in_array($name, ['href', 'src'], true) && ! $this->safeUrl($attribute->value, $name === 'src')) {
                $node->removeAttribute($attribute->name);
            }
            if ($name === 'style') {
                $style = preg_replace('/(?:expression|javascript\s*:|vbscript\s*:|behavior\s*:|@import)/i', '', $attribute->value);
                $node->setAttribute('style', mb_substr((string) $style, 0, 2000));
            }
        }
        if (strtolower($node->tagName) === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function safeUrl(string $url, bool $allowDataImage): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('/[\x00-\x1F\x7F]/', $url) || str_starts_with($url, '//')) return false;
        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?')) return true;
        if ($allowDataImage && preg_match('#^data:image/(?:png|gif|jpe?g|webp);base64,#i', $url)) return true;
        return (bool) preg_match('#^(?:https?://|mailto:|tel:)#i', $url);
    }

    private function allowedFrame(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && in_array($host, ['youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'google.com', 'www.google.com', 'maps.google.com'], true);
    }

    private function unwrapOrRemove(DOMNode $node): void
    {
        if (in_array(strtolower($node->nodeName), ['script', 'style', 'object', 'embed', 'link', 'meta'], true)) {
            $node->parentNode?->removeChild($node);
            return;
        }
        while ($node->firstChild) $node->parentNode?->insertBefore($node->firstChild, $node);
        $node->parentNode?->removeChild($node);
    }
}
