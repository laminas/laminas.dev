<?php

declare(strict_types=1);

namespace App\Slack;

use League\CommonMark\Block\Element as Block;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\Inline\Element as Inline;
use League\CommonMark\Node\Node;

use function array_walk;
use function in_array;
use function preg_replace;
use function spl_object_hash;
use function sprintf;
use function strip_tags;

class MarkdownToSlackFormatter
{
    private const SKIPPABLE_HTML_BLOCK = [
        Block\HtmlBlock::TYPE_2_COMMENT,
        Block\HtmlBlock::TYPE_5_CDATA,
    ];

    /** @var string[] */
    private $visitedNodes = [];

    public function format(string $markdown): string
    {
        $this->visitedNodes = [];
        $document           = $this->createDocument($markdown);
        $this->stripComments($document);
        return $this->walk($document);
    }

    private function createDocument(string $markdown): Block\Document
    {
        $parser = new DocParser(Environment::createCommonMarkEnvironment());
        return $parser->parse($markdown);
    }

    private function stripComments(Block\Document $document): void
    {
        $walker   = $document->walker();
        $toRemove = [];

        while ($event = $walker->next()) {
            if (! $event->isEntering()) {
                continue;
            }

            $node = $event->getNode();
            if (
                $node instanceof Block\HtmlBlock
                && in_array($node->getType(), self::SKIPPABLE_HTML_BLOCK, true)
            ) {
                $toRemove[] = $node;
            }
        }

        array_walk($toRemove, function (Node $node) {
            $node->detach();
        });
    }

    private function walk(Node $node, string $linePrefix = ''): string
    {
        $walker = $node->walker();
        $output = '';

        while ($event = $walker->next()) {
            $node = $event->getNode();

            if ($this->visited($node)) {
                continue;
            }

            switch (true) {
                // INLINE NODES

                case $node instanceof Inline\Newline:
                    $this->visit($node);
                    $output .= "\n";
                    break;

                case $node instanceof Inline\HtmlInline:
                case $node instanceof Inline\Text:
                    $this->visit($node);
                    $output .= $node->getContent();
                    break;

                case $node instanceof Inline\Code:
                    $this->visit($node);
                    $output .= sprintf('`%s`', $node->getContent());
                    break;

                case $node instanceof Inline\Emphasis:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf('_%s_', $content);
                    break;

                case $node instanceof Inline\Strong:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf('*%s*', $content);
                    break;

                case $node instanceof Inline\Link:
                    $this->visit($node);
                    $url     = $node->getUrl();
                    $content = $this->walk($node);
                    $output .= sprintf('<%s|%s>', $url, $content);
                    break;

                case $node instanceof Inline\Image:
                    $this->visit($node);
                    $output .= sprintf("%s ", $node->getUrl());
                    break;

                // BLOCK NODES

                case $node instanceof Block\Paragraph:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s%s\n\n", $linePrefix, $content);
                    break;

                case $node instanceof Block\Heading:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s*%s*\n\n", $linePrefix, $content);
                    break;

                case $node instanceof Block\ListItem:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s* %s", $linePrefix, $content);
                    break;

                case $node instanceof Block\BlockQuote:
                    $this->visit($node);
                    $content = $this->walk($node, '> ');
                    $output .= $content;
                    break;

                case $node instanceof Block\FencedCode:
                case $node instanceof Block\IndentedCode:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $content = sprintf("```\n%s\n```\n\n", $content);
                    if (! empty($linePrefix)) {
                        $content = preg_replace('/^/m', $linePrefix, $content);
                    }
                    $output .= $content;
                    break;

                case $node instanceof Block\HtmlBlock:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s\n\n", strip_tags($content));
                    break;

                case $node instanceof Block\AbstractStringContainerBlock:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s\n\n", $content);
                    break;

                default:
                    // Do nothing
                    break;
            }
        }

        return $output;
    }

    private function visit(Node $node): void
    {
        $this->visitedNodes[spl_object_hash($node)] = true;
    }

    private function visited(Node $node): bool
    {
        return isset($this->visitedNodes[spl_object_hash($node)]);
    }
}
