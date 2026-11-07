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

        $data = $response->json();

return [
    'location' => [
        'name' => $data['name'],
        'country' => $data['sys']['country'] ?? null,
        'latitude' => $data['coord']['lat'],
        'longitude' => $data['coord']['lon'],
    ],
    'current' => [
        'temperature_celsius' => $data['main']['temp'],
        'feels_like_celsius' => $data['main']['feels_like'],
        'humidity_percent' => $data['main']['humidity'],
        'wind_speed_mps' => $data['wind']['speed'] ?? null,
        'description' => $data['weather'][0]['description'],
        'icon_code' => $data['weather'][0]['icon'],
    ],
    'observed_at' => $data['dt'],
    'timezone_offset_seconds' => $data['timezone'],
];
    }
}