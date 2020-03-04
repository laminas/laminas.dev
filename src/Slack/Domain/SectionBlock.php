<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\InvalidArgumentException;

class SectionBlock implements BlockInterface
{
    /** @var null|ElementInterface */
    private $accessory;

    /** TextObject[] */
    private $fields = [];

    /** @var null|TextObject */
    private $text;

    public static function fromArray(array $data): self
    {
        $block = new self();

        if (isset($data['text'])) {
            $block->setText(TextObject::fromArray($data['text']));
        }

        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $textData) {
                $block->addField(TextObject::fromArray($textData));
            }
        }

        if (isset($data['accessory'])) {
            $block->setAccessory(ImageElement::fromArray($data['accessory']));
        }

        return $block;
    }

    public function setText(TextObject $text): void
    {
        $this->text = $text;
    }

    public function getText(): ?TextObject
    {
        return $this->text;
    }

    public function setAccessory(ElementInterface $accessory): void
    {
        $this->accessory = $accessory;
    }

    public function getAccessory(): ?ElementInterface
    {
        return $this->accessory;
    }

    public function addField(TextObject $field): void
    {
        $this->fields[] = $field;
    }

    /** @return TextObject[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function validate(): void
    {
        if ($this->text === null
            && empty($this->fields)
        ) {
            throw new InvalidArgumentException(
                'Section block requires one or both of the "text" and "fields" keys; neither provided',
                0,
                'text,fields',
                []
            );
        }

        if ($this->text) {
            $this->text->validate();
        }

        if ($this->accessory) {
            $this->accessory->validate();
        }

        array_walk($this->fields, function (TextObject $field) {
            $field->validate();
        });
    }

    public function toArray(): array
    {
        $payload = ['type' => 'section'];

        if ($this->text) {
            $payload['text'] = $this->text->toArray();
        }

        if (! empty($this->fields)) {
            $payload['fields'] = $this->renderFields();
        }

        if ($this->accessory) {
            $payload['accessory'] = $this->accessory->toArray();
        }

        return $payload;
    }

    private function renderFields(): array
    {
        return array_map(function (TextObject $field) : array {
            return $field->toArray();
        }, $this->fields);
    }
}
