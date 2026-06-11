<?php

declare(strict_types=1);

namespace Treblle\Symfony\Tests\Masking;

use PHPUnit\Framework\TestCase;
use Treblle\Symfony\Masking\DataMasker;

final class DataMaskerTest extends TestCase
{
    public function test_masks_known_field(): void
    {
        $masker = new DataMasker(['password']);
        $result = $masker->mask(['password' => 'secret123']);
        $this->assertSame('*********', $result['password']);
    }

    public function test_masking_is_case_insensitive(): void
    {
        $masker = new DataMasker(['password']);
        $result = $masker->mask(['PASSWORD' => 'secret']);
        $this->assertSame('******', $result['PASSWORD']);
    }

    public function test_does_not_mask_unknown_fields(): void
    {
        $masker = new DataMasker(['password']);
        $result = $masker->mask(['username' => 'alice']);
        $this->assertSame('alice', $result['username']);
    }

    public function test_masks_nested_array(): void
    {
        $masker = new DataMasker(['token']);
        $result = $masker->mask(['user' => ['token' => 'abc123']]);
        $this->assertSame('******', $result['user']['token']);
    }

    public function test_preserves_null_for_masked_field(): void
    {
        $masker = new DataMasker(['password']);
        $result = $masker->mask(['password' => null]);
        $this->assertNull($result['password']);
    }

    public function test_preserves_empty_string_for_masked_field(): void
    {
        $masker = new DataMasker(['password']);
        $result = $masker->mask(['password' => '']);
        $this->assertSame('', $result['password']);
    }

    public function test_masks_integer_value(): void
    {
        $masker = new DataMasker(['pin']);
        $result = $masker->mask(['pin' => 1234]);
        $this->assertSame('****', $result['pin']);
    }

    public function test_masks_float_value(): void
    {
        $masker = new DataMasker(['amount']);
        $result = $masker->mask(['amount' => 9.99]);
        $this->assertSame('****', $result['amount']);
    }

    public function test_masks_array_value_element_by_element(): void
    {
        $masker = new DataMasker(['tokens']);
        $result = $masker->mask(['tokens' => ['abc', 'def']]);
        $this->assertSame(['***', '***'], $result['tokens']);
    }

    public function test_non_array_input_passes_through_unchanged(): void
    {
        $masker = new DataMasker(['password']);
        $this->assertSame('hello', $masker->mask('hello'));
        $this->assertNull($masker->mask(null));
        $this->assertSame(42, $masker->mask(42));
    }

    public function test_empty_masked_keys_list_masks_nothing(): void
    {
        $masker = new DataMasker([]);
        $result = $masker->mask(['password' => 'secret', 'token' => 'abc']);
        $this->assertSame('secret', $result['password']);
        $this->assertSame('abc', $result['token']);
    }

    public function test_returns_empty_array_beyond_max_depth(): void
    {
        $masker = new DataMasker([]);
        // Build 12-deep nested array — MAX_DEPTH is 10, so innermost is truncated
        $data = ['key' => 'value'];
        for ($i = 0; $i < 12; $i++) {
            $data = ['nested' => $data];
        }
        $result = $masker->mask($data);
        $this->assertIsArray($result);
        // Walk to depth 10 — should hit an empty array before reaching 'value'
        $node = $result;
        for ($i = 0; $i < 10; $i++) {
            $this->assertArrayHasKey('nested', $node);
            $node = $node['nested'];
        }
        $this->assertSame([], $node);
    }
}
