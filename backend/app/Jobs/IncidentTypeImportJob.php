<?php

namespace App\Jobs;

use App\Enums\NotificationStyles;
use App\Imports\IncidentTypeImport;
use App\Models\IncidentType;
use App\Models\User;
use App\Notifications\GenericNotification;
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

            $this->user->notify(new GenericNotification(
                "Tipos de Ocorrência Atualizados!",
                "A nova lista de tipos de ocorrência foi carregada com sucesso.",
                NotificationStyles::SUCCESS
            ));
        } catch (\Exception $exception) {
            if (!empty($import->getFailures())) {
                foreach ($import->getFailures() as $failure) {
                    Log::warning("Row {$failure->row()}: " . implode(', ', $failure->errors()));
                }

                $this->user->notify(new GenericNotification(
                    "Erro ao Atualizar Tipos de Ocorrência",
                    implode("\n", $import->getFailures()),
                    NotificationStyles::ERROR
                ));
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
