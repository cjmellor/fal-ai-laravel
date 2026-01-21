<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\GetDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(DeploymentResponse::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function createDeploymentResponseFromMock(array $data): DeploymentResponse
{
    Saloon::fake([
        GetDeploymentRequest::class => MockResponse::make($data, 200),
    ]);

    $driver = new ReplicateDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://api.replicate.com',
    ]);

    return $driver->deployments()->get('test-owner', 'test-name');
}

describe('DeploymentResponse property accessors', function (): void {

    it('returns owner', function (): void {
        $response = createDeploymentResponseFromMock(['owner' => 'acme']);

        expect($response)->owner->toBe('acme');
    });

    it('returns null for missing owner', function (): void {
        $response = createDeploymentResponseFromMock([]);

        expect($response)->owner->toBeNull();
    });

    it('returns name', function (): void {
        $response = createDeploymentResponseFromMock(['name' => 'my-deployment']);

        expect($response)->name->toBe('my-deployment');
    });

    it('returns null for missing name', function (): void {
        $response = createDeploymentResponseFromMock([]);

        expect($response)->name->toBeNull();
    });

    it('returns current release', function (): void {
        $currentRelease = [
            'number' => 1,
            'model' => 'stability-ai/sdxl',
            'version' => 'abc123',
            'hardware' => 'gpu-t4',
            'min_instances' => 1,
            'max_instances' => 5,
        ];

        $response = createDeploymentResponseFromMock(['current_release' => $currentRelease]);

        expect($response)->currentRelease->toBe($currentRelease);
    });

    it('returns null for missing current release', function (): void {
        $response = createDeploymentResponseFromMock([]);

        expect($response)->currentRelease->toBeNull();
    });

});

describe('DeploymentResponse helper methods', function (): void {

    it('returns hardware from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['hardware' => 'gpu-t4'],
        ]);

        expect($response)->hardware()->toBe('gpu-t4');
    });

    it('returns null hardware when no current release', function (): void {
        $response = createDeploymentResponseFromMock([]);

        expect($response)->hardware()->toBeNull();
    });

    it('returns model from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['model' => 'stability-ai/sdxl'],
        ]);

        expect($response)->model()->toBe('stability-ai/sdxl');
    });

    it('returns version from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['version' => 'da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf'],
        ]);

        expect($response)->version()->toBe('da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf');
    });

    it('returns min instances from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['min_instances' => 1],
        ]);

        expect($response)->minInstances()->toBe(1);
    });

    it('returns max instances from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['max_instances' => 5],
        ]);

        expect($response)->maxInstances()->toBe(5);
    });

    it('returns null for missing instances', function (): void {
        $response = createDeploymentResponseFromMock([]);

        expect($response)->minInstances()->toBeNull()
            ->and($response)->maxInstances()->toBeNull();
    });

    it('returns release number from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['number' => 3],
        ]);

        expect($response)->releaseNumber()->toBe(3);
    });

    it('returns created at from current release', function (): void {
        $response = createDeploymentResponseFromMock([
            'current_release' => ['created_at' => '2024-01-15T12:00:00.000Z'],
        ]);

        expect($response)->createdAt()->toBe('2024-01-15T12:00:00.000Z');
    });

    it('returns created by from current release', function (): void {
        $createdBy = ['type' => 'user', 'username' => 'acme'];
        $response = createDeploymentResponseFromMock([
            'current_release' => ['created_by' => $createdBy],
        ]);

        expect($response)->createdBy()->toBe($createdBy);
    });

});

describe('DeploymentResponse HTTP status', function (): void {

    it('returns successful for 200 response', function (): void {
        $response = createDeploymentResponseFromMock(['owner' => 'test']);

        expect($response)->successful()->toBeTrue()
            ->and($response)->failed()->toBeFalse()
            ->and($response)->status()->toBe(200);
    });

});
