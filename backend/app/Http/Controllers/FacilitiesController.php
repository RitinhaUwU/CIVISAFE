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
            ->orderBy('id')
            ->cursorPaginate($request->input('per_page', 15))
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
            return response()->json(['message' => $e->getMessage(),], 500);
        }
    }

    public function deleteImage(Facility $facility)
    {
        try {
            if ($facility->image === null) {
                return response()->json(['message' => 'Esta instalação não tem imagem.'], 404);
            }

            Storage::disk('data_bucket')->delete($facility->image);

            $facility->image = null;
            $facility->saveOrFail();

            return new FacilityResource($facility);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage()], 500);
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
        if ($facility->image !== null) {
            Storage::disk('data_bucket')->delete($facility->image);
        }

        $facility->clearMediaCollection('documents');

        $facility->delete();

        return response()->json();
    }

    public function signedDocumentUrl(Request $request)
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', function ($attribute, $value, $fail) {
                if (explode(".", $value)
                        |> last(...)
                        |> (fn($x) => ! in_array($x, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpeg', 'jpg', 'png']))) {
                    $fail("The file must be pdf, doc, docx, xls, xlsx, jpeg, jpg or png format.");
                }
            }],
            'content_type' => ['required', 'string'],
        ]);

        $fileExtension = last(explode('.', $validated['filename']));
        $fileKey = '/facilities/documents/' . uuid_create() . '.' . $fileExtension;

        $upload = Storage::disk('data_bucket')->temporaryUploadUrl($fileKey, now()->addMinutes(10), ['ContentType' => $validated['content_type']]);

        return response()->json([
            'key' => $fileKey,
            'url' => $upload['url'],
            'headers' => $upload['headers'],
        ]);
    }

    public function confirmDocumentUpload(Request $request, Facility $facility)
    {
        try {
            $validated = $request->validate([
                'filename' => ['required', 'string'],
                'key' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (
                        explode(".", $value)
                            |> last(...)
                            |> (fn($x) => ! in_array($x, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpeg', 'jpg', 'png']))
                    ) {
                        $fail("The file must be pdf, doc, docx, xls, xlsx, jpeg, jpg or png format.");
                    }
                }],
            ]);

            $facility->addMediaFromDisk($validated['key'], 'data_bucket')
                ->usingName(pathinfo($validated['filename'], PATHINFO_FILENAME))
                ->usingFileName($validated['filename'])
                ->toMediaCollection('documents');

            return new FacilityResource($facility->fresh());
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage(),], 500);
        }
    }

    public function downloadDocument(Facility $facility, int $mediaId)
    {
        $media = $facility->getMedia('documents')->firstWhere('id', $mediaId);

        if (! $media) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return response()->json([
            'url' => $media->getTemporaryUrl(now()->addMinutes(10)),
        ]);
    }

    public function deleteDocument(Facility $facility, int $mediaId)
    {
        try {
            $media = $facility->getMedia('documents')->firstWhere('id', $mediaId);

            if (! $media) {
                return response()->json(['message' => 'Document not found.',], 404);
            }

            $media->delete();

            return new FacilityResource($facility->fresh());
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage(),], 500);
        }
    }
}
