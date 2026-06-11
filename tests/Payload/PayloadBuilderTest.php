<?php

declare(strict_types=1);

namespace Treblle\Symfony\Tests\Payload;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treblle\Symfony\Doctrine\QueryCollector;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Treblle\Symfony\Masking\DataMasker;
use Treblle\Symfony\Payload\PayloadBuilder;
use Treblle\Symfony\TreblleBundle;

final class PayloadBuilderTest extends TestCase
{
    private PayloadBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = $this->makeBuilder();
    }

    private function makeBuilder(array $maskedFields = []): PayloadBuilder
    {
        $config = new TreblleConfiguration(
            sdkToken: 'sdk-test-token',
            apiKey: 'api-test-key',
        );

        return new PayloadBuilder(
            configuration: $config,
            masker: new DataMasker($maskedFields),
            queryCollector: new QueryCollector(),
        );
    }

    private function build(Request $request, Response $response, ?string $routePath = null): array
    {
        return $this->builder->build($request, $response, microtime(true), $routePath, []);
    }

    public function test_build_contains_required_top_level_keys(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'GET'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
        );

        $this->assertSame('sdk-test-token', $payload['sdk_token']);
        $this->assertSame('api-test-key', $payload['api_key']);
        $this->assertSame(TreblleBundle::SDK_NAME, $payload['sdk']);
        $this->assertArrayHasKey('version', $payload);
        $this->assertArrayHasKey('data', $payload);
    }

    public function test_data_section_contains_required_keys(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'GET'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
        );

        foreach (['server', 'language', 'request', 'response', 'errors', 'queries'] as $key) {
            $this->assertArrayHasKey($key, $payload['data'], "Missing data key: {$key}");
        }
    }

    public function test_language_section_identifies_php(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'GET'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
        );

        $this->assertSame('php', $payload['data']['language']['name']);
        $this->assertSame(PHP_VERSION, $payload['data']['language']['version']);
    }

    public function test_request_json_body_is_decoded(): void
    {
        $request = Request::create('/api/users', 'POST', [], [], [], [], '{"name":"Alice"}');
        $request->headers->set('Content-Type', 'application/json');

        $payload = $this->build($request, new Response('{}', 201, ['Content-Type' => 'application/json']));

        $this->assertSame(['name' => 'Alice'], $payload['data']['request']['body']);
    }

    public function test_request_empty_body_returns_empty_array(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'GET'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
        );

        $this->assertSame([], $payload['data']['request']['body']);
    }

    public function test_request_form_data_is_captured(): void
    {
        $request = Request::create('/login', 'POST', ['username' => 'alice', 'password' => 'secret']);

        $payload = $this->build($request, new Response('{}', 200, ['Content-Type' => 'application/json']));

        $this->assertSame('alice', $payload['data']['request']['body']['username']);
    }

    public function test_request_method_is_uppercased(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'get'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
        );

        $this->assertSame('GET', $payload['data']['request']['method']);
    }

    public function test_response_json_body_is_decoded(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'GET'),
            new Response('{"id":1,"name":"Alice"}', 200, ['Content-Type' => 'application/json']),
        );

        $this->assertSame(['id' => 1, 'name' => 'Alice'], $payload['data']['response']['body']);
    }

    public function test_response_non_json_body_returns_empty_array(): void
    {
        $payload = $this->build(
            Request::create('/api/users', 'GET'),
            new Response('<html>ok</html>', 200, ['Content-Type' => 'text/html']),
        );

        $this->assertSame([], $payload['data']['response']['body']);
    }

    public function test_response_status_code_is_captured(): void
    {
        $payload = $this->build(
            Request::create('/api/users/99', 'GET'),
            new Response('{}', 404, ['Content-Type' => 'application/json']),
        );

        $this->assertSame(404, $payload['data']['response']['code']);
    }

    public function test_route_path_is_passed_through(): void
    {
        $payload = $this->build(
            Request::create('/api/users/42', 'GET'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
            '/api/users/{id}',
        );

        $this->assertSame('/api/users/{id}', $payload['data']['request']['route_path']);
    }

    public function test_sensitive_fields_are_masked_in_request_body(): void
    {
        $builder = $this->makeBuilder(['password']);
        $request = Request::create('/login', 'POST', ['username' => 'alice', 'password' => 'secret']);

        $payload = $builder->build($request, new Response('{}', 200, ['Content-Type' => 'application/json']), microtime(true), null, []);

        $this->assertSame('******', $payload['data']['request']['body']['password']);
        $this->assertSame('alice', $payload['data']['request']['body']['username']);
    }

    public function test_errors_are_included_in_payload(): void
    {
        $errors = [['source' => 'onException', 'type' => 'UNHANDLED_EXCEPTION', 'message' => 'Boom']];

        $payload = $this->builder->build(
            Request::create('/api/users', 'GET'),
            new Response('{}', 500, ['Content-Type' => 'application/json']),
            microtime(true),
            null,
            $errors,
        );

        $this->assertSame($errors, $payload['data']['errors']);
    }

    public function test_queries_from_collector_are_included(): void
    {
        $config = new TreblleConfiguration(sdkToken: 'tok', apiKey: 'key');
        $collector = new QueryCollector();
        $collector->add('SELECT 1', 0.5);

        $builder = new PayloadBuilder($config, new DataMasker(), $collector);
        $payload = $builder->build(
            Request::create('/api/users', 'GET'),
            new Response('{}', 200, ['Content-Type' => 'application/json']),
            microtime(true),
            null,
            [],
        );

        $this->assertCount(1, $payload['data']['queries']);
        $this->assertSame('SELECT 1', $payload['data']['queries'][0]['sql']);
    }
}
