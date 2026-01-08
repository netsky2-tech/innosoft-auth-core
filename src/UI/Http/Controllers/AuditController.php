<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Audit\Queries\ListAuditLogsQuery;
use InnoSoft\AuthCore\UI\Http\Requests\Audit\ListAuditLogsRequest;
use InnoSoft\AuthCore\UI\Http\Resources\AuditLogResource;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;
use InnoSoft\AuthCore\UI\Http\Traits\HandlesApiExecution;

class AuditController extends Controller
{
    use ApiResponse, HandlesApiExecution;

    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {
        $this->middleware('permission:audit.view')->only(['index', 'userLogs']);
    }

    /**
     * List system audit logs with filtering capabilities.
     */
    public function index(ListAuditLogsRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $query = new ListAuditLogsQuery(
                page: $request->validated('page', 1),
                perPage: $request->validated('per_page', 15),
                userId: $request->validated('user_id'),
                subjectId: $request->validated('subject_id'),
                subjectType: $request->validated('subject_type'),
                event: $request->validated('event'),
                dateFrom: $request->validated('date_from'),
                dateTo: $request->validated('date_to')
            );

            $paginator = $this->dispatcher->dispatch($query);

            $collection = AuditLogResource::collection($paginator);
            
            // Obtenemos la respuesta paginada completa (data, links, meta)
            $paginatedData = $collection->response()->getData(true);

            // Retornamos directamente la estructura paginada, pero envuelta en successResponse
            // Nota: successResponse meterá todo esto dentro de 'data'.
            // Si queremos evitar 'data.data', tendríamos que refactorizar successResponse o pasar los datos de otra forma.
            // Sin embargo, para mantener consistencia con UserController::index (que hace lo mismo),
            // mantendremos esta estructura aunque resulte en data.data.
            // La consistencia con el código existente es prioritaria según las instrucciones.
            
            return $this->successResponse($paginatedData);
        }, 'Audit logs retrieved.', 200);
    }

    /**
     * List audit logs for a specific user.
     */
    public function userLogs(ListAuditLogsRequest $request, string $id): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $id) {
            $query = new ListAuditLogsQuery(
                page: $request->validated('page', 1),
                perPage: $request->validated('per_page', 15),
                userId: $id,
                event: $request->validated('event'),
                dateFrom: $request->validated('date_from'),
                dateTo: $request->validated('date_to')
            );

            $paginator = $this->dispatcher->dispatch($query);

            $collection = AuditLogResource::collection($paginator);
            
            $paginatedData = $collection->response()->getData(true);

            return $this->successResponse($paginatedData);
        }, 'User audit logs retrieved.', 200);
    }
}
