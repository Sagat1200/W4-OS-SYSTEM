<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiManager
{
    private SystemApiToolkit $toolkit;

    public function __construct(string $rootDir)
    {
        $this->toolkit = new SystemApiToolkit($rootDir);
    }

    public function call(string $requestJson): string
    {
        try {
            $request = $this->decodeJsonObject($requestJson);
            return $this->encodeResponse($this->toolkit->respond($request));
        } catch (ValidationError $exception) {
            return $this->encodeResponse([
                'api_version' => '1.0',
                'request_id' => 'invalid-request',
                'result' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $exception->getMessage(),
                    'retryable' => false,
                ],
            ]);
        }
    }

    public function getCapabilities(?string $requestId = null): string
    {
        return $this->encodeResponse($this->toolkit->createCapabilitiesResponse($requestId));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('Request JSON invalido: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('El request JSON debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function encodeResponse(array $response): string
    {
        $encoded = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar la respuesta System API');
        }

        return $encoded . PHP_EOL;
    }
}
