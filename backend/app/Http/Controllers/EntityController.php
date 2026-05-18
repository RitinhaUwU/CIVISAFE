<?php

namespace App\Http\Controllers;

use App\Http\Requests\Entity\EntityCreateRequest;
use App\Http\Requests\Entity\EntityUpdateRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EntityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ENTITIES_LIST')->only(['index', 'show']);
        $this->middleware('permission:ENTITIES_CREATE')->only(['store']);
        $this->middleware('permission:ENTITIES_UPDATE')->only(['update']);
        $this->middleware('permission:ENTITIES_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
        ]);

        $types = QueryBuilder::for(Entity::class)
            ->with(['entityType'])
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where('name', 'ILIKE', "%{$value}%");
                }),
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return EntityResource::collection($types);
    }

    public function signedUrl(Request $request)
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', function ($attribute, $value, $fail) {
                if (explode(".", $value)
                        |> last(...)
                        |> (fn($x) => !in_array($x, ['jpeg', 'jpg', 'png']))) {
                    $fail("The file must be jpeg, jpg or png format.");
                }
            }],
        ]);

        $fileExtension = last(explode('.', $validated['filename']));
        $fileKey = '/entities/' . uuid_create() . '.' . $fileExtension;

        return response()->json([
            'key' => $fileKey,
            'url' => Storage::disk('data_bucket')->temporaryUploadUrl($fileKey, now()->addMinutes(10))
        ]);
    }

    public function confirmUpload(Request $request, Entity $entity)
    {
        try {
            $validated = $request->validate([
                'key' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (explode(".", $value)
                            |> last(...)
                            |> (fn($x) => !in_array($x, ['jpeg', 'jpg', 'png']))) {
                        $fail("The file must be jpeg, jpg or png format.");
                    }
                }],
            ]);

            if($entity->logo !== null) {
                Storage::disk('data_bucket')->delete($entity->logo);
            }

            $entity->logo = $validated['key'];
            $entity->saveOrFail();
            return new EntityResource($entity);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(EntityCreateRequest $request)
    {
        return new EntityResource(Entity::create($request->validated()));
    }

    public function show(Entity $entity)
    {
        return new EntityResource($entity->load([
            'entityType',
        ]));
    }

    public function update(EntityUpdateRequest $request, Entity $entity)
    {
        $entity->update($request->validated());

        return new EntityResource($entity);
    }

    public function destroy(Entity $entity)
    {
        
        if($entity->logo !== null) {
            Storage::disk('data_bucket')->delete($entity->logo);
        }

        $entity->delete();

        return response()->json();
    }
}
