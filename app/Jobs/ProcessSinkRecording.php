<?php

namespace App\Jobs;

use App\Events\RecordingSinkSuccess;
use App\Services\ZoomIntegrationService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSinkRecording implements ShouldQueue
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
            $zoomInstance->downloadRecording($this->recording);
            $success = true;
        } catch (ClientException $e){
            $success = false;
        }

        if($success){
            event(new RecordingSinkSuccess($this->recording));
        }
    }
}
