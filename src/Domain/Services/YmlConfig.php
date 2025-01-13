<?php

namespace Batsirai\CDD\Domain\Services;

use Symfony\Component\Yaml\Yaml;

class YmlConfig
{
    private const ENTITY_NAME = 'entity_name';
    private const ENTITY_TABLE_NAME = 'table_name';
    private const ENTITY_NAMESPACE = 'namespace';
    private const ENTITY_FIELDS = 'fields';
    private const ENTITY_RELATIONSHIPS = 'relationships';

    private array $config;

    public function __construct( string $file ) {
        $this->config = $this->resolveYml( $file );
    }

    private function resolveYml( string $file )
    {
        return Yaml::parseFile( $file );
    }

    public function getEntityName(): string {
        return $this->config[ self::ENTITY_NAME ];
    }

    public function getModelQualifiedClassNamespace(): string {
        $namespace = $this->getNamespace();
        $entityName = $this->getEntityName();

        return $namespace . '\\Domain\\Models\\' . $entityName;
    }

    public function getTableName(): string {
        return $this->config[ self::ENTITY_TABLE_NAME ];
    }

    public function getNamespace(): string {
        return $this->config[ self::ENTITY_NAMESPACE ];
    }

    public function getFields(): array {
        return $this->config[ self::ENTITY_FIELDS ];
    }

    public function getRelationships(): array {
        return $this->config[ self::ENTITY_RELATIONSHIPS ];
    }
}
