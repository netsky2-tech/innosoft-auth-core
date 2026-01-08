<?php

declare(strict_types=1);

namespace InnoSoft\AuthCore\Domain\Teams\Repositories;

interface TeamRepositoryInterface
{
    /**
     * Verifica si un usuario pertenece a un equipo específico.
     * Esto desacopla al paquete de la estructura de BD del Host.
     */
    public function userBelongsToTeam(string $userId, string $teamId): bool;
}