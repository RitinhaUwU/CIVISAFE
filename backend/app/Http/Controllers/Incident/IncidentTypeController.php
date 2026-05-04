<?php

namespace App\Http\Controllers\Incident;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\IncidentTypeImportRequest;
use App\Http\Resources\Incident\IncidentTypeResource;
use App\Jobs\IncidentTypeImportJob;
use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENT_TYPES_LIST')->only(['index']);
        $this->middleware('permission:INCIDENT_TYPES_UPLOAD')->only(['store']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
            'search' => 'nullable|string'
        ]);

        $types = QueryBuilder::for(IncidentType::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('code', 'ILIKE', "%{$value}%")
                            ->orWhere('species', 'ILIKE', "%{$value}%")
                            ->orWhere('type', 'ILIKE', "%{$value}%");
                    });
                }),
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return IncidentTypeResource::collection($types);
    }

    public function show(IncidentType $incidentType)
    {
        return new IncidentTypeResource($incidentType);
    }

    public function store(IncidentTypeImportRequest $request)
    {
        try {
            $file = $request->file('file')->store("/", [
                'disk' => 'misc_bucket',
                'visibility' => 'private'
            ]);

            IncidentTypeImportJob::dispatch($file, $request->user())->onQueue('imports');

            return response()->json(['message' => 'File sent to processing', 'file' => $file], 201);
        } catch (\Exception $e) {
            Log::error("Could not upload file to disk.", [
                'message' => $e->getMessage(),
                'exceptionData' => $e,
            ]);
            return response()->json([
                'message' => 'Ocorreu um erro interno ao carregar o ficheiro.'
            ], 500);
        }
    }
}
