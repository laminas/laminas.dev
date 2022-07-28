<?php

declare(strict_types=1);

namespace App\Slack;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block as HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline as HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\AbstractWebResource;
use League\CommonMark\Node\Block;
use League\CommonMark\Node\Inline;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

use function array_walk;
use function in_array;
use function preg_match;
use function preg_replace;
use function spl_object_hash;
use function sprintf;
use function strip_tags;

class MarkdownToSlackFormatter
{
    private const SKIPPABLE_HTML_BLOCK = [
        HtmlBlock\HtmlBlock::TYPE_2_COMMENT,
        HtmlBlock\HtmlBlock::TYPE_5_CDATA,
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
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $parser = new MarkdownParser($environment);
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
                $node instanceof HtmlBlock\HtmlBlock
                && in_array($node->getType(), self::SKIPPABLE_HTML_BLOCK, true)
            ) {
                $toRemove[] = $node;
            }
        }

        array_walk($toRemove, function (Node $node) {
            $node->detach();
        });
    }

    private function walk(Node $rootNode, string $linePrefix = ''): string
    {
        $output = '';

        foreach ($rootNode->iterator() as $node) {
            if ($this->visited($node)) {
                continue;
            }

            // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.NoAssignment
            switch (true) {
                // INLINE NODES

                case $node instanceof Inline\Newline:
                    $this->visit($node);
                    $output .= "\n";
                    break;

                case $node instanceof HtmlInline\HtmlInline:
                case $node instanceof Inline\Text:
                    $this->visit($node);
                    /** @var StringContainerInterface $node */
                    $output .= $node->getLiteral();
                    break;

                case $node instanceof HtmlInline\Code:
                    $this->visit($node);
                    /** @var StringContainerInterface $node */
                    $output .= sprintf('`%s`', $node->getLiteral());
                    break;

                case $node instanceof HtmlInline\Emphasis:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf('_%s_', $content);
                    break;

                case $node instanceof HtmlInline\Strong:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf('*%s*', $content);
                    break;

                case $node instanceof HtmlInline\Link:
                    $this->visit($node);
                    // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
                    /** @var AbstractWebResource $node */
                    $url     = $node->getUrl();
                    $content = $this->walk($node);
                    $output .= sprintf('<%s|%s>', $url, $content);
                    break;

                case $node instanceof HtmlInline\Image:
                    $this->visit($node);
                    /** @var AbstractWebResource $node */
                    $output .= sprintf("%s ", $node->getUrl());
                    break;

                // BLOCK NODES

                case $node instanceof Block\Paragraph:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s%s\n\n", $linePrefix, $content);
                    break;

                case $node instanceof HtmlBlock\Heading:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s*%s*\n\n", $linePrefix, $content);
                    break;

                case $node instanceof HtmlBlock\ListItem:
                    $this->visit($node);
                    $content = $this->walk($node);
                    $output .= sprintf("%s* %s", $linePrefix, $content);
                    break;

                case $node instanceof HtmlBlock\BlockQuote:
                    $this->visit($node);
                    $content = $this->walk($node, '> ');
                    $output .= $content;
                    break;

                case $node instanceof HtmlBlock\FencedCode:
                case $node instanceof HtmlBlock\IndentedCode:
                    $this->visit($node);
                    // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
                    /** @var StringContainerInterface $node */
                    $content = sprintf("```\n%s```\n\n", $node->getLiteral());
                    if (! empty($linePrefix)) {
                        $content = preg_replace('/^/m', $linePrefix, $content);
                    }
                    $output .= $content;
                    break;

                case $node instanceof HtmlBlock\HtmlBlock:
                    $this->visit($node);
                    /** @var StringContainerInterface $node */
                    $output .= sprintf("%s\n\n", strip_tags($node->getLiteral()));
                    break;

                case $node instanceof Inline\AbstractStringContainer:
                    $this->visit($node);
                    $content = $this->walk($node);
                    if (! preg_match('/^\s+$/s', $content)) {
                        $output .= sprintf("%s\n\n", $content);
                    }
                    break;

                default:
                    // Do nothing
                    break;
            }
            // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.NoAssignment
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
