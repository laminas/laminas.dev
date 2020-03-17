<?php

declare(strict_types=1);

namespace App\Slack;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

use function error_log;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * @see https://gist.github.com/SiamKreative/0098993097bdf5cea5da#gistcomment-2241255
 */
class HtmlToSlackFormatter
{
    public function format(string $html): string
    {
        $dom                      = new DOMDocument('1.0', 'utf-8');
        $dom->recover             = true;
        $dom->strictErrorChecking = false;
        @$dom->loadHTML($html);

        return $this->traverseNode($dom);
    }

    private function traverseNode(DOMNode $rootNode): string
    {
        if (! $rootNode->hasChildNodes()) {
            return $rootNode->textContent;
        }

        $output = '';
        foreach ($rootNode->childNodes as $node) {
            $output .= $this->handleNode($node);
        }

        return $output;
    }

    private function handleNode(DOMNode $node): string
    {
        if ($node instanceof DOMComment) {
            return '';
        }

        if ($node instanceof DOMText) {
            return $node->textContent;
        }

        switch (strtolower($node->nodeName)) {
            case 'aside':
                if (! $node instanceof DOMElement || ! $node->hasAttribute('class')) {
                    return $this->traverseNode($node);
                }

                // Handle Discourse furling
                if (preg_match('/\bonebox\b/', $node->getAttribute('class'))) {
                    return $this->handleDiscourseFurling($node);
                }

                return $this->traverseNode($node);

            case 'a':
                if (! $node instanceof DOMElement || ! $node->hasAttribute('href')) {
                    return $this->traverseNode($node);
                }

                return sprintf('<%s|%s>', $node->getAttribute('href'), $this->traverseNode($node));

            case 'b':
            case 'strong':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                return sprintf('*%s*', $this->traverseNode($node));

            case 'br':
                return "\n";

            case 'code':
                return sprintf('`%s`', $this->traverseNode($node));

            case 'del':
            case 'strike':
                return sprintf('~%s~', $this->traverseNode($node));

            case 'i':
            case 'em':
                return sprintf('_%s_', $this->traverseNode($node));

            case 'li':
                return sprintf('* %s', $this->traverseNode($node));

            case 'pre':
                return sprintf('```%s```', $this->handlePreformattedText($node));

            default:
                return $this->traverseNode($node);
        }
    }

    private function handleDiscourseFurling(DOMElement $rootNode): string
    {
        if (! $rootNode->hasChildNodes()) {
            return $rootNode->textContent;
        }

        foreach ($rootNode->childNodes as $node) {
            switch (strtolower($node->nodeName)) {
                case 'header':
                    if (
                        ! $node instanceof DOMElement
                        || ! $node->hasAttribute('class')
                        || ! preg_match('/\bsource\b/', $node->getAttribute('class'))
                    ) {
                        error_log(sprintf('Aside does not have source class? %s', $rootNode->textContent));
                        break;
                    }

                    return $this->traverseNode($node);

                default:
                    break;
            }
        }

        return '';
    }

    private function handlePreformattedText(DOMElement $rootNode): string
    {
        if (! $rootNode->hasChildNodes()) {
            return $rootNode->textContent;
        }

        $output = '';
        foreach ($rootNode->childNodes as $node) {
            switch (strtolower($node->nodeName)) {
                case 'code':
                    $output .= $this->traverseNode($node);
                    break;
                default:
                    $output .= $this->handleNode($node);
                    break;
            }
        }

        return $output;
    }
}
