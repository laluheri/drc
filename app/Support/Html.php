<?php

namespace App\Support;

class Html
{
    public static function clean(?string $html): string
    {
        if (! $html) {
            return '';
        }
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $walk = function ($node) use (&$walk) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMElement) {
                    if (! in_array(strtolower($child->tagName), ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr'])) {
                        if (in_array(strtolower($child->tagName), ['script', 'style', 'iframe', 'object', 'svg', 'form'])) {
                            $node->removeChild($child);

                            continue;
                        }
                        $walk($child);
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        } $node->removeChild($child);

                        continue;
                    }
                    foreach (iterator_to_array($child->attributes) as $attr) {
                        if (! ($child->tagName === 'a' && $attr->name === 'href' && self::safeUrl($attr->value))) {
                            $child->removeAttribute($attr->name);
                        }
                    }
                    $walk($child);
                } elseif ($child instanceof \DOMProcessingInstruction || $child instanceof \DOMComment) {
                    $node->removeChild($child);
                }
            }
        };
        $walk($document);

        return $document->saveHTML();
    }

    public static function safeUrl(?string $value): bool
    {
        return is_string($value) && (preg_match('~^https?://~i', $value) || preg_match('~^/(?!/)[^\\\\]*$~', $value));
    }

    public static function mapUrl(?string $embed): ?string
    {
        if (! $embed || ! preg_match('/\bsrc=["\x27]([^"\x27]+)["\x27]/i', $embed, $match)) {
            return null;
        }
        $url = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https' && ($parts['host'] ?? '') === 'www.google.com' && str_starts_with($parts['path'] ?? '', '/maps/embed') ? $url : null;
    }
}
