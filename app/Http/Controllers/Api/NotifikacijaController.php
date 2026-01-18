<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikacija;
use Illuminate\Http\Request;

class NotifikacijaController extends Controller
{
    public function getAll(Request $request)
    {
        $user = $request->user();

        $notifikacije = Notifikacija::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($notifikacije);
    }

    public function getNeprocitane(Request $request)
    {
        $user = $request->user();

        $count = Notifikacija::where('user_id', $user->id)
            ->neprocitane()
            ->count();

        return response()->json(['count' => $count]);
    }

    public function index(Request $request)
    {
        return $this->getAll($request);
    }

    public function neprocitane(Request $request)
    {
        return $this->getNeprocitane($request);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        $notifikacija = Notifikacija::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notifikacija->markAsRead();

        return response()->json(['message' => 'Notifikacija označena kao pročitana']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notifikacija::where('user_id', $user->id)
            ->neprocitane()
            ->update([
                'procitano' => true,
                'procitano_at' => now(),
            ]);

        return response()->json(['message' => 'Sve notifikacije označene kao pročitane']);
    }

    /**
     * Mark notifications by type as read
     */
    public function markByTypeAsRead(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'types' => 'required|array',
            'types.*' => 'string',
        ]);

        $count = Notifikacija::where('user_id', $user->id)
            ->whereIn('tip', $validated['types'])
            ->neprocitane()
            ->update([
                'procitano' => true,
                'procitano_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notifikacije označene kao pročitane',
            'count' => $count
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $notifikacija = Notifikacija::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notifikacija->delete();

        return response()->json(['message' => 'Notifikacija obrisana']);
    }

    public function delete(Request $request, $id)
    {
        return $this->destroy($request, $id);
    }
}
