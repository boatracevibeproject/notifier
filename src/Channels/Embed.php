<?php

declare(strict_types=1);

namespace BVP\Notifier\Channels;

use InvalidArgumentException;

/**
 * @author shimomo
 */
final class Embed
{
    private const TITLE_MAX_LENGTH = 256;
    private const DESCRIPTION_MAX_LENGTH = 4096;
    private const FIELD_NAME_MAX_LENGTH = 256;
    private const FIELD_VALUE_MAX_LENGTH = 1024;
    private const MAX_FIELDS = 25;

    /**
     * @param string $title
     * @param ?string $url
     * @param ?string $description
     * @param array<int, array{
     *   name: string,
     *   value: string,
     *   inline?: bool,
     * }> $fields
     * @param ?int $color
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $url = null,
        public readonly ?string $description = null,
        public readonly array $fields = [],
        public readonly ?int $color = null,
    ) {
        $titleLength = mb_strlen($this->title);

        if ($titleLength > self::TITLE_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Embed title must be {$titleLength} chars or fewer (" . self::TITLE_MAX_LENGTH . ' allowed).',
            );
        }

        if ($this->url !== null && filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Embed url must be a valid URL.');
        }

        if ($this->description !== null) {
            $descriptionLength = mb_strlen($this->description);

            if ($descriptionLength > self::DESCRIPTION_MAX_LENGTH) {
                throw new InvalidArgumentException(
                    "Embed description must be {$descriptionLength} chars or fewer (" . self::DESCRIPTION_MAX_LENGTH . ' allowed).',
                );
            }
        }

        if (count($this->fields) > self::MAX_FIELDS) {
            throw new InvalidArgumentException('Embed fields must be ' . self::MAX_FIELDS . ' or fewer.');
        }

        foreach ($this->fields as $field) {
            $nameLength = mb_strlen($field['name']);

            if ($nameLength > self::FIELD_NAME_MAX_LENGTH) {
                throw new InvalidArgumentException(
                    "Embed field name must be {$nameLength} chars or fewer (" . self::FIELD_NAME_MAX_LENGTH . ' allowed).',
                );
            }

            $valueLength = mb_strlen($field['value']);

            if ($valueLength > self::FIELD_VALUE_MAX_LENGTH) {
                throw new InvalidArgumentException(
                    "Embed field value must be {$valueLength} chars or fewer (" . self::FIELD_VALUE_MAX_LENGTH . ' allowed).',
                );
            }
        }
    }

    /**
     * @return array{
     *   title: string,
     *   url?: string,
     *   description?: string,
     *   color?: int,
     *   fields?: list<array{
     *     name: string,
     *     value: string,
     *     inline: bool,
     *   }>,
     * }
     */
    public function toArray(): array
    {
        $payload = ['title' => $this->title];

        if ($this->url !== null) {
            $payload['url'] = $this->url;
        }

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        if ($this->color !== null) {
            $payload['color'] = $this->color;
        }

        if (!empty($this->fields)) {
            $payload['fields'] = array_values(array_map(fn(array $field): array => [
                'name' => $field['name'],
                'value' => $field['value'],
                'inline' => $field['inline'] ?? false,
            ], $this->fields));
        }

        return $payload;
    }

    /**
     * @return int
     */
    public function totalLength(): int
    {
        $length = mb_strlen($this->title) + mb_strlen($this->description ?? '');

        foreach ($this->fields as $field) {
            $length += mb_strlen($field['name']) + mb_strlen($field['value']);
        }

        return $length;
    }
}
