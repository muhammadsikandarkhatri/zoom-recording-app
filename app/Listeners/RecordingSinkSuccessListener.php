<?php

namespace App\Listeners;

use App\Events\RecordingSinkSuccess;
use App\Jobs\ProcessRecordingCloudSink;
use App\Jobs\ProcessSinkRecording;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RecordingSinkSuccessListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        ProcessRecordingCloudSink::dispatch($event->recording)
            ->delay(now()->addMinutes(1));
    }
}
