<?php

namespace InnoSoft\AuthCore\Application\Audit\DTOs;

use Spatie\Activitylog\Models\Activity;

final readonly class AuditLogView
{
    public function __construct(
        public int $id,
        public string $logName,
        public string $description,
        public ?string $subjectType,
        public ?string $subjectId,
        public ?string $causerType,
        public ?string $causerId,
        public ?string $causerName,
        public array $properties,
        public string $createdAt
    ) {}

    public static function fromModel(Activity $activity): self
    {
        return new self(
            id: $activity->id,
            logName: $activity->log_name ?? 'default',
            description: $activity->description,
            subjectType: $activity->subject_type,
            subjectId: (string) $activity->subject_id,
            causerType: $activity->causer_type,
            causerId: (string) $activity->causer_id,
            causerName: $activity->causer?->name,
            properties: $activity->properties->toArray(),
            createdAt: $activity->created_at->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->logName,
            'description' => $this->description,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'causer_type' => $this->causerType,
            'causer_id' => $this->causerId,
            'causer_name' => $this->causerName,
            'properties' => $this->properties,
            'created_at' => $this->createdAt,
        ];
    }
}
