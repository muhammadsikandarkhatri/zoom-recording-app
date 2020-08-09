<?php

namespace App\Jobs;

use App\Services\ZoomIntegrationService;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRecordingCloudSink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recording;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($recording)
    {
        $this->recording = $recording;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $success = false;
        $zoomInstance = new ZoomIntegrationService();

        try {
            $zoomInstance->uploadRecordingToCloud($this->recording);
            $success = true;
        } catch (S3Exception $e) {
            $success = false;
        }

        if($success){
            info('Cloud Recording uploaded Successfully');
        }
    }
}
