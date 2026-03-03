<?php

declare(strict_types=1);

namespace App\Tests;

use App\Color;
use App\Palette;
use App\PaletteFixed;
use App\PaletteNonNullable;
use App\PaletteNonPromoted;
use App\PalettePromoted;
use App\PalettePromotedFixed;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class NullableArrayDenormalizationTest extends TestCase
{
    private const JSON = '{"colors": [{"name": "Red", "hex": "#FF0000"}, {"name": "Blue", "hex": "#0000FF"}]}';

    #[Test]
    public function varWithoutNullOnNullableArrayLosesTypeInfo(): void
    {
        $serializer = $this->createSerializer();

        $palette = $serializer->deserialize(self::JSON, Palette::class, 'json');

        self::assertEquals(new Palette($this->expectedColors()), $palette);
    }

    #[Test]
    public function varWithNullOnNullableArrayPreservesTypeInfo(): void
    {
        $serializer = $this->createSerializer();

        $palette = $serializer->deserialize(self::JSON, PaletteFixed::class, 'json');

        self::assertEquals(new PaletteFixed($this->expectedColors()), $palette);
    }

    #[Test]
    public function varOnNonNullableArrayPreservesTypeInfo(): void
    {
        $serializer = $this->createSerializer();

        $palette = $serializer->deserialize(self::JSON, PaletteNonNullable::class, 'json');

        self::assertEquals(new PaletteNonNullable($this->expectedColors()), $palette);
    }

    #[Test]
    public function nonPromotedParamWithoutNullPreservesTypeInfo(): void
    {
        $serializer = $this->createSerializer();

        $palette = $serializer->deserialize(self::JSON, PaletteNonPromoted::class, 'json');

        self::assertEquals(new PaletteNonPromoted($this->expectedColors()), $palette);
    }

    #[Test]
    public function promotedParamWithoutNullOnNullableArrayLosesTypeInfo(): void
    {
        $serializer = $this->createSerializer();

        $palette = $serializer->deserialize(self::JSON, PalettePromoted::class, 'json');

        self::assertEquals(new PalettePromoted($this->expectedColors()), $palette);
    }

    #[Test]
    public function promotedParamWithNullOnNullableArrayPreservesTypeInfo(): void
    {
        $serializer = $this->createSerializer();

        $palette = $serializer->deserialize(self::JSON, PalettePromotedFixed::class, 'json');

        self::assertEquals(new PalettePromotedFixed($this->expectedColors()), $palette);
    }

    /** @return Color[] */
    private function expectedColors(): array
    {
        return [
            new Color('Red', '#FF0000'),
            new Color('Blue', '#0000FF'),
        ];
    }

    private function createSerializer(): Serializer
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        $propertyInfoExtractor = new PropertyInfoExtractor(
            listExtractors: [$reflectionExtractor],
            typeExtractors: [$phpDocExtractor, $reflectionExtractor],
            accessExtractors: [$reflectionExtractor],
            initializableExtractors: [$reflectionExtractor],
        );

        return new Serializer(
            [
                new ObjectNormalizer(propertyTypeExtractor: $propertyInfoExtractor),
                new ArrayDenormalizer(),
            ],
            [new JsonEncoder()],
        );
    }
}
