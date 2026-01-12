<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Enums\PredictionStatus;
use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\GetPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(PredictionResponse::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function createPredictionResponseFromMock(array $data): PredictionResponse
{
    Saloon::fake([
        GetPredictionRequest::class => MockResponse::make($data, 200),
    ]);

    $driver = new ReplicateDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://api.replicate.com',
    ]);

    return $driver->status('test-id');
}

describe('PredictionResponse property accessors', function (): void {

    it('returns prediction ID', function (): void {
        $response = createPredictionResponseFromMock(['id' => 'pred-123']);

        expect($response->id)->toBe('pred-123');
    });

    it('returns empty string for missing ID', function (): void {
        $response = createPredictionResponseFromMock([]);

        expect($response->id)->toBe('');
    });

    it('returns prediction status as string', function (): void {
        $response = createPredictionResponseFromMock(['status' => 'processing']);

        expect($response->predictionStatus)->toBe('processing');
    });

    it('returns prediction status as enum', function (): void {
        $response = createPredictionResponseFromMock(['status' => 'succeeded']);

        expect($response->predictionStatusEnum())
            ->toBeInstanceOf(PredictionStatus::class)
            ->and($response->predictionStatusEnum())->toBe(PredictionStatus::Succeeded);
    });

    it('returns null for unknown status enum', function (): void {
        $response = createPredictionResponseFromMock(['status' => 'unknown-status']);

        expect($response->predictionStatusEnum())->toBeNull();
    });

    it('returns model identifier', function (): void {
        $response = createPredictionResponseFromMock(['model' => 'stability-ai/sdxl']);

        expect($response->model)->toBe('stability-ai/sdxl');
    });

    it('returns null for missing model', function (): void {
        $response = createPredictionResponseFromMock([]);

        expect($response->model)->toBeNull();
    });

    it('returns version ID', function (): void {
        $response = createPredictionResponseFromMock(['version' => 'abc123']);

        expect($response->version)->toBe('abc123');
    });

    it('returns input parameters', function (): void {
        $response = createPredictionResponseFromMock(['input' => ['prompt' => 'test']]);

        expect($response->input)->toBe(['prompt' => 'test']);
    });

    it('returns empty array for missing input', function (): void {
        $response = createPredictionResponseFromMock([]);

        expect($response->input)->toBe([]);
    });

    it('returns output data', function (): void {
        $response = createPredictionResponseFromMock(['output' => ['https://example.com/image.png']]);

        expect($response->output)->toBe(['https://example.com/image.png']);
    });

    it('returns null for missing output', function (): void {
        $response = createPredictionResponseFromMock([]);

        expect($response->output)->toBeNull();
    });

    it('returns error message', function (): void {
        $response = createPredictionResponseFromMock(['error' => 'Something went wrong']);

        expect($response->error)->toBe('Something went wrong');
    });

    it('returns logs', function (): void {
        $response = createPredictionResponseFromMock(['logs' => 'Processing started...']);

        expect($response->logs)->toBe('Processing started...');
    });

    it('returns URLs', function (): void {
        $urls = ['get' => 'https://api.replicate.com/v1/predictions/123'];
        $response = createPredictionResponseFromMock(['urls' => $urls]);

        expect($response->urls)->toBe($urls);
    });

    it('returns timestamps', function (): void {
        $response = createPredictionResponseFromMock([
            'created_at' => '2024-01-01T00:00:00Z',
            'started_at' => '2024-01-01T00:00:01Z',
            'completed_at' => '2024-01-01T00:00:10Z',
        ]);

        expect($response->createdAt)->toBe('2024-01-01T00:00:00Z')
            ->and($response->startedAt)->toBe('2024-01-01T00:00:01Z')
            ->and($response->completedAt)->toBe('2024-01-01T00:00:10Z');
    });

    it('returns metrics data', function (): void {
        $response = createPredictionResponseFromMock([
            'metrics' => ['predict_time' => 1.5, 'total_time' => 2.0],
        ]);

        expect($response->metricsData)->toEqual(['predict_time' => 1.5, 'total_time' => 2.0])
            ->and($response->predictTime)->toEqual(1.5)
            ->and($response->totalTime)->toEqual(2.0);
    });

    it('returns null for missing metrics', function (): void {
        $response = createPredictionResponseFromMock([]);

        expect($response->predictTime)->toBeNull()
            ->and($response->totalTime)->toBeNull();
    });

});

describe('PredictionResponse status helpers', function (): void {

    it('isSucceeded returns true only for succeeded status', function (): void {
        $succeeded = createPredictionResponseFromMock(['status' => 'succeeded']);
        $processing = createPredictionResponseFromMock(['status' => 'processing']);

        expect($succeeded->isSucceeded())->toBeTrue()
            ->and($processing->isSucceeded())->toBeFalse();
    });

    it('isFailed returns true only for failed status', function (): void {
        $failed = createPredictionResponseFromMock(['status' => 'failed']);
        $succeeded = createPredictionResponseFromMock(['status' => 'succeeded']);

        expect($failed->isFailed())->toBeTrue()
            ->and($succeeded->isFailed())->toBeFalse();
    });

    it('isCanceled returns true only for canceled status', function (): void {
        $canceled = createPredictionResponseFromMock(['status' => 'canceled']);
        $succeeded = createPredictionResponseFromMock(['status' => 'succeeded']);

        expect($canceled->isCanceled())->toBeTrue()
            ->and($succeeded->isCanceled())->toBeFalse();
    });

    it('isRunning returns true for starting and processing statuses', function (): void {
        $starting = createPredictionResponseFromMock(['status' => 'starting']);
        $processing = createPredictionResponseFromMock(['status' => 'processing']);
        $succeeded = createPredictionResponseFromMock(['status' => 'succeeded']);

        expect($starting->isRunning())->toBeTrue()
            ->and($processing->isRunning())->toBeTrue()
            ->and($succeeded->isRunning())->toBeFalse();
    });

    it('isTerminal returns true for terminal statuses', function (): void {
        $succeeded = createPredictionResponseFromMock(['status' => 'succeeded']);
        $failed = createPredictionResponseFromMock(['status' => 'failed']);
        $canceled = createPredictionResponseFromMock(['status' => 'canceled']);
        $processing = createPredictionResponseFromMock(['status' => 'processing']);

        expect($succeeded->isTerminal())->toBeTrue()
            ->and($failed->isTerminal())->toBeTrue()
            ->and($canceled->isTerminal())->toBeTrue()
            ->and($processing->isTerminal())->toBeFalse();
    });

    it('handles unknown status gracefully', function (): void {
        $unknown = createPredictionResponseFromMock(['status' => 'unknown']);

        expect($unknown->isSucceeded())->toBeFalse()
            ->and($unknown->isFailed())->toBeFalse()
            ->and($unknown->isCanceled())->toBeFalse()
            ->and($unknown->isRunning())->toBeFalse()
            ->and($unknown->isTerminal())->toBeFalse();
    });

    it('handles missing status gracefully', function (): void {
        $noStatus = createPredictionResponseFromMock([]);

        expect($noStatus->isSucceeded())->toBeFalse()
            ->and($noStatus->isFailed())->toBeFalse()
            ->and($noStatus->isCanceled())->toBeFalse()
            ->and($noStatus->isRunning())->toBeFalse()
            ->and($noStatus->isTerminal())->toBeFalse();
    });

});

describe('PredictionResponse HTTP status', function (): void {

    it('returns successful for 200 response', function (): void {
        $response = createPredictionResponseFromMock(['id' => 'test']);

        expect($response->successful())->toBeTrue()
            ->and($response->failed())->toBeFalse()
            ->and($response->status())->toBe(200);
    });

});
