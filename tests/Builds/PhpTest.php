<?php
/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

use App\Docker\Service;
use App\Docker\Services\Composer;
use App\Docker\Services\Php;
use App\Facades\Env;
use App\Recipes\Laravel\Services\Scheduler;
use App\Recipes\Laravel\Services\Websocket;
use App\Recipes\Laravel\Services\Worker;
use Symfony\Component\Process\Process;
use function Termwind\render;

test('docker test build', function (string $targetClass, string $phpVersion) {
    render("<div class='mx-1 my-1 text-black bg-green'>BUILD TEST target = $targetClass & php version = $phpVersion</div>");

    Env::fake(['RECIPE' => 'test-recipe', 'REDIS_ENABLED' => true, 'EXTRA_TOOLS' => 'mysql_client,libreoffice_writer,pcov,xdebug']);
    Env::put('PHP_VERSION', $phpVersion);
    Service::fake();

    /** @var Php $target */
    $target = new $targetClass();
    $target->publishAssets();

    $root = invade($target->assets())->config['root'];

    $command = [
        'docker', 'build', '--target', $target->getTarget(), '--tag', 'dock-test-php', $root,
    ];

    $process = new Process(command: $command, env: [
        'COMPOSE_DOCKER_CLI_BUILD' => 1,
        'DOCKER_BUILDKIT' => 1,
    ]);
    $process->setTimeout(null);
    $process->setIdleTimeout(null);

    $output = "";
    $exitCode = $process->run(function ($type, $buffer) use (&$output) {
        $output .= "$buffer\n";
    });

    if($exitCode>0){
        echo $output;
    }

    expect($exitCode)->toBe(0);

    $process = new Process(['docker', 'rmi', 'dock-test-php']);
    $process->run();
})
    ->with([Php::class, Composer::class, Scheduler::class, Worker::class, Websocket::class])
    ->with('php versions');
