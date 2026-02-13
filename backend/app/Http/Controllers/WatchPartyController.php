<?php

namespace App\Http\Controllers;

use App\Events\VideoStarted;
use App\Models\Room;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WatchPartyController extends Controller
{
    public function createRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $room = Room::create([
            'name' => $request->name,
            'creator_id' => Auth::id(),
            'token' => Str::random(10),
        ]);

        Participant::create([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json($room, 201);
    }

    public function joinRoom(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $room = Room::where('token', $request->token)->firstOrFail();

        Participant::firstOrCreate([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json($room);
    }

    public function startVideo(Request $request, $roomId)
    {
        $request->validate([
            'video_id' => 'required|integer|exists:videos,id',
        ]);

        $room = Room::findOrFail($roomId);

        if ($room->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Only the creator can start a video'], 403);
        }

        $room->update(['current_video_id' => $request->video_id]);

        broadcast(new VideoStarted($room->id, $request->video_id))->toOthers();

        return response()->json(['message' => 'Video started']);
    }

    public function getActiveRooms()
    {
        return Room::with('creator:id,username')->latest()->get();
    }
}
