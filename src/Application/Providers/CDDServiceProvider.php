<?php

namespace Batsirai\CDD\Application\Providers;

use App\Abstracts\Providers\AbstractServiceProvider;
use Batsirai\CDD\Application\Console\Commands\GenerateEntityResources;

class CDDServiceProvider extends AbstractServiceProvider {
    protected array $commands = [
        GenerateEntityResources::class
    ];
}
