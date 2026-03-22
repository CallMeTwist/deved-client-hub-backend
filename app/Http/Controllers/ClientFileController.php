<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientFileController extends Controller
{
    public function index(Request $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        $files = ClientFile::where('tenant_id', $request->user()->tenant_id)
            ->where('client_id', $client->id)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $files->map(fn ($f) => [
                'id'            => $f->id,
                'name'          => $f->name,
                'mime_type'     => $f->mime_type,
                'size'          => $f->size,
                'formatted_size'=> $f->formatted_size,
                'uploaded_by'   => $f->uploader ? ['id' => $f->uploader->id, 'name' => $f->uploader->name] : null,
                'created_at'    => $f->created_at?->toISOString(),
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
            'file' => ['required', 'file', 'max:20480'], // 20MB max
        ]);

        $uploaded = $request->file('file');
        $path     = $uploaded->store("clients/{$client->id}/files", 'local');

        $file = ClientFile::create([
            'tenant_id'   => $request->user()->tenant_id,
            'client_id'   => $client->id,
            'uploaded_by' => $request->user()->id,
            'name'        => $uploaded->getClientOriginalName(),
            'path'        => $path,
            'mime_type'   => $uploaded->getMimeType(),
            'size'        => $uploaded->getSize(),
        ]);

        \App\Models\UserNotification::createForAdmins(
            $request->user()->tenant_id,
            'file_uploaded',
            'File uploaded',
            "{$request->user()->name} uploaded {$file->name} for {$client->full_name}.",
            "/clients/{$client->id}"
        );

        return response()->json([
            'message' => 'File uploaded.',
            'data'    => [
                'id'            => $file->id,
                'name'          => $file->name,
                'mime_type'     => $file->mime_type,
                'size'          => $file->size,
                'formatted_size'=> $file->formatted_size,
                'uploaded_by'   => ['id' => $request->user()->id, 'name' => $request->user()->name],
                'created_at'    => $file->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function download(Request $request, Client $client, ClientFile $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeTenant($request, $client);

        if ($file->client_id !== $client->id || $file->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($file->path)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk('local')->download($file->path, $file->name);
    }

    public function preview(Request $request, Client $client, ClientFile $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeTenant($request, $client);

        if ($file->client_id !== $client->id || $file->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($file->path)) {
            abort(404, 'File not found on disk.');
        }

        $mimeType = $file->mime_type ?? 'application/octet-stream';

        return response()->stream(function () use ($file) {
            $stream = Storage::disk('local')->readStream($file->path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $file->name . '"',
        ]);
    }

    public function destroy(Request $request, Client $client, ClientFile $file): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if ($file->client_id !== $client->id || $file->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        Storage::disk('local')->delete($file->path);
        $file->delete();

        return response()->json(['message' => 'File deleted.']);
    }

    private function authorizeTenant(Request $request, Client $client): void
    {
        if ($client->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
