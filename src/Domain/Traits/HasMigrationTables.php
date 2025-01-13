<?php

namespace CDD\Domain\Traits;

use Illuminate\Support\Facades\Artisan;

trait HasMigrationTables
{
    private function createMigration(): string {
        Artisan::call('make:migration', [
            'name' => 'create_' . $this->config->getTableName() . '_table',
        ]);

        $output = Artisan::output();

        // Extract the migration file name using a regular expression
        if (preg_match('/database\/migrations\/(\d{4}_\d{2}_\d{2}_\d{6}_create_\w+_table\.php)/', $output, $matches)) {
            $migrationFilePath = $matches[0];
            $migrationFileName = $matches[1];

            return $this->moveMigrationToNamespaceFolder( $migrationFilePath, $migrationFileName );
        } else {
            throw new \RuntimeException("Failed to extract the migration file name from output.");
        }
    }

    private function moveMigrationToNamespaceFolder( string $migrationFilePath, string $migrationFileName ): string {
        $sourcePath = base_path( $migrationFilePath );
        $destinationPath = $this->getBasePath( $migrationFilePath );

        if ( rename( $sourcePath, $destinationPath ) ) {
            \Laravel\Prompts\info( "Migration file moved to: $destinationPath" );
        } else {
            throw new \RuntimeException("Failed to move migration file to: $destinationPath");
        }

        return $destinationPath;
    }

    private function addFieldsToMigration( string $path ): void {
        $fields = $this->config->getFields();
        $relationships = $this->config->getRelationships();
        $migrationContent = file_get_contents( $path );

        $columns = array_map(function ($field) {
            return $this->getColumnDefinition($field['name'], $field['type']);
        }, $fields);


        // Generate the relationship definitions
        $relationshipColumns = array_map(function ($relationship) {
            return $this->getRelationshipDefinition( $relationship );
        }, $relationships);

        // Join the columns with new lines and proper indentation
        $columnsString = implode("\n            ", array_merge($columns, $relationshipColumns));

        // Prepare the complete Schema::create block with $table->uuid('id')->primary() and columns
        $schemaBlock = "Schema::create('definitions', function (Blueprint \$table) {\n"
            . "            \$table->uuid('id')->primary();\n"
            . "            $columnsString\n"
            . "            \$table->timestamps();";

        // Use regex to replace the existing Schema::create block
        $updatedContent = preg_replace(
            '/Schema::create\(.*?\{.*?\$table->id\(\);.*?\$table->timestamps\(\);/s',
            $schemaBlock,
            $migrationContent
        );

        file_put_contents( $path, $updatedContent);

        \Laravel\Prompts\info( "Fields added to migration at: $path" );
    }

    private function getRelationshipDefinition( $relationship ): string {
        $foreignKey = $relationship['foreign_key'] ?? strtolower( $relationship['related_entity'] ) . '_id';

        return "\$table->foreignUuid('$foreignKey');";
    }

    private function getColumnDefinition(string $name, string $type): string
    {
        $typeMap = [
            'string' => 'string',
            'integer' => 'integer',
            'datetime' => 'timestamp',
        ];

        $method = $typeMap[$type] ?? 'string';

        return "\$table->{$method}('$name');";
    }
}
