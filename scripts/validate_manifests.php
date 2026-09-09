<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ManifestToolkit.php';

try {
    $resolveProfile = null;
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        if ($arguments[$index] !== '--resolve') {
            throw new ValidationError(sprintf('Argumento no soportado: %s', $arguments[$index]));
        }

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError('Debe indicar un PROFILE_ID despues de --resolve');
        }

        $resolveProfile = $arguments[$index + 1];
        $index++;
    }

    $toolkit = new ManifestToolkit(dirname(__DIR__));
    $manifests = $toolkit->loadManifests();
    $toolkit->validateAll($manifests);

    if ($resolveProfile !== null) {
        printJson($toolkit->resolveProfile($manifests, $resolveProfile));
        exit(0);
    }

    printJson([
        'status' => 'ok',
        'manifests' => array_keys($manifests),
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
