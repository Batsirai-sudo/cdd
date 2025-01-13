<?php

namespace CDD\Application\Console\Commands;

use CDD\Domain\Services\EntityService;
use Illuminate\Console\Command;

class GenerateEntityResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate entity resources from Yml files';

    /**
     * This function generates the Entity resource which are
     * 1. Controller
     * 2. Service
     * 3. Repository
     * 4. Model
     * 5. Transformer
     *
     * @param EntityService $entityService
     * @return void
     * @throws \Exception
     */
    public function handle( EntityService $entityService ): void {
        $entityService
            ->registerEntities()
            ->generateResources();
    }
}
