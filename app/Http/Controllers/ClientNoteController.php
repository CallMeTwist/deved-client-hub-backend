<?php

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
                'created_by' => $n->creator ? ['id' => $n->creator->id, 'name' => $n->creator->name] : null,
                'created_at' => $n->created_at?->toISOString(),
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

        return response()->json([
            'message' => 'Note created.',
            'data'    => [
                'id'         => $note->id,
                'content'    => $note->content,
                'created_by' => ['id' => $request->user()->id, 'name' => $request->user()->name],
                'created_at' => $note->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function destroy(Request $request, Client $client, ClientNote $note): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if ($note->client_id !== $client->id || $note->tenant_id !== $request->user()->tenant_id) {
            abort(403);
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
}
