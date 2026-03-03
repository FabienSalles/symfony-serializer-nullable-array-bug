<?php

declare(strict_types=1);

namespace App;

final class PaletteFixed
{
    /** @var Color[]|null */
    private ?array $colors;

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
