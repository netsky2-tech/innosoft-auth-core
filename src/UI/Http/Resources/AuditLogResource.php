<?php

namespace InnoSoft\AuthCore\UI\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InnoSoft\AuthCore\Application\Audit\DTOs\AuditLogView;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AuditLogView $this->resource */
        return $this->resource->toArray();
    }
}
