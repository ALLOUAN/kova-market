<?php

namespace App\Services\Catalog\Import;

/**
 * Outcome of a catalog import or of its preview (F-104): what was (or would be) created or updated, and the
 * refused lines with their reason. Kept as a plain array between Livewire requests.
 */
class ImportReport
{
    public int $rows = 0;

    public int $productsCreated = 0;

    public int $variantsCreated = 0;

    public int $variantsUpdated = 0;

    /** @var array<int, string> line number => reason */
    public array $errors = [];

    public function __construct(public readonly bool $preview) {}

    public function fail(int $line, string $reason): void
    {
        $this->errors[$line] = $reason;
    }

    public function imported(): int
    {
        return $this->rows - count($this->errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'preview' => $this->preview,
            'rows' => $this->rows,
            'imported' => $this->imported(),
            'products_created' => $this->productsCreated,
            'variants_created' => $this->variantsCreated,
            'variants_updated' => $this->variantsUpdated,
            'errors' => $this->errors,
        ];
    }
}
