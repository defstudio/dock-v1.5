<?php

use App\Docker\Service;
use App\Docker\ServiceDefinition;
use App\Facades\Env;
use App\Facades\Terminal;
use App\Services\RecipeService;
use Illuminate\Support\Collection;
use Tests\Fixtures\Recipes\TestRecipe\TestRecipe;

it('can build a service', function () {
    Env::fake(['RECIPE' => 'test-recipe']);
    Storage::fake('cwd');
    Terminal::fake();

    $cookbook = app(RecipeService::class);

    invade($cookbook)->active = new class extends TestRecipe
    {
        public function services(): Collection
        {
            return Collection::make([
                'Testing\Fake\Service' => new class extends Service
                {
                    protected function configure(): void
                    {
                        $this->setServiceName('foo-service');
                        $this->serviceDefinition = new ServiceDefinition([]);
                    }
                },
            ]);
        }
    };
    app()->bind(RecipeService::class, fn () => $cookbook);

    $this->artisan('build foo-service')->assertSuccessful();

    Terminal::assertRan(['docker-compose', 'pull', 'foo-service']);
    Terminal::assertRan(['docker-compose', 'up', '-d', '--no-deps', '--build', 'foo-service']);
});
