<?php

namespace App\Http\Controllers;

use App\Events\VideoStarted;
use App\Events\VideoSynced;
use App\Models\Room;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WatchPartyController extends Controller
{
    public function syncVideo(Request $request, $roomId)
    {
        $request->validate([
            'video_id' => 'required|integer',
            'current_time' => 'required|numeric',
            'is_playing' => 'required|boolean',
        ]);

        $room = Room::findOrFail($roomId);

        if ($room->creator_id !== Auth::id()) {
             // In some cases we might want to allow others, but usually creator is master
            return response()->json(['message' => 'Only the creator can sync the video'], 403);
        }

        broadcast(new VideoSynced(
            $room->id,
            $request->video_id,
            $request->current_time,
            $request->is_playing,
            Auth::id()
        ))->toOthers();

        return response()->json(['message' => 'Video synced']);
    }

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
        return Room::with('creator:id,username')
            ->withCount('participants')
            ->latest()
            ->get();
    }

    public function show($id)
    {
        $room = Room::with(['creator:id,username', 'participants.user:id,username', 'currentVideo'])
            ->withCount('participants')
            ->findOrFail($id);

        return response()->json($room);
    }

    public function leaveRoom($id)
    {
        $room = Room::findOrFail($id);

        Participant::where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['message' => 'Left the room']);
    }
}
