<?php

namespace App\Http\Controllers;

use App\Http\Requests\FacilitiesRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacilitiesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:FACILITIES_LIST')->only(['index', 'show']);
        $this->middleware('permission:FACILITIES_CREATE')->only(['store']);
        $this->middleware('permission:FACILITIES_UPDATE')->only(['update']);
        $this->middleware('permission:FACILITIES_DELETE')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $facility = QueryBuilder::for(Facility::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('name', 'ILIKE', "%{$value}%");
                    });
                }),
            )
            ->orderBy('id', 'asc')
            ->paginate($request->input('per_page', 15))
            ->appends($request->query());

        return FacilityResource::collection($facility);    }

    public function signedUrl(Request $request){
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
        $fileKey = '/facilities/images/' . uuid_create() . '.' . $fileExtension;

        return response()->json([
            'key' => $fileKey,
            'url' => Storage::disk('data_bucket')->temporaryUploadUrl($fileKey, now()->addMinutes(10))
        ]);
    }

    public function confirmUpload(Request $request, Facility $facility)
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

            if($facility->image !== null) {
                Storage::disk('data_bucket')->delete($facility->image);
            }

            $facility->image = $validated['key'];
            $facility->saveOrFail();
            return new FacilityResource($facility);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(FacilitiesRequest $request)
    {
        return new FacilityResource(Facility::create($request->validated()));
    }

    public function show(Facility $facility)
    {
        return new FacilityResource($facility);
    }

    public function update(FacilitiesRequest $request, Facility $facility)
    {
        $facility->update($request->validated());

        return new FacilityResource($facility);
    }

    public function destroy(Facility $facility)
    {
        if($facility->image !== null) {
            Storage::disk('data_bucket')->delete($facility->logo);
        }

        $facility->delete();

        return response()->json();
    }

    /**
     * Upload de ficheiros
     * POST /facilities/{facility}/documents
     */
    public function uploadDocuments(Request $request, Facility $facility)
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf,docx,xlsx', 'max:20480'],
        ]);

        try {
            foreach ($request->file('files') as $file) {
                $facility->addMedia($file)->toMediaCollection('documents');
            }

            return new FacilityResource($facility->fresh());
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Download de um ficheiro
     * GET /facilities/{facility}/documents/{mediaId}/download
     */
    public function downloadDocument(Facility $facility, int $mediaId)
    {
        $media = $facility->getMedia('documents')->firstWhere('id', $mediaId);

        if (! $media) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return $media->toResponse(request());
    }

    /**
     * Eliminar um ficheiro
     * DELETE /facilities/{facility}/documents/{mediaId}
     */
    public function deleteDocument(Facility $facility, int $mediaId)
    {
        $media = $facility->getMedia('documents')->firstWhere('id', $mediaId);

        if (! $media) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $media->delete();

        return response()->json();
    }
}
