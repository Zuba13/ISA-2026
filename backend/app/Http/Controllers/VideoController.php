<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(): JsonResponse
    {
        $videos = Video::with('user:id,username,name,surname')->latest()->get();
        return response()->json($videos);
    }
}
