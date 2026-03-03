<?php

declare(strict_types=1);

namespace App;

final class PaletteNonPromoted
{
    private ?array $colors;

    /**
     * @param Color[] $colors
     */
    public function __construct(?array $colors = [])
    {
        $this->colors = $colors;
    }

    /**
     * @return Color[]|null
     */
    public function getColors(): ?array
    {
        return $this->colors;
    }
}
