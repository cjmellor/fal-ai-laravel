<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\FalAiException;
use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Cjmellor\FalAi\Exceptions\RequestFailedException;
use Cjmellor\FalAi\Requests\GetResultRequest;

describe('Exception Tests', function (): void {
    it('can throw exceptions with custom messages', function (string $exceptionClass, string $message): void {
        expect(fn () => throw new $exceptionClass($message))
            ->toThrow($exceptionClass, $message);
    })->with([
        'FalAiException' => [FalAiException::class, 'Test error message for FalAiException'],
        'InvalidModelException' => [InvalidModelException::class, 'Test error message for InvalidModelException'],
        'RequestFailedException' => [RequestFailedException::class, 'Test error message for RequestFailedException'],
    ]);

    it('provides meaningful error context for each exception type', function (string $exceptionClass, string $context): void {
        $exception = new $exceptionClass($context);

        expect($exception->getMessage())->toBe($context)
            ->and($exception)->toBeInstanceOf($exceptionClass);
    })->with([
        'FalAiException with context' => [FalAiException::class, 'Test error message for FalAiException'],
        'InvalidModelException with context' => [InvalidModelException::class, 'Test error message for InvalidModelException'],
        'RequestFailedException with context' => [RequestFailedException::class, 'Test error message for RequestFailedException'],
    ]);

    it('maintains exception hierarchy properly', function (string $childClass, string $parentClass): void {
        $exception = new $childClass('Test message');

        expect($exception)
            ->toBeInstanceOf($childClass)
            ->toBeInstanceOf($parentClass);
    })->with([
        'InvalidModelException extends FalAiException' => [InvalidModelException::class, FalAiException::class],
        'RequestFailedException extends FalAiException' => [RequestFailedException::class, FalAiException::class],
        'FalAiException extends Exception' => [FalAiException::class, Exception::class],
    ]);

    it('can be caught by parent exception type', function (): void {
        $caught = false;

        try {
            throw new InvalidModelException('Test error');
        } catch (FalAiException $e) {
            $caught = true;

            expect($e->getMessage())->toBe('Test error');
        }

        expect($caught)->toBeTrue();
    });

    it('throws InvalidModelException when default model is empty', function (): void {
        config(['fal-ai.default_model' => '']); // Set empty default model

        $request = new GetResultRequest('test-request-id');

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('throws InvalidModelException when modelId parameter is empty', function (): void {
        config(['fal-ai.default_model' => 'valid-model']); // Set valid default

        $request = new GetResultRequest('test-request-id', ''); // Empty modelId overrides default

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('throws InvalidModelException when both modelId and default are empty', function (): void {
        config(['fal-ai.default_model' => '']); // Empty default

        $request = new GetResultRequest('test-request-id', ''); // Empty modelId

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('preserves error codes and messages correctly', function (string $message, int $code): void {
        $exception = new FalAiException($message, $code);

        expect($exception)
            ->getMessage()->toBe($message)
            ->getCode()->toBe($code)
            ->and($exception)->toBeInstanceOf(FalAiException::class);
    })->with([
        'user error' => ['User error', 400],
        'system error' => ['System error', 500],
        'not found error' => ['Simple error', 404],
    ]);

    it('preserves exception codes when provided', function (string $exceptionClass): void {
        $code = 1001;
        $message = 'Test exception with code';

        $exception = new $exceptionClass($message, $code);

        expect($exception->getCode())->toBe($code)
            ->and($exception->getMessage())->toBe($message);
    })->with([
        'FalAiException' => [FalAiException::class],
        'InvalidModelException' => [InvalidModelException::class],
        'RequestFailedException' => [RequestFailedException::class],
    ]);
});
