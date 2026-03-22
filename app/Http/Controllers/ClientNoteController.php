<?php
//
//namespace App\Http\Controllers;
//
//use App\Models\Client;
//use App\Models\ClientNote;
//use Illuminate\Http\JsonResponse;
//use Illuminate\Http\Request;
//
//class ClientNoteController extends Controller
//{
//    public function index(Request $request, Client $client): JsonResponse
//    {
//        $this->authorizeTenant($request, $client);
//
//        $notes = ClientNote::where('tenant_id', $request->user()->tenant_id)
//            ->where('client_id', $client->id)
//            ->with('creator:id,name')
//            ->orderByDesc('created_at')
//            ->get();
//
//        return response()->json([
//            'data' => $notes->map(fn ($n) => [
//                'id'         => $n->id,
//                'content'    => $n->content,
//                'created_by' => $n->creator ? ['id' => $n->creator->id, 'name' => $n->creator->name] : null,
//                'created_at' => $n->created_at?->toISOString(),
//            ]),
//        ]);
//    }
//
//    public function store(Request $request, Client $client): JsonResponse
//    {
//        $this->authorizeTenant($request, $client);
//
//        if (! $request->user()->can('create_records')) {
//            abort(403);
//        }
//
//        $request->validate([
//            'content' => ['required', 'string'],
//        ]);
//
//        $note = ClientNote::create([
//            'tenant_id'  => $request->user()->tenant_id,
//            'client_id'  => $client->id,
//            'created_by' => $request->user()->id,
//            'content'    => $request->input('content'),
//        ]);
//
//        return response()->json([
//            'message' => 'Note created.',
//            'data'    => [
//                'id'         => $note->id,
//                'content'    => $note->content,
//                'created_by' => ['id' => $request->user()->id, 'name' => $request->user()->name],
//                'created_at' => $note->created_at?->toISOString(),
//            ],
//        ], 201);
//    }
//
//    public function destroy(Request $request, Client $client, ClientNote $note): JsonResponse
//    {
//        $this->authorizeTenant($request, $client);
//
//        if ($note->client_id !== $client->id || $note->tenant_id !== $request->user()->tenant_id) {
//            abort(403);
//        }
//
//        $note->delete();
//
//        return response()->json(['message' => 'Note deleted.']);
//    }
//
//    private function authorizeTenant(Request $request, Client $client): void
//    {
//        if ($client->tenant_id !== $request->user()->tenant_id) {
//            abort(403, 'Access denied.');
//        }
//    }
//}



namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientNoteController extends Controller
{
    public function index(Request $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        $notes = ClientNote::where('tenant_id', $request->user()->tenant_id)
            ->where('client_id', $client->id)
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $notes->map(fn ($n) => [
                'id'         => $n->id,
                'content'    => $n->content,
                'created_by' => $n->creator
                    ? ['id' => $n->creator->id, 'name' => $n->creator->name]
                    : null,
                'created_at' => $n->created_at?->toISOString(),
                'updated_at' => $n->updated_at?->toISOString(),
            ]),
        ]);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if (! $request->user()->can('create_records')) {
            abort(403);
        }

        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $note = ClientNote::create([
            'tenant_id'  => $request->user()->tenant_id,
            'client_id'  => $client->id,
            'created_by' => $request->user()->id,
            'content'    => $request->input('content'),
        ]);

        \App\Models\ActivityLog::log(
            $request->user()->tenant_id,
            $request->user()->id,
            'note_added',
            "Added a note to {$client->full_name}'s profile",
            "/clients/{$client->id}"
        );

        \App\Models\UserNotification::createForAdmins(
            $request->user()->tenant_id,
            'note_added',
            'Note added',
            "{$request->user()->name} added a note to {$client->full_name}'s profile.",
            "/clients/{$client->id}"
        );

        // Dispatch notification to admin users
        $this->notifyAdmins(
            $request->user()->tenant_id,
            'note_added',
            'Note added',
            "{$request->user()->name} added a note to {$client->full_name}'s profile.",
            "/clients/{$client->id}"
        );

        return response()->json([
            'message' => 'Note created.',
            'data'    => [
                'id'         => $note->id,
                'content'    => $note->content,
                'created_by' => ['id' => $request->user()->id, 'name' => $request->user()->name],
                'created_at' => $note->created_at?->toISOString(),
                'updated_at' => $note->updated_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * PUT /api/clients/{client}/notes/{note}
     * Only the note creator can edit their own note.
     */
    public function update(Request $request, Client $client, ClientNote $note): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if ($note->client_id !== $client->id) {
            abort(404);
        }

        // Only the creator can edit
        if ($note->created_by !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only edit your own notes.',
            ], 403);
        }

        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $note->update(['content' => $request->input('content')]);

        \App\Models\ActivityLog::log(
            $request->user()->tenant_id,
            $request->user()->id,
            'note_edited',
            "Edited a note on {$client->full_name}'s profile",
            "/clients/{$client->id}"
        );

        return response()->json([
            'message' => 'Note updated.',
            'data'    => [
                'id'         => $note->id,
                'content'    => $note->content,
                'created_by' => ['id' => $request->user()->id, 'name' => $request->user()->name],
                'created_at' => $note->created_at?->toISOString(),
                'updated_at' => $note->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * DELETE /api/clients/{client}/notes/{note}
     * Only users with delete_records permission (admin) can delete.
     */
    public function destroy(Request $request, Client $client, ClientNote $note): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if ($note->client_id !== $client->id || $note->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only admins (delete_records) can delete notes
        if (! $request->user()->can('delete_records')) {
            return response()->json([
                'message' => 'Only administrators can delete notes.',
            ], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted.']);
    }

    private function authorizeTenant(Request $request, Client $client): void
    {
        if ($client->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }

    private function notifyAdmins(int $tenantId, string $type, string $title, string $message, string $link = null): void
    {
        // We'll implement this fully in Section 3
        // Leave as a stub for now — won't crash
        try {
            \App\Models\UserNotification::createForAdmins($tenantId, $type, $title, $message, $link);
        } catch (\Throwable $e) {
            // Silently fail — notification failure must never break the main action
        }
    }
}
