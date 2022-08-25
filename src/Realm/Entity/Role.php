<?php

namespace Keycloak\Realm\Entity;

use JsonSerializable;
use Keycloak\JsonDeserializable;

/**
 * Class Role
 * @package Keycloak\Realm\Entity
 */
class Role implements JsonSerializable, JsonDeserializable
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var ?string
     */
    public $description;

    /**
     * @var bool
     */
    public $composite;

    /**
     * @var bool
     */
    public $clientRole;

    /**
     * @var string
     */
    public $containerId;

    public function __construct(
        string $id,
        string $name,
        ?string $description,
        bool $composite,
        bool $clientRole,
        string $containerId
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->composite = $composite;
        $this->clientRole = $clientRole;
        $this->containerId = $containerId;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'composite' => $this->composite,
            'clientRole' => $this->clientRole,
            'containerId' => $this->containerId
        ];
    }

    /**
     * @param string|array $json
     * @return mixed Should always return an instance of the class that implements this interface.
     */
    public static function fromJson($json): Role
    {
        $arr = is_array($json)
            ? $json
            : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['name'],
            $arr['description'] ?? null,
            $arr['composite'] ?? false,
            $arr['clientRole'],
            $arr['containerId']
        );
    }
}
