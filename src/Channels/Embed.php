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
        public readonly ?string $description = null,
        public readonly array $fields = [],
        public readonly ?int $color = null,
    ) {
        if (mb_strlen($this->title) > self::TITLE_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Embed title must be {$this->title} chars or fewer (" . self::TITLE_MAX_LENGTH . ' allowed).',
            );
        }

        if ($this->description !== null && mb_strlen($this->description) > self::DESCRIPTION_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Embed description must be ' . self::DESCRIPTION_MAX_LENGTH . ' chars or fewer.',
            );
        }

        if (count($this->fields) > self::MAX_FIELDS) {
            throw new InvalidArgumentException('Embed fields must be ' . self::MAX_FIELDS . ' or fewer.');
        }

        foreach ($this->fields as $field) {
            if (mb_strlen($field['name']) > self::FIELD_NAME_MAX_LENGTH) {
                throw new InvalidArgumentException(
                    'Embed field name must be ' . self::FIELD_NAME_MAX_LENGTH . ' chars or fewer.',
                );
            }

            if (mb_strlen($field['value']) > self::FIELD_VALUE_MAX_LENGTH) {
                throw new InvalidArgumentException(
                    'Embed field value must be ' . self::FIELD_VALUE_MAX_LENGTH . ' chars or fewer.',
                );
            }
        }
    }

    /**
     * @return array{
     *   title: string,
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
