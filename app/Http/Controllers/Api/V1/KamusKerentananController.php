<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\KamusKerentanan;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKamusKerentananRequest;
use App\Http\Requests\UpdateKamusKerentananRequest;
use App\Http\Resources\KamusKerentananResource;

class KamusKerentananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return KamusKerentananResource::collection(KamusKerentanan::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKamusKerentananRequest $request)
    {
        $kamusKerentanan = KamusKerentanan::create($request->validated());

        return KamusKerentananResource::make($kamusKerentanan);
    }

    /**
     * Display the specified resource.
     */
    public function show(KamusKerentanan $kamusKerentanan)
    {
        return KamusKerentananResource::make($kamusKerentanan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKamusKerentananRequest $request, KamusKerentanan $kamusKerentanan)
    {
        $kamusKerentanan->update($request->validated());

        return KamusKerentananResource::make($kamusKerentanan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KamusKerentanan $kamusKerentanan)
    {
        $kamusKerentanan->delete();

        return response()->noContent();
    }
}
