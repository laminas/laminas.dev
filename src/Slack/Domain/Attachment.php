<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use function implode;

/**
 * @see https://api.slack.com/docs/message-attachments
 */
class Attachment
{
    /** @var string */
    private $fallback;

    /** @var AttachmentColor */
    private $color;

    /** @var null|string */
    private $pretext;

    /** @var null|string */
    private $title;

    /** @var null|string */
    private $titleLink;

    /** @var string[] */
    private $text = [];

    /** @var string */
    private $footer;

    public function __construct(string $fallback, ?AttachmentColor $color = null)
    {
        $this->fallback = $fallback;
        $this->color    = $color ?? AttachmentColor::default();
    }

    public function withPretext(string $pretext) : self
    {
        $this->pretext = $pretext;

        return $this;
    }

    public function withTitle(string $title) : self
    {
        $this->title = $title;

        return $this;
    }

    public function withTitleLink(string $titleLink) : self
    {
        $this->titleLink = $titleLink;

        return $this;
    }

    public function addText(string $text) : self
    {
        $this->text[] = $text;

        return $this;
    }

    public function withFooter(string $footer) : self
    {
        $this->footer = $footer;

        return $this;
    }

    public function toArray() : array
    {
        return [
            'fallback'   => $this->fallback,
            'color'      => (string) $this->color,
            'pretext'    => $this->pretext,
            'title'      => $this->title,
            'title_link' => $this->titleLink,
            'text'       => implode("\n", $this->text),
            'footer'     => $this->footer,
            'mrkdwn_in'  => ['pretext', 'text'],
        ];
    }
}
