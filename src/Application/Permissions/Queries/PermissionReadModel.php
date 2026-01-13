<?php

namespace InnoSoft\AuthCore\Application\Permissions\Queries;

readonly class PermissionReadModel implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $guardName,
        public string $createdAt
    ){}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard' => $this->guardName,
            'created_at' => $this->createdAt,
        ];
    }
}
