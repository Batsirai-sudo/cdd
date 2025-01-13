<?php

namespace Batsirai\CDD\Domain\Traits;

trait HasFiles {
    private function generateSingleFile( string $type, string $directory, callable $beforeFileCreation = null ): static
    {
        $content = $this->getProcessedStubFile( $type );
        $outputPath = $this->outputPath( $type, $directory );

        $this->createFile( $content, $outputPath, $beforeFileCreation );

        return $this;
    }

    private function cleanupGeneratedFiles(): void {
        foreach ($this->createdFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
                \Laravel\Prompts\info("Deleted file: $filePath");
            }
        }
    }

    private function outputPath( string $type, string $folder ): string {
        $updatedType = $type !== 'Model' ? $type : '';

        $fileName = $this->config->getEntityName() . $updatedType . '.php';
        $folderPath = '/src/Domain/' . $folder . '/' . $fileName;

        return $this->getBasePath(  $folderPath );
    }

    private function getBasePath( string $folderPath ): string
    {
        return base_path($this->basePath . $this->config->getNamespace() . '/' . $folderPath );
    }

    private function createFile( $content, $outputPath,  callable $beforeFileCreation = null ): void {
        if ( ! is_dir( dirname($outputPath) ) ) {
            if ( ! mkdir( dirname($outputPath), 0777, true) && ! is_dir( dirname($outputPath) ) ){
                throw new \RuntimeException("Failed to create directory: " . dirname($outputPath));
            }
        }
        $updatedContent = $content;

        if ( $beforeFileCreation !== null ) {
            $updatedContent = $beforeFileCreation( $content );
        }

        file_put_contents( $outputPath, $updatedContent );

        $this->createdFiles[] = $outputPath; // Track the created file

        \Laravel\Prompts\info( "Model file generated successfully at: $outputPath\n" );
    }

    private function generateMethod(string $methodName, string $relationshipType, string $relatedModel): string
    {
        return <<<EOT
    public function {$methodName}() {
            return \$this->{$relationshipType}({$relatedModel}::class);
        }
    EOT;
    }

    private function getProcessedStubFile( $type ): array | string {
        $fileName = $type !== 'Model' ? 'Model' . $type : 'Model';
        $stubPath = __DIR__ . '/../../../stubs/Domain/' . $fileName . '.php.stub' ;

        $stubContent = file_get_contents( $stubPath );

        if ( $type === 'Transformer' ) {
            $this->replaceTransformerVariables( $stubContent );
        }

        return $this->replaceStubVariables( $stubContent );
    }

    private function replaceTransformerVariables( string &$stubContent ): void {
        $names = array_column($this->config->getFields(), 'name');
        $quotedNames = array_map(fn($name) => "'$name'", $names);

        $commaSeparatedNames = implode(', ', $quotedNames);

        $stubContent = str_replace(
            self::TRANSFORMER_FIELDS,
            $commaSeparatedNames,
            $stubContent
        );
    }

    private function replaceStubVariables( $stubContent ): array | string{
        return str_replace(
            [ self::NAMESPACE_KEY, self::MODEL_KEY ],
            [ $this->config->getNamespace(), $this->config->getEntityName() ],
            $stubContent
        );
    }
}
