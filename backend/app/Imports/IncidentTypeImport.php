<?php

namespace App\Imports;

use App\Models\IncidentType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class IncidentTypeImport implements ToModel, WithValidation, SkipsEmptyRows, WithHeadingRow, WithChunkReading, SkipsOnFailure
{
    private array $failures = [];

    public function rules(): array
    {
        return [
            'code' => ['required', 'numeric', Rule::unique('incident_types', 'code')->withoutTrashed()],
            'species' => ['required', 'string'],
            'type' => ['required', 'string'],
            'description' => ['required', 'string'],
        ];
    }

    public function model(array $row): IncidentType
    {
        return new IncidentType([
            'code' => $row['code'],
            'species' => $row['species'],
            'type' => $row['type'],
            'description' => $row['description'],
        ]);
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function onFailure(Failure ...$failures): void
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    public function getFailures(): array
    {
        return $this->failures;
    }
}
