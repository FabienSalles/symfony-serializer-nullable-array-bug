<?php

declare(strict_types=1);

namespace App;

final class PaletteNonNullable
{
    private array $colors;

    /**
     * @param Color[] $colors
     */
    public function __construct(array $colors = [])
    {
        $this->colors = $colors;
    }

    /**
     * @return Color[]
     */
    public function getColors(): array
    {
        return $this->colors;
    }
}
