<?php

namespace CDD\Domain\Services;

use CDD\Domain\Traits\HasFiles;
use CDD\Domain\Traits\HasMigrationTables;
use Exception;
use Illuminate\Support\Facades\Artisan;

class FileService
{
    use HasFiles, HasMigrationTables;

    const NAMESPACE_KEY = '{{NAMESPACE}}';
    const MODEL_KEY = '{{MODEL}}';
    const TRANSFORMER_FIELDS = '{{TRANSFORMER_FIELDS}}';

    private string $basePath = 'src/Modules/';
    private YmlConfig $config;

    private array $createdFiles = []; // Track created files

    /**
     * This the main fxn and the methods it calls have to be in this file too any extra
     * have to be in trait
     *
     * @param YmlConfig $config
     * @return void
     */
    public function generateFiles( YmlConfig $config ): void {
        $this->setConfig( $config );

        try {

            $this->generateController()
                ->generateService()
                ->generateRepository()
                ->generateModel()
                ->generateTransformer()
                ->addApiResource()
                ->generateMigration();

            \Laravel\Prompts\info("All files generated successfully.");
        } catch (Exception $e) {
            // Cleanup: Delete all created files if an error occurs
            $this->cleanupGeneratedFiles();

            throw new \RuntimeException("An error occurred during file generation: " . $e->getMessage(), 0, $e);
        }
    }

    private function setConfig( YmlConfig $config ): self {
        $this->config = $config;

        return $this;
    }

    private function generateController(): static {
        return $this->generateSingleFile(
            'Controller',
            'Controllers'
        );
    }

    private function generateService(): static {
        return $this->generateSingleFile(
            'Service',
            'Services'
        );
    }

    private function generateRepository(): static {
        return $this->generateSingleFile(
            'Repository',
            'Repositories'
        );
    }

    private function generateModel(): static {
        return $this->generateSingleFile(
            'Model',
            'Models',
            function( $data ) {
                $relationshipMethods = $this->generateRelationshipMethods( $data );

                return str_replace( '{{RELATIONSHIPS}}', $relationshipMethods, $data );
            }
        );
    }

    private function generateRelationshipMethods( string $data): string {
        $methods = array_map( function ( $relationship ) {
            $type = $relationship['type'];
            $relatedEntity = $relationship['related_entity'];
            $methodName = lcfirst($relatedEntity); // Use camel case for method names

            return $this->generateMethod( $methodName, $type, $relatedEntity );

        }, $this->config->getRelationships() );

        return implode( "\n\n", $methods );
    }

    private function generateTransformer(): self {
        return $this->generateSingleFile(
            'Transformer',
            'Transformers'
        );
    }

    /**
     * @throws Exception
     */
    private function addApiResource(): self {
        $namespace = $this->config->getNamespace();
        $entityName = $this->config->getEntityName();

        $controllerClass = "{$namespace}\\Domain\\Controllers\\{$entityName}Controller";

        $newRouteLine = "Route::apiResource('" . strtolower($entityName) . "s', {$controllerClass}::class);";

        $filePath = $this->getBasePath( 'routes/api.php' );
        $fileContent = file_get_contents( $filePath );

        // Check if the route already exists to avoid duplication
        if ( str_contains( $fileContent, $newRouteLine ) ) {
            throw new Exception( "The route for {$entityName} already exists in the route file." );
        }

        $updatedContent = $fileContent . "\n" . $newRouteLine . "\n";

        file_put_contents( $filePath, $updatedContent );

        \Laravel\Prompts\info("Route for {$entityName} added successfully.\n");

        return $this;
    }

    private function generateMigration(): self {
        $path = $this->createMigration();

        $this->addFieldsToMigration( $path );

        // Step 3: Run the migration using Artisan
        Artisan::call('migrate');

        \Laravel\Prompts\info( Artisan::output() );

        return $this;
    }
}
