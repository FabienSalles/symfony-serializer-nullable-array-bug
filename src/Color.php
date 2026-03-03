<?php

declare(strict_types=1);

namespace App;

final class Color
{
    public function __construct(
        private string $name,
        private string $hex,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHex(): string
    {
        return $this->hex;
    }
}
