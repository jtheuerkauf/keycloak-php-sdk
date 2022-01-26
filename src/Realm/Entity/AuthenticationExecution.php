<?php

declare(strict_types=1);

namespace Keycloak\Realm\Entity;

use JsonSerializable;
use Keycloak\JsonDeserializable;

class AuthenticationExecution implements JsonSerializable, JsonDeserializable
{
    /** @var string */
    public $id;
    /** @var string */
    public $alias;
    /** @var string */
    public $requirement;
    /** @var array|string[] */
    public $requirementChoices;
    /** @var bool */
    public $configurable;
    /** @var string */
    public $providerId;
    /** @var int */
    public $level;
    /** @var int */
    public $index;

    public function __construct(
        string $id,
        string $alias,
        string $requirement,
        array $requirementChoices,
        bool $configurable,
        string $providerId,
        int $level,
        int $index
    ) {
        $this->id = $id;
        $this->alias = $alias;
        $this->requirement = $requirement;
        $this->requirementChoices = $requirementChoices;
        $this->configurable = $configurable;
        $this->providerId = $providerId;
        $this->level = $level;
        $this->index = $index;
    }

    public static function fromJson($json): self
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['alias'] ?? '',
            $arr['requirement'],
            $arr['requirementChoices'] ?? [],
            $arr['configurable'],
            $arr['providerId'] ?? '',
            $arr['level'],
            $arr['index'],
        );
    }

    public function jsonSerialize(): self
    {
        return $this;
    }
}
