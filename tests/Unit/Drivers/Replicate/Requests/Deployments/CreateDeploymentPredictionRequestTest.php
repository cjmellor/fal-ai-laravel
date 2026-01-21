<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentPredictionRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;

covers(CreateDeploymentPredictionRequest::class);

describe('CreateDeploymentPredictionRequest', function (): void {

    it('uses POST method', function (): void {
        $request = new CreateDeploymentPredictionRequest('acme', 'my-deployment', ['prompt' => 'test']);

        expect($request->getMethod())->toBe(Method::POST);
    });

    it('resolves endpoint with owner and name', function (): void {
        $request = new CreateDeploymentPredictionRequest('acme', 'my-deployment', ['prompt' => 'test']);

        expect($request->resolveEndpoint())->toBe('/v1/deployments/acme/my-deployment/predictions');
    });

    it('returns body with input', function (): void {
        $input = ['prompt' => 'A beautiful sunset'];
        $request = new CreateDeploymentPredictionRequest('acme', 'my-deployment', $input);

        $body = $request->defaultBody();

        expect($body)->toHaveKey('input')
            ->and($body['input'])->toBe($input);
    });

    it('includes webhook url in body when provided', function (): void {
        $request = new CreateDeploymentPredictionRequest(
            owner: 'acme',
            name: 'my-deployment',
            input: ['prompt' => 'test'],
            webhookUrl: 'https://example.com/webhook',
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('webhook')
            ->and($body['webhook'])->toBe('https://example.com/webhook');
    });

    it('excludes webhook from body when not provided', function (): void {
        $request = new CreateDeploymentPredictionRequest('acme', 'my-deployment', ['prompt' => 'test']);

        $body = $request->defaultBody();

        expect($body)->not->toHaveKey('webhook');
    });

    it('includes webhook events filter when provided', function (): void {
        $request = new CreateDeploymentPredictionRequest(
            owner: 'acme',
            name: 'my-deployment',
            input: ['prompt' => 'test'],
            webhookUrl: 'https://example.com/webhook',
            webhookEventsFilter: ['start', 'completed'],
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('webhook_events_filter')
            ->and($body['webhook_events_filter'])->toBe(['start', 'completed']);
    });

    it('excludes webhook events filter when empty', function (): void {
        $request = new CreateDeploymentPredictionRequest(
            owner: 'acme',
            name: 'my-deployment',
            input: ['prompt' => 'test'],
            webhookUrl: null,
            webhookEventsFilter: [],
        );

        $body = $request->defaultBody();

        expect($body)->not->toHaveKey('webhook_events_filter');
    });

    it('implements HasBody interface', function (): void {
        $request = new CreateDeploymentPredictionRequest('acme', 'my-deployment', ['prompt' => 'test']);

        expect($request)->toBeInstanceOf(HasBody::class);
    });

});
