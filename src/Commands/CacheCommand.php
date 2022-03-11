<?php

namespace Orbit\Commands;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Orbit\Concerns\Orbital;
use ReflectionClass;

class CacheCommand extends Command
{
    protected $name = 'orbit:cache';

    protected $descripition = 'Cache all Orbit models.';

    public function handle()
    {
        $models = $this->findOrbitModels();

        if ($models->count() === 0) {
            $this->warn('Could not find any Orbit models.');
            return 0;
        }

        $models->each(function (string $modelName): void {
            (new $modelName())->migrate();
        });

        $this->info('Cached the following Orbit models:');
        $this->newLine();
        $this->line($models->map(fn ($model) => "• <info>{$model}</info>"));

        return 0;
    }

    protected function findOrbitModels(): Collection
    {
        return collect(File::allFiles(app_path()))
            ->map(function ($item) {
                // Convert file path to namespace
                $path = $item->getRelativePathName();
                $class = sprintf(
                    '\%s%s',
                    app()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );

                return $class;
            })
            ->filter(function ($class) {
                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    // Only include non-abstract Model classes that use the Oribtal trait
                    return $reflection->isSubclassOf(Model::class) &&
                        !$reflection->isAbstract() &&
                        isset(class_uses_recursive($class)[Orbital::class]);
                }

                return false;
            });
    }
}
