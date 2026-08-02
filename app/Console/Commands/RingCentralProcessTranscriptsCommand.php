<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CallTranscriptionProcessor;
use Illuminate\Console\Command;

class RingCentralProcessTranscriptsCommand extends Command
{
    protected $signature = 'ringcentral:process-transcripts';

    protected $description = 'Process one pending RingCentral call transcription (max one every scheduler tick)';

    public function handle(CallTranscriptionProcessor $processor): int
    {
        if (! filled(config('services.openai.api_key'))) {
            $this->warn('OPENAI_API_KEY is not configured; skipping.');

            return self::SUCCESS;
        }

        $result = $processor->processNext();
        if ($result === null) {
            $this->info('No pending call transcriptions.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Processed RingCentral call #%d → %s',
            $result['call_id'],
            $result['status'],
        ));

        return self::SUCCESS;
    }
}
