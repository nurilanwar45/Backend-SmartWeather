<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function current(float $latitude, float $longitude): array
    {
        $response = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->get(
                config('services.openweather.base_url').'/weather',
                [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => config('services.openweather.key'),
                    'units' => 'metric',
                    'lang' => 'id',
                ]
            );

        $response->throw();

        return $response->json();
    }
}