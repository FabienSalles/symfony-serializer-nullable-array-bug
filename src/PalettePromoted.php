<?php

declare(strict_types=1);

namespace App;

final class PalettePromoted
{
    /**
     * @param Color[] $colors
     */
    public function __construct(
        private ?array $colors = [],
    ) {
    }

    /**
     * @return Color[]|null
     */
    public function getColors(): ?array
    {
        return $this->colors;
    }
}
