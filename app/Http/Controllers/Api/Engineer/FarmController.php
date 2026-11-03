<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engineer\StoreFarmRequest;
use App\Http\Requests\Engineer\UpdateFarmRequest;
use App\Http\Resources\FarmResource;
use App\Models\Client;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;

class FarmController extends Controller
{
    public function store(StoreFarmRequest $request, Client $client): JsonResponse
    {
        $farm = Farm::create([
            ...$request->validated(),
            'client_id' => $client->id,
            'created_by' => $request->user()->id,
        ]);

        $farm->load('client')->loadCount('openTasks');

        return response()->json(['data' => new FarmResource($farm)], 201);
    }

    public function show(Farm $farm): JsonResponse
    {
        $farm->load(['client', 'nextVisit', 'tasks'])->loadCount('openTasks');

        return response()->json(['data' => new FarmResource($farm)]);
    }

    public function update(UpdateFarmRequest $request, Farm $farm): JsonResponse
    {
        $farm->update($request->validated());

        $farm->load(['client', 'nextVisit'])->loadCount('openTasks');

        return response()->json(['data' => new FarmResource($farm)]);
    }
}
