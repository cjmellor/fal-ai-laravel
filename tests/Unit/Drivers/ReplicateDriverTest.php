<?php

declare(strict_types=1);

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\CancelPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\CreatePredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\GetPredictionRequest;
use Cjmellor\FalAi\Exceptions\PlatformNotSupportedException;
use Cjmellor\FalAi\Support\FluentRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(ReplicateDriver::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
        'fal-ai.drivers.replicate.default_model' => 'stability-ai/sdxl',
    ]);
});

function createReplicateDriver(): ReplicateDriver
{
    return new ReplicateDriver([
        'api_key' => 'test-replicate-key',
        'base_url' => 'https://api.replicate.com',
        'default_model' => 'stability-ai/sdxl',
    ]);
}

describe('ReplicateDriver Implementation', function (): void {

    it('implements DriverInterface', function (): void {
        $driver = createReplicateDriver();

        expect($driver)->toBeInstanceOf(DriverInterface::class);
    });

    it('returns correct driver name', function (): void {
        $driver = createReplicateDriver();

        expect($driver->getName())->toBe('replicate');
    });

    it('creates FluentRequest from model method', function (): void {
        $driver = createReplicateDriver();

        $request = $driver->model('stability-ai/sdxl:abc123');

        expect($request)->toBeInstanceOf(FluentRequest::class);
    });

});

describe('ReplicateDriver Predictions', function (): void {

    it('can create a prediction', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::fixture('Replicate/create-prediction-success'),
        ]);

        $driver = createReplicateDriver();

        $response = $driver->model('stability-ai/sdxl:da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf')
            ->prompt('A beautiful sunset over mountains')
            ->run();

        expect($response)->toBeInstanceOf(Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse::class)
            ->and($response->id)->toBe('gm3qorzdhgbfurvjtvhg6dckhu')
            ->and($response->predictionStatus)->toBe('starting')
            ->and($response->model)->toBe('stability-ai/sdxl');
    });

    it('can get prediction status', function (): void {
        Saloon::fake([
            GetPredictionRequest::class => MockResponse::fixture('Replicate/get-prediction-processing'),
        ]);

        $driver = createReplicateDriver();

        $response = $driver->status('gm3qorzdhgbfurvjtvhg6dckhu');

        expect($response)->toBeInstanceOf(Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse::class)
            ->and($response->id)->toBe('gm3qorzdhgbfurvjtvhg6dckhu')
            ->and($response->predictionStatus)->toBe('processing')
            ->and($response->isRunning())->toBeTrue()
            ->and($response->isTerminal())->toBeFalse();
    });

    it('can get completed prediction result', function (): void {
        Saloon::fake([
            GetPredictionRequest::class => MockResponse::fixture('Replicate/get-prediction-succeeded'),
        ]);

        $driver = createReplicateDriver();

        $response = $driver->result('gm3qorzdhgbfurvjtvhg6dckhu');

        expect($response)->toBeInstanceOf(Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse::class)
            ->and($response->predictionStatus)->toBe('succeeded')
            ->and($response->output)->toBeArray()
            ->and($response->output)->toHaveCount(2)
            ->and($response->isTerminal())->toBeTrue()
            ->and($response->isSucceeded())->toBeTrue();
    });

    it('can get failed prediction', function (): void {
        Saloon::fake([
            GetPredictionRequest::class => MockResponse::fixture('Replicate/get-prediction-failed'),
        ]);

        $driver = createReplicateDriver();

        $response = $driver->result('gm3qorzdhgbfurvjtvhg6dckhu');

        expect($response->predictionStatus)->toBe('failed')
            ->and($response->error)->toBe('Model failed to generate output: NSFW content detected')
            ->and($response->isFailed())->toBeTrue()
            ->and($response->isSucceeded())->toBeFalse();
    });

    it('can cancel a prediction', function (): void {
        Saloon::fake([
            CancelPredictionRequest::class => MockResponse::fixture('Replicate/cancel-prediction-success'),
        ]);

        $driver = createReplicateDriver();

        $result = $driver->cancel('gm3qorzdhgbfurvjtvhg6dckhu');

        expect($result)->toBeTrue();
    });

});

describe('ReplicateDriver Fluent Interface', function (): void {

    it('can use fluent interface with prompt', function (): void {
        $driver = createReplicateDriver();

        $request = $driver->model('stability-ai/sdxl:abc123')
            ->prompt('A majestic dragon');

        expect($request->toArray()['prompt'])->toBe('A majestic dragon');
    });

    it('can use fluent interface with custom parameters', function (): void {
        $driver = createReplicateDriver();

        $request = $driver->model('stability-ai/sdxl:abc123')
            ->prompt('A beautiful landscape')
            ->numOutputs(4)
            ->guidanceScale(7.5);

        $data = $request->toArray();

        expect($data['prompt'])->toBe('A beautiful landscape')
            ->and($data['num_outputs'])->toBe(4)
            ->and($data['guidance_scale'])->toBe(7.5);
    });

    it('can use with method for batch parameters', function (): void {
        $driver = createReplicateDriver();

        $request = $driver->model('stability-ai/sdxl:abc123')
            ->with([
                'prompt' => 'A sunset',
                'negative_prompt' => 'ugly, blurry',
                'num_outputs' => 2,
            ]);

        $data = $request->toArray();

        expect($data['prompt'])->toBe('A sunset')
            ->and($data['negative_prompt'])->toBe('ugly, blurry')
            ->and($data['num_outputs'])->toBe(2);
    });

});

describe('ReplicateDriver Platform APIs', function (): void {

    it('throws PlatformNotSupportedException when accessing platform()', function (): void {
        $driver = createReplicateDriver();

        expect(fn () => $driver->platform())
            ->toThrow(PlatformNotSupportedException::class, "Platform APIs are not supported by the 'replicate' driver. Platform APIs are only available with the 'fal' driver.");
    });

});

describe('ReplicateDriver Streaming', function (): void {

    it('throws exception for stream as not yet implemented', function (): void {
        $driver = createReplicateDriver();
        $request = $driver->model('stability-ai/sdxl:abc123')
            ->prompt('Test');

        expect(fn () => $driver->stream($request))
            ->toThrow(Cjmellor\FalAi\Exceptions\RequestFailedException::class, 'Streaming is not yet supported for the Replicate driver. Use run() and poll status() instead.');
    });

});
