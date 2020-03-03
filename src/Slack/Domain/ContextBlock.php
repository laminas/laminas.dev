<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use InvalidArgumentException;

class ContextBlock implements BlockInterface
{
    /** @var array */
    private $elements = [];

    public static function fromArray($data): self
    {
        $context = new self();

        foreach ($data['elements'] as $elementData) {
            switch ($elementData['type']) {
                case 'image':
                    $context->addElement(ImageElement::fromArray($elementData));
                    break;
                case TextObject::TYPE_MARKDOWN:
                case TextObject::TYPE_PLAIN_TEXT:
                    $context->addElement(TextObject::fromArray($elementData));
                    break;
            }
        }

        return $context;
    }

    public function addElement(ElementInterface $element): void
    {
        $this->elements[] = $element;
    }

    /** @return ElementInterface[] */
    public function getElements(): array
    {
        return $this->elements;
    }

    public function validate(): void
    {
        $count = count($this->elements);
        if ($count === 0 || $count > 10) {
            throw new InvalidArgumentException(sprintf(
                'Context requires at least 1 and no more than 10 elements; contains %d',
                $count
            ));
        }
        array_walk($this->elements, function (ValidatableInterface $element) {
            $element->validate();
        });
    }

    public function toArray(): array
    {
        return [
            'type' => 'context',
            'elements' => array_map(function (RenderableInterface $element) {
                return $element->toArray();
            }, $this->elements),
        ];
    }
}
