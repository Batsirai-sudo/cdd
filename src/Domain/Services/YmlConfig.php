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

        // we need to validate the config at this level
        $this->validateConfig();
    }

    private function resolveYml( string $file )
    {
        return Yaml::parseFile( $file );
    }

    private function validateConfig(): void
    {
        $requiredFields = [
            self::ENTITY_NAME,
            self::ENTITY_TABLE_NAME,
            self::ENTITY_NAMESPACE,
            self::ENTITY_FIELDS
        ];

        foreach ($requiredFields as $field) {
            if (!isset($this->config[$field])) {
                throw new \InvalidArgumentException(sprintf('Missing required field "%s" in entity configuration', $field));
            }
        }
        
        if (!is_array($this->config[self::ENTITY_FIELDS])) {
            throw new \InvalidArgumentException('Entity fields must be an array');
        }
        
        if (isset($this->config[self::ENTITY_RELATIONSHIPS]) && !is_array($this->config[self::ENTITY_RELATIONSHIPS])) {
            throw new \InvalidArgumentException('Entity relationships must be an array');
        }
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
