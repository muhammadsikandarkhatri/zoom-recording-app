<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessZoomRecordings;

class ZoomController extends Controller
{
    public function process(){
        ProcessZoomRecordings::dispatchNow();
    }
}
