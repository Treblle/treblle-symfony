<?php

declare(strict_types=1);

namespace Tests\Treblle\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Treblle\Contract\LanguageDataProvider;
use Treblle\Contract\ServerDataProvider;
use Treblle\InMemoryErrorDataProvider;
use Treblle\Model\Language;
use Treblle\Model\Os;
use Treblle\Model\Server;
use Treblle\PayloadAnonymizer;
use Treblle\Symfony\DataProvider;
use Treblle\Symfony\EventSubscriber\TreblleEventSubscriber;
use Treblle\Treblle;

/**
 * @internal
 * @coversNothing
 *
 * @small
 */
final class TreblleIntegrationTest extends TestCase
{
    private Validator $validator;

    private string $schemaPath;

    private MockHandler $mockHandler;

    private array $container = [];

    /** @var ServerDataProvider&MockObject */
    private ServerDataProvider $serverDataProvider;

    /** @var LanguageDataProvider&MockObject */
    private LanguageDataProvider $languageDataProvider;

    private InMemoryErrorDataProvider $errorDataProvider;

    private DataProvider $dataProvider;

    private Treblle $treblle;

    private TreblleEventSubscriber $eventSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator();
        $this->schemaPath = __DIR__.'/../vendor/treblle/treblle-php/schema/request.json';

        $this->mockHandler = new MockHandler([]);
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $this->serverDataProvider = $this->createMock(ServerDataProvider::class);
        $this->languageDataProvider = $this->createMock(LanguageDataProvider::class);
        $this->errorDataProvider = new InMemoryErrorDataProvider();
        $this->dataProvider = new DataProvider(new PayloadAnonymizer([]));

        $this->treblle = new Treblle(
            'http://127.0.0.1',
            'my api key',
            'my project id',
            $client,
            $this->serverDataProvider,
            $this->languageDataProvider,
            $this->dataProvider,
            $this->dataProvider,
            $this->errorDataProvider,
            true
        );

        $this->eventSubscriber = new TreblleEventSubscriber($this->treblle, new NullLogger());
    }

    public function provideTestData(): iterable
    {
        $server = new Server(
            '1.1.1.1',
            'Europe/London',
            'My Software',
            'My Signature',
            'https',
            new Os('Ubuntu', '14.04', 'x86'),
            'utf-8'
        );
        $language = new Language('php', '8.0', 'Off', 'Off');

        yield 'request and response without errors' => [
            'server' => $server,
            'language' => $language,
            'request' => Request::create(
                'http://localhost/foo',
                'POST',
                ['foo' => 'bar'],
                [],
                [],
                [
                    'HTTP_HOST' => 'localhost',
                    'REMOTE_ADDR' => '8.8.8.8',
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_USER_AGENT' => 'browser',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
                    'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                ],
                \Safe\json_encode(['baz' => 'bash']),
            ),
            'response' => new Response(
                \Safe\json_encode(['status' => 'ok']),
                200,
                [
                    'Accept' => 'application/json',
                    'Content-type' => 'application/json',
                    'cache-control' => 'no-cache, private',
                    'date' => 'Mon, 25 Oct 2021 21:22:33 GMT',
                ],
            ),
            'errors' => [],
            'expectedRequest' => [
                'api_key' => 'my api key',
                'project_id' => 'my project id',
                'version' => 0.8,
                'sdk' => 'php',
                'data' => [
                    'server' => [
                        'ip' => '1.1.1.1',
                        'timezone' => 'Europe/London',
                        'software' => 'My Software',
                        'signature' => 'My Signature',
                        'protocol' => 'https',
                        'os' => [
                            'name' => 'Ubuntu',
                            'release' => '14.04',
                            'architecture' => 'x86',
                        ],
                        'encoding' => 'utf-8',
                    ],
                    'language' => [
                        'name' => 'php',
                        'version' => '8.0',
                        'expose_php' => 'Off',
                        'display_errors' => 'Off',
                    ],
                    'request' => [
                        'timestamp' => 'YYYY-MM-DD hh:mm:ss',
                        'ip' => '8.8.8.8',
                        'url' => 'http://localhost/foo',
                        'user_agent' => 'browser',
                        'method' => 'POST',
                        'headers' => [
                            'host' => 'localhost',
                            'user-agent' => 'browser',
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                            'accept-language' => 'en-us,en;q=0.5',
                            'accept-charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                        ],
                        'body' => [
                            'foo' => 'bar',
                        ],
                        'raw' => [
                            'baz' => 'bash',
                        ],
                    ],
                    'response' => [
                        'headers' => [
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                            'cache-control' => 'no-cache, private',
                            'date' => 'Mon, 25 Oct 2021 21:22:33 GMT',
                        ],
                        'code' => 200,
                        'size' => 15,
                        'body' => [
                            'status' => 'ok',
                        ],
                    ],
                    'errors' => [],
                ],
            ],
        ];

        yield 'request and response with a single error' => [
            'server' => $server,
            'language' => $language,
            'request' => Request::create(
                'http://localhost/foo',
                'POST',
                ['foo' => 'bar'],
                [],
                [],
                [
                    'HTTP_HOST' => 'localhost',
                    'REMOTE_ADDR' => '8.8.8.8',
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_USER_AGENT' => 'browser',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
                    'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                ],
                \Safe\json_encode(['baz' => 'bash']),
            ),
            'response' => new Response(
                \Safe\json_encode(['status' => 'ok']),
                200,
                [
                    'Accept' => 'application/json',
                    'Content-type' => 'application/json',
                    'cache-control' => 'no-cache, private',
                    'date' => 'Mon, 25 Oct 2021 21:22:33 GMT',
                ],
            ),
            'errors' => [
                new \Exception(
                    'message',
                    1,
                    null,
                ),
                new \LogicException(
                    'UNHANDLED_EXCEPTION',
                    2,
                    null,
                ),
            ],
            'expectedRequest' => [
                'api_key' => 'my api key',
                'project_id' => 'my project id',
                'version' => 0.8,
                'sdk' => 'php',
                'data' => [
                    'server' => [
                        'ip' => '1.1.1.1',
                        'timezone' => 'Europe/London',
                        'software' => 'My Software',
                        'signature' => 'My Signature',
                        'protocol' => 'https',
                        'os' => [
                            'name' => 'Ubuntu',
                            'release' => '14.04',
                            'architecture' => 'x86',
                        ],
                        'encoding' => 'utf-8',
                    ],
                    'language' => [
                        'name' => 'php',
                        'version' => '8.0',
                        'expose_php' => 'Off',
                        'display_errors' => 'Off',
                    ],
                    'request' => [
                        'timestamp' => 'YYYY-MM-DD hh:mm:ss',
                        'ip' => '8.8.8.8',
                        'url' => 'http://localhost/foo',
                        'user_agent' => 'browser',
                        'method' => 'POST',
                        'headers' => [
                            'host' => 'localhost',
                            'user-agent' => 'browser',
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                            'accept-language' => 'en-us,en;q=0.5',
                            'accept-charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                        ],
                        'body' => [
                            'foo' => 'bar',
                        ],
                        'raw' => [
                            'baz' => 'bash',
                        ],
                    ],
                    'response' => [
                        'headers' => [
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                            'cache-control' => 'no-cache, private',
                            'date' => 'Mon, 25 Oct 2021 21:22:33 GMT',
                        ],
                        'code' => 200,
                        'size' => 15,
                        'body' => [
                            'status' => 'ok',
                        ],
                    ],
                    'errors' => [
                        [
                            'source' => 'onException',
                            'type' => 'UNHANDLED_EXCEPTION',
                            'message' => 'message',
                            'file' => __FILE__,
                            'line' => 236,
                        ],
                        [
                            'source' => 'onException',
                            'type' => 'UNHANDLED_EXCEPTION',
                            'message' => 'UNHANDLED_EXCEPTION',
                            'file' => __FILE__,
                            'line' => 241,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTestData
     */
    public function test_it_correctly_serializes_request_data_on_shutdown(
        Server $server,
        Language $language,
        Request $httpRequest,
        Response $httpResponse,
        array $errors,
        array $expectedRequest
    ): void {
        $this->serverDataProvider->expects($this->once())
            ->method('getServer')
            ->willReturn($server);
        $this->languageDataProvider->expects($this->once())
            ->method('getLanguage')
            ->willReturn($language);

        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent->expects($this->once())->method('isMasterRequest')->willReturn(true);
        $requestEvent->expects($this->once())->method('getRequest')->willReturn($httpRequest);
        $this->dataProvider->onKernelRequest($requestEvent);

        $responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            HttpKernelInterface::MASTER_REQUEST,
            $httpResponse
        );
        $this->dataProvider->onKernelResponse($responseEvent);

        foreach ($errors as $error) {
            $exceptionEvent = new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                $this->createMock(Request::class),
                HttpKernelInterface::MASTER_REQUEST,
                $error
            );
            $this->eventSubscriber->onException($exceptionEvent);
        }

        $this->mockHandler->append(new \GuzzleHttp\Psr7\Response(201));
        $this->eventSubscriber->onKernelTerminate($this->createMock(KernelEvent::class));

        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Request::class, $request);

        $requestBody = $request->getBody()->getContents();
        $decodedRequestBody = \Safe\json_decode($requestBody, true);
        // Don't compare timestamp
        $expectedRequest['data']['request']['timestamp'] = $decodedRequestBody['data']['request']['timestamp'];

        // Don't compare load time
        $expectedRequest['data']['response']['load_time'] = $decodedRequestBody['data']['response']['load_time'];

        $this->assertEquals($decodedRequestBody, $expectedRequest);

        $requestBody = \Safe\json_decode($requestBody);
        $this->validator->validate($requestBody, (object) ['$ref' => 'file://'.realpath($this->schemaPath)]);

        $this->assertTrue(
            $this->validator->isValid(),
            array_reduce(
                $this->validator->getErrors(),
                static fn (string $carry, array $error) => $carry."\n".\Safe\json_encode($error),
                ''
            )
        );
    }
}
