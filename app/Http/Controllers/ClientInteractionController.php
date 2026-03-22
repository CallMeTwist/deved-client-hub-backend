<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientInteractionController extends Controller
{
    public function index(Request $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        $interactions = ClientInteraction::where('tenant_id', $request->user()->tenant_id)
            ->where('client_id', $client->id)
            ->with('creator:id,name')
            ->orderByDesc('interacted_at')
            ->get();

        return response()->json([
            'data' => $interactions->map(fn ($i) => [
                'id'                => $i->id,
                'type'              => $i->type,
                'summary'           => $i->summary,
                'duration'          => $i->formatted_duration,
                'interacted_at'     => $i->interacted_at?->toISOString(),
                'created_by'        => $i->creator ? ['id' => $i->creator->id, 'name' => $i->creator->name] : null,
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
            'type'             => ['required', 'in:call,email,visit,meeting'],
            'summary'          => ['required', 'string', 'max:2000'],
            'interacted_at'    => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $interaction = ClientInteraction::create([
            'tenant_id'        => $request->user()->tenant_id,
            'client_id'        => $client->id,
            'created_by'       => $request->user()->id,
            'type'             => $request->type,
            'summary'          => $request->summary,
            'interacted_at'    => $request->interacted_at ?? now(),
            'duration_minutes' => $request->duration_minutes,
        ]);

        return response()->json([
            'message' => 'Interaction logged.',
            'data'    => [
                'id'            => $interaction->id,
                'type'          => $interaction->type,
                'summary'       => $interaction->summary,
                'duration'      => $interaction->formatted_duration,
                'interacted_at' => $interaction->interacted_at?->toISOString(),
                'created_by'    => ['id' => $request->user()->id, 'name' => $request->user()->name],
            ],
        ], 201);
    }

    public function destroy(Request $request, Client $client, ClientInteraction $interaction): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if ($interaction->client_id !== $client->id || $interaction->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $interaction->delete();

        return response()->json(['message' => 'Interaction deleted.']);
    }

    private function authorizeTenant(Request $request, Client $client): void
    {
        if ($client->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
