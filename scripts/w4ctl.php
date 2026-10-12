<?php

declare(strict_types=1);

use W4\OS\Support\ValidationError;
use W4\OS\W4ctl\W4ctlApplication;

require_once __DIR__ . '/bootstrap.php';

try {
    $application = new W4ctlApplication(dirname(__DIR__));
    /** @var list<string> $arguments */
    $arguments = $argv ?? [];
    printJson($application->handle($arguments));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
