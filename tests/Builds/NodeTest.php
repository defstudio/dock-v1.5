<?php

/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Composer;
use App\Docker\Services\Nginx;
use App\Docker\Services\Node;
use App\Docker\Services\Php;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Scheduler;
use App\Recipes\Laravel\Services\Websocket;
use App\Recipes\Laravel\Services\Worker;
use Symfony\Component\Process\Process;
use function Termwind\render;

test('docker test build', function (string $version) {
    render("<div class='mx-1 my-1 text-black bg-green'>BUILD TEST NODE $version</div>");

    Env::fake(['RECIPE' => 'test-recipe', 'NODE_VERSION' => $version]);
    Service::fake();

    $node = new Node();
    $node->publishAssets();

    $root = invade($node->assets())->config['root'];

    $command = [
        'docker', 'build', '--tag', 'dock-test-image', "$root/build",
    ];

    $process = new Process(command: $command, env: [
        'COMPOSE_DOCKER_CLI_BUILD' => 1,
        'DOCKER_BUILDKIT' => 1,
    ]);
    $process->setTimeout(null);
    $process->setIdleTimeout(null);

    $output = '';
    $exitCode = $process->run(function ($type, $buffer) use (&$output) {
        $output .= "$buffer\n";
    });

    if ($exitCode > 0) {
        echo $output;
    }

    expect($exitCode)->toBe(0);

    $process = new Process(['docker', 'rmi', 'dock-test-image']);
    $process->run();
})->with(['lts', 'current', '18', '16', '14']);
