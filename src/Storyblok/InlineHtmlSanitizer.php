<?php

namespace TAFER\Core\Storyblok;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class InlineHtmlSanitizer
{
    public static function sanitize(?string $html, array $allowedTags = ['em', 'strong', 'br']): string
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        $previousLibxmlState = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        $sanitizedHtml = '';
        foreach ($document->documentElement?->childNodes ?? [] as $childNode) {
            $sanitizedHtml .= self::renderNode($childNode, $allowedTags);
        }

        return $sanitizedHtml;
    }

    public static function stripTags(?string $html): string
    {
        return trim(strip_tags((string) $html));
    }

    private static function renderNode(DOMNode $node, array $allowedTags): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tagName = strtolower($node->tagName);

        if (in_array($tagName, ['script', 'style'], true)) {
            return '';
        }

        if ($tagName === 'br' && in_array($tagName, $allowedTags, true)) {
            return '<br>';
        }

        $childrenHtml = '';
        foreach ($node->childNodes as $childNode) {
            $childrenHtml .= self::renderNode($childNode, $allowedTags);
        }

        if (in_array($tagName, ['em', 'strong', 'span'], true) && in_array($tagName, $allowedTags, true)) {
            return "<{$tagName}>{$childrenHtml}</{$tagName}>";
        }

        return $childrenHtml;
    }
}
