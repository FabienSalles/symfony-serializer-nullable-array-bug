# [Serializer] `AbstractObjectNormalizer::denormalizeParameter()` silently discards collection element type for nullable array when PhpDoc misses `|null`

## Symfony version

7.4.x (regression introduced in 7.4.0 — works fine with Symfony 7.3)

## Description

When `AbstractObjectNormalizer::denormalizeParameter()` resolves the type for a `?array` constructor parameter whose PhpDoc annotation (`@var` or `@param`) declares a collection type like `Color[]` **without** `|null`, the type comparison logic (lines 1014–1022) silently replaces the rich PhpDoc type (which contains collection element info) with the bare PHP reflection type, losing all knowledge of the element class.

Array elements are then denormalized as plain associative arrays instead of proper objects.

The bug affects explicit properties with `@var` and promoted constructor parameters with `@param`. Interestingly, non-promoted constructors with `@param Color[]` (without `|null`) are **not** affected because `PhpDocExtractor` merges nullability from the PHP type hint in that context.

## How to reproduce

```bash
git clone https://github.com/fabiensalles/symfony-serializer-nullable-array-bug.git
cd symfony-serializer-nullable-array-bug

# Symfony 7.4 — 2 tests FAIL (bug)
make test-7.4

# Symfony 7.3 — all 6 tests PASS (no bug)
make test-7.3
```

Requires Docker. Uses `php:8.3-cli`.

### The DTOs

**Palette.php** — explicit property with `@var Color[]` (triggers the bug):

```php
final class Palette
{
    /** @var Color[] */
    private ?array $colors;

    public function __construct(?array $colors = [])
    {
        $this->colors = $colors;
    }
}
```

**PalettePromoted.php** — promoted constructor with `@param Color[]` (also triggers the bug):

```php
final class PalettePromoted
{
    /**
     * @param Color[] $colors
     */
    public function __construct(
        private ?array $colors = [],
    ) {}
}
```

**PaletteNonPromoted.php** — non-promoted constructor with `@param Color[]` (does **not** trigger the bug):

```php
final class PaletteNonPromoted
{
    private ?array $colors;

    /** @param Color[] $colors */
    public function __construct(?array $colors = [])
    {
        $this->colors = $colors;
    }
}
```

**PaletteFixed.php** — workaround (adding `|null`):

```php
final class PaletteFixed
{
    /** @var Color[]|null */
    private ?array $colors;

    public function __construct(?array $colors = [])
    {
        $this->colors = $colors;
    }
}
```

### `make test-7.4` — Symfony 7.4 (bug present)

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

F...F.                                                              6 / 6 (100%)

There were 2 failures:

1) App\Tests\NullableArrayDenormalizationTest::varWithoutNullOnNullableArrayLosesTypeInfo
Failed asserting that two objects are equal.
--- Expected
+++ Actual
@@ @@
 App\Palette Object (
     'colors' => Array (
-        0 => App\Color Object (...)
-        1 => App\Color Object (...)
+        0 => [...]
+        1 => [...]
     )
 )

2) App\Tests\NullableArrayDenormalizationTest::promotedParamWithoutNullOnNullableArrayLosesTypeInfo
Failed asserting that two objects are equal.
--- Expected
+++ Actual
@@ @@
 App\PalettePromoted Object (
     'colors' => Array (
-        0 => App\Color Object (...)
-        1 => App\Color Object (...)
+        0 => [...]
+        1 => [...]
     )
 )

FAILURES!
Tests: 6, Assertions: 6, Failures: 2.
```

### Test summary

| Test | Scenario | Result |
|------|----------|--------|
| `varWithoutNullOnNullableArrayLosesTypeInfo` | `@var Color[]` on `?array` property | **FAIL** |
| `varWithNullOnNullableArrayPreservesTypeInfo` | `@var Color[]\|null` on `?array` property | PASS |
| `varOnNonNullableArrayPreservesTypeInfo` | `@var Color[]` on `array` property | PASS |
| `nonPromotedParamWithoutNullPreservesTypeInfo` | `@param Color[]` on non-promoted `?array` | PASS |
| `promotedParamWithoutNullOnNullableArrayLosesTypeInfo` | `@param Color[]` on promoted `?array` | **FAIL** |
| `promotedParamWithNullOnNullableArrayPreservesTypeInfo` | `@param Color[]\|null` on promoted `?array` | PASS |


### `make test-7.3` — Symfony 7.3 (no bug)

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

......                                                              6 / 6 (100%)

OK (6 tests, 6 assertions)
```

All 6 tests pass on Symfony 7.3, confirming this is a regression introduced in **Symfony 7.4.0**.

## Expected behavior

The serializer should denormalize `Color[]` into `Color` instances. The `@var Color[]` / `@param Color[]` annotation is semantically valid for a `?array` parameter.

## Actual behavior

Array elements remain as raw associative arrays. The object denormalization for the element type (`Color`) is never triggered.

## Root cause analysis

Then at lines 1014–1022 of `AbstractObjectNormalizer::denormalizeParameter()`, the code resolves the PHP reflection type for the `?array` parameter, which produces `NullableType(BuiltinType(array))`. It calls `isSatisfiedBy()` with a callback that checks each component of the reflection type against the PhpDoc type:

```php
$resolvedParameterType = $parameterTypeResolver->resolve($parameterType);
if ($resolvedParameterType->isSatisfiedBy(static fn (Type $t) => match (true) {
    $t instanceof BuiltinType => !$type->isIdentifiedBy($t->getTypeIdentifier()),
    $t instanceof ObjectType => !$type->isIdentifiedBy($t->getClassName()),
    default => false,
})) {
    $type = $resolvedParameterType;  // ← rich type is replaced here
}
```

For `NullableType(BuiltinType(array))`, `isSatisfiedBy()` checks two components:
1. `BuiltinType(array)` → `!CollectionType->isIdentifiedBy(ARRAY)` → `false` (array IS recognized)
2. `BuiltinType(null)` → `!CollectionType->isIdentifiedBy(NULL)` → `true` (null is NOT recognized by CollectionType)

Since at least one component returns `true`, `isSatisfiedBy()` returns `true`, and the rich `CollectionType(list<Color>)` is **completely replaced** by the bare `NullableType(BuiltinType(array))`.

## Why this is problematic

2. **Silent regression**: This worked fine with Symfony 7.3
3. **Data loss**: The type information is completely discarded, not gracefully degraded. Even if `|null` was arguably missing from the docblock, the serializer should not silently lose the element type info
4. **Debugging difficulty**: No exception, no warning. Elements are silently returned as arrays
7. **Inconsistent behavior**: Non-promoted constructors with `@param Color[]` (test 4) work correctly because `PhpDocExtractor` merges nullability from the PHP type hint. Promoted constructors and `@var` properties do not get this treatment, creating a confusing inconsistency
