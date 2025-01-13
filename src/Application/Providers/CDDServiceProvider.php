<?php

namespace CDD\Application\Providers;

use App\Abstracts\Providers\AbstractServiceProvider;
use CDD\Application\Console\Commands\GenerateEntityResources;

class CDDServiceProvider extends AbstractServiceProvider {
    protected array $commands = [
        GenerateEntityResources::class
    ];
}
