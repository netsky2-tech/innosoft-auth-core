<?php

namespace InnoSoft\AuthCore\Application\Roles\Queries;

readonly class RoleReadModel implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $guardName,
        public array $permissions = [],
        public string $createdAt
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard' => $this->guardName,
            'permissions' => $this->permissions,
            'created_at' => $this->createdAt,
        ];
    }
}