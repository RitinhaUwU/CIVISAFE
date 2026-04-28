<?php

namespace App\Jobs;

use App\Imports\IncidentTypeImport;
use App\Models\IncidentType;
use App\Models\User;
use App\Notifications\IncidentTypeImportSuccessNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Throwable;

#[Tries(1)]
class IncidentTypeImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $fileName, public User $user)
    {
    }

    public function handle(): void
    {
        $import = new IncidentTypeImport();
        try {
            DB::transaction(function () use ($import) {
                IncidentType::query()->delete();
                Excel::import($import, $this->fileName, 'misc_bucket');

                if (!empty($import->getFailures())) {
                    throw new \RuntimeException("Validation errors");
                }
            });

            $this->user->notify(new IncidentTypeImportSuccessNotification());
        } catch (\Exception $exception) {
            if (!empty($import->getFailures())) {
                foreach ($import->getFailures() as $failure) {
                    Log::warning("Row {$failure->row()}: " . implode(', ', $failure->errors()));
                }
            } else {
                Log::error("Error importing incident types", ['message' => $exception->getMessage(), 'exception' => get_class($exception)]);
            }
        } finally {
            Storage::disk('misc_bucket')->delete($this->fileName);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Error importing incident types", ['message' => $exception->getMessage(), 'exception' => $exception]);
    }
}
