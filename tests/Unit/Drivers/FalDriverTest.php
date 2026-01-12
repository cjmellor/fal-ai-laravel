<?php

declare(strict_types=1);

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Contracts\SupportsPlatform;
use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Enums\RequestMode;
use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\CancelRequest;
use Cjmellor\FalAi\Requests\FetchRequestStatusRequest;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Cjmellor\FalAi\Requests\StreamRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Cjmellor\FalAi\Responses\ResultResponse;
use Cjmellor\FalAi\Responses\StatusResponse;
use Cjmellor\FalAi\Responses\StreamResponse;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Cjmellor\FalAi\Support\FluentRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

covers(FalDriver::class);

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.drivers.fal.api_key' => 'test-api-key',
        'fal-ai.drivers.fal.base_url' => 'https://queue.fal.run',
        'fal-ai.drivers.fal.sync_url' => 'https://fal.run',
        'fal-ai.drivers.fal.platform_base_url' => 'https://api.fal.ai',
        'fal-ai.drivers.fal.default_model' => 'test-model',
    ]);
});

function createFalDriverForDriverTests(): FalDriver
{
    return new FalDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'platform_base_url' => 'https://api.fal.ai',
        'default_model' => 'test-model',
    ]);
}

describe('FalDriver Implementation', function (): void {

    it('implements DriverInterface', function (): void {
        $driver = createFalDriverForDriverTests();

        expect($driver)->toBeInstanceOf(DriverInterface::class);
    });

    it('implements SupportsPlatform', function (): void {
        $driver = createFalDriverForDriverTests();

        expect($driver)->toBeInstanceOf(SupportsPlatform::class);
    });

    it('returns correct driver name', function (): void {
        $driver = createFalDriverForDriverTests();

        expect($driver->getName())->toBe('fal');
    });

    it('creates FluentRequest from model method', function (): void {
        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell');

        expect($request)->toBeInstanceOf(FluentRequest::class);
    });

});

describe('FalDriver Model Operations', function (): void {

    it('can submit a request', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'test-request-123',
                'status_url' => 'https://queue.fal.run/test-model/requests/test-request-123/status',
                'response_url' => 'https://queue.fal.run/test-model/requests/test-request-123',
                'cancel_url' => 'https://queue.fal.run/test-model/requests/test-request-123/cancel',
            ], 200),
        ]);

        $driver = createFalDriverForDriverTests();

        $response = $driver->model('fal-ai/flux/schnell')
            ->prompt('A beautiful sunset')
            ->run();

        expect($response)->toBeInstanceOf(SubmitResponse::class)
            ->and($response->requestId)->toBe('test-request-123');
    });

    it('can check request status', function (): void {
        MockClient::global([
            FetchRequestStatusRequest::class => MockResponse::make([
                'status' => 'IN_PROGRESS',
                'logs' => [],
                'partial' => null,
            ], 200),
        ]);

        $driver = createFalDriverForDriverTests();

        $response = $driver->status('test-request-123', 'fal-ai/flux/schnell');

        expect($response)->toBeInstanceOf(StatusResponse::class)
            ->and($response->json()['status'])->toBe('IN_PROGRESS');
    });

    it('can get request result', function (): void {
        MockClient::global([
            GetResultRequest::class => MockResponse::make([
                'images' => [
                    ['url' => 'https://example.com/image.jpg'],
                ],
                'seed' => 12345,
            ], 200),
        ]);

        $driver = createFalDriverForDriverTests();

        $response = $driver->result('test-request-123', 'fal-ai/flux/schnell');

        expect($response)->toBeInstanceOf(ResultResponse::class)
            ->and($response->json()['images'])->toHaveCount(1);
    });

    it('can cancel a request', function (): void {
        MockClient::global([
            CancelRequest::class => MockResponse::make([
                'cancelled' => true,
                'request_id' => 'test-request-123',
            ], 200),
        ]);

        $driver = createFalDriverForDriverTests();

        $result = $driver->cancel('test-request-123', 'fal-ai/flux/schnell');

        expect($result)->toBeTrue();
    });

    it('can stream model execution', function (): void {
        MockClient::global([
            StreamRequest::class => MockResponse::make('data: {"step":1}\n\n', 200, [
                'Content-Type' => 'text/event-stream',
            ]),
        ]);

        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell')->prompt('A beautiful sunset');
        $response = $driver->stream($request);

        expect($response)->toBeInstanceOf(StreamResponse::class);
    });

});

describe('FalDriver Mode Switching', function (): void {

    it('uses queue mode by default', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'queue-request-123',
            ], 200),
        ]);

        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell')
            ->prompt('Test prompt');

        expect($request->getMode())->toBe(RequestMode::Queue);
    });

    it('can switch to sync mode', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'sync-request-123',
            ], 200),
        ]);

        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell')
            ->sync()
            ->prompt('Test prompt');

        expect($request->getMode())->toBe(RequestMode::Sync);
    });

    it('can use queue method explicitly', function (): void {
        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell')
            ->queue()
            ->prompt('Test prompt');

        expect($request->getMode())->toBe(RequestMode::Queue);
    });

});

describe('FalDriver Platform APIs', function (): void {

    it('provides access to platform APIs', function (): void {
        $driver = createFalDriverForDriverTests();

        $platform = $driver->platform();

        expect($platform)->toBeInstanceOf(Platform::class);
    });

});

describe('FalDriver Fluent Interface', function (): void {

    it('supports all common fluent methods', function (): void {
        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell')
            ->prompt('A beautiful landscape')
            ->imageSize('landscape_4_3')
            ->numImages(2)
            ->seed(12345)
            ->guidanceScale(7.5)
            ->numInferenceSteps(50)
            ->negativePrompt('blurry, low quality');

        $data = $request->toArray();

        expect($data['prompt'])->toBe('A beautiful landscape')
            ->and($data['image_size'])->toBe('landscape_4_3')
            ->and($data['num_images'])->toBe(2)
            ->and($data['seed'])->toBe(12345)
            ->and($data['guidance_scale'])->toBe(7.5)
            ->and($data['num_inference_steps'])->toBe(50)
            ->and($data['negative_prompt'])->toBe('blurry, low quality');
    });

    it('supports webhook configuration', function (): void {
        $driver = createFalDriverForDriverTests();

        $request = $driver->model('fal-ai/flux/schnell')
            ->prompt('Test')
            ->withWebhook('https://example.com/webhook');

        expect($request->getWebhook())->toBe('https://example.com/webhook')
            ->and($request->getMode())->toBe(RequestMode::Queue);
    });

    it('supports conditional when method', function (): void {
        $driver = createFalDriverForDriverTests();
        $addSeed = true;

        $request = $driver->model('fal-ai/flux/schnell')
            ->prompt('Test')
            ->when($addSeed, fn ($req) => $req->seed(42));

        expect($request->toArray()['seed'])->toBe(42);
    });

});
