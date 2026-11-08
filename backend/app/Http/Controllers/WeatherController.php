<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function current(
        Request $request,
        WeatherService $weatherService
    ): JsonResponse {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $weather = $weatherService->current(
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );
        } catch (ConnectionException | RequestException $exception) {
            return response()->json([
                'message' => 'Data cuaca sementara tidak tersedia. Silakan coba lagi.',
            ], 503);
        }

        return response()->json([
            'data' => $weather,
        ]);
    }

            public function forecast(
            Request $request,
            WeatherService $weatherService
            ): JsonResponse {
            $validated = $request->validate([
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            try {
                $forecast = $weatherService->forecast(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
            } catch (ConnectionException | RequestException $exception) {
                return response()->json([
                    'message' => 'Prakiraan cuaca sementara tidak tersedia. Silakan coba lagi.',
                ], 503);
            }

            return response()->json([
                'data' => $forecast,
            ]);
        }
}