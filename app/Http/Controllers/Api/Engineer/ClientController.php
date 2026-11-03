<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engineer\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::query()
            ->with(['farms' => fn ($query) => $query->withCount('openTasks')])
            ->latest('id')
            ->get();

        return response()->json(['data' => ClientResource::collection($clients)]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'phone_verified_at' => null,
        ]);

        return response()->json(['data' => new ClientResource($client)], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load(['farms' => fn ($query) => $query->with('nextVisit')->withCount('openTasks')]);

        return response()->json(['data' => new ClientResource($client)]);
    }
}
