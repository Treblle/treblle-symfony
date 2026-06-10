<?php

declare(strict_types=1);

namespace Treblle\Symfony\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Treblle\Symfony\Routing\PathMatcher;

final class PathMatcherTest extends TestCase
{
    private PathMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PathMatcher();
    }

    public function test_returns_false_for_empty_excluded_list(): void
    {
        $this->assertFalse($this->matcher->isExcluded('/api/users', []));
    }

    public function test_matches_exact_path(): void
    {
        $this->assertTrue($this->matcher->isExcluded('/health', ['/health']));
    }

    public function test_matches_regardless_of_leading_slash(): void
    {
        $this->assertTrue($this->matcher->isExcluded('/health', ['health']));
        $this->assertTrue($this->matcher->isExcluded('health', ['/health']));
    }

    public function test_does_not_match_different_path(): void
    {
        $this->assertFalse($this->matcher->isExcluded('/api/users', ['/api/orders']));
    }

    public function test_wildcard_matches_subpaths(): void
    {
        $this->assertTrue($this->matcher->isExcluded('/admin/settings', ['admin/*']));
        $this->assertTrue($this->matcher->isExcluded('/admin/users/1', ['admin/*']));
    }

    public function test_wildcard_does_not_match_different_prefix(): void
    {
        $this->assertFalse($this->matcher->isExcluded('/api/users', ['admin/*']));
    }

    public function test_wildcard_matches_the_prefix_path_itself(): void
    {
        $this->assertTrue($this->matcher->isExcluded('/admin', ['admin/*']));
    }

    public function test_bare_wildcard_matches_any_path(): void
    {
        $this->assertTrue($this->matcher->isExcluded('/anything/at/all', ['*']));
        $this->assertTrue($this->matcher->isExcluded('/api/v1/users', ['*']));
    }

    public function test_first_matching_rule_wins(): void
    {
        $this->assertTrue($this->matcher->isExcluded('/api/users', ['/other', '/api/users']));
    }
}
