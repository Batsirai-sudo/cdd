<?php

namespace Batsirai\CDD\Domain\Services;

use Exception;

class EntityService
{
    public array $entities = [];

    private string $entitiesFolderPath;

    public function __construct( private readonly FileService $fileService ){
        $this->setEntitiesFolderPath();
    }

    /**
     * @throws Exception
     */
    public function registerEntities(): self {
        $this->checkEntityFolderExists();

        $ymlFiles = $this->getAllYmlFiles();

        $this->checkFolderIsNotEmpty( $ymlFiles );

        foreach ( $ymlFiles as $file ) {
            $this->entities[] = app()->make( YmlConfig::class, [ 'file' => $file ] );
        }

        return $this;
    }

    private function setEntitiesFolderPath(): void {
        $this->entitiesFolderPath = base_path( config('cdd.entities_path') );
    }

    public function generateResources(): void {
        forEach ($this->entities as $entity ) {
            if ( $this->checkIfEntityExists( $entity ) ) {

                \Laravel\Prompts\info( $entity->getEntityName() . ' entity already exists' );

                continue;
            };

            $this->fileService->generateFiles( $entity );
        }
    }

    private function checkIfEntityExists( YmlConfig $config ): bool {
        $className = $config->getModelQualifiedClassNamespace();

        return class_exists( $className );
    }

    /**
     * @throws Exception
     */
    private function checkEntityFolderExists(): void {
        if ( ! is_dir( $this->entitiesFolderPath ) ) {
            throw new Exception("Entities folder not found: {$this->entitiesFolderPath}");
        }
    }

    /**
     * @throws Exception
     */
    private function checkFolderIsNotEmpty( $files ): void {
        if ( empty( $files ) ) {
            throw new \Exception("No YML files found in folder: {$this->entitiesFolderPath}");
        }
    }

    private function getAllYmlFiles(): false | array {
        return glob( $this->entitiesFolderPath . '/*.yml' );
    }
}
