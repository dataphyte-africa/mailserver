<?php

namespace App\Services\Forms;

use InvalidArgumentException;

class FormTemplateRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            'application_basic' => [
                'mode' => 'application',
                'requires_review' => true,
            ],
            'data_collection_basic' => [
                'mode' => 'data_collection',
                'requires_review' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $templateFamily): array
    {
        $definition = $this->all()[trim($templateFamily)] ?? null;

        if (! is_array($definition)) {
            throw new InvalidArgumentException(sprintf('Unsupported form template family [%s].', $templateFamily));
        }

        return $definition;
    }
}
