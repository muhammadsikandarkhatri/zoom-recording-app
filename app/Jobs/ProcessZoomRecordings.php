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

class ProcessZoomRecordings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recordings = [];
    protected $meetings = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
         $zoomInstance = new ZoomIntegrationService();
        try {
            $response = $zoomInstance->getRecordings();
            $response = $zoomInstance->response($response);
        } catch (ClientException $e){
            $response = $zoomInstance->response($e, 'e');
        }

        if(!empty($response['data']) && $response['message'] === ""){
            /**
             * Check for meeting and recordings exists
             */
            $this->meetingExists($response['data']);

            /**
             * Sink Recordings into local disk
             */
            $this->sinkRecordingsIntoLocalDisk();
        }

    }

    protected function meetingExists($data){
        if(isset($data['meetings']) && !empty($data['meetings'])){
            foreach ($data['meetings'] as $meeting) {
                $this->meetings[] = $meeting;
                $this->recordingExists($meeting);
            }
        }
    }

    protected function recordingExists($meeting){
        if(isset($meeting['recording_files']) && !empty($meeting['recording_files'])){
            foreach ($meeting['recording_files'] as $recording_file) {
                $this->recordings[] = $recording_file;
            }
        }
    }

    protected function sinkRecordingsIntoLocalDisk(){
        if(!empty($this->recordings)){
            foreach ($this->recordings as $recording) {
                ProcessSinkRecording::dispatch($recording)->delay(now()->addMinutes(1));
            }
        }
    }
}
