<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function current(float $latitude, float $longitude): array
{
    
    $cacheKey = 'weather:current:v1:'.hash(
        'sha256',
        json_encode([$latitude, $longitude, 'metric', 'id'])
    );

    return Cache::remember(
        $cacheKey,
        now()->addMinutes(5),
        function () use ($latitude, $longitude): array {
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

    );
}
    public function forecast(float $latitude, float $longitude): array
    {
            $cacheKey = 'weather:forecast:v1:'.hash(
                'sha256',
                json_encode([$latitude, $longitude, 'metric', 'id'])
            );

            return Cache::remember(
                $cacheKey,
                now()->addMinutes(10),
                function () use ($latitude, $longitude): array {
                    $response = Http::acceptJson()
                        ->connectTimeout(5)
                        ->timeout(10)
                        ->get(
                            config('services.openweather.base_url').'/forecast',
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
                            'name' => $data['city']['name'],
                            'country' => $data['city']['country'] ?? null,
                            'latitude' => $data['city']['coord']['lat'],
                            'longitude' => $data['city']['coord']['lon'],
                        ],
                        'interval_hours' => 3,
                        'timezone_offset_seconds' => $data['city']['timezone'],
                        'forecasts' => array_map(
                            function (array $item): array {
                                return [
                                    'forecast_at' => $item['dt'],
                                    'temperature_celsius' => $item['main']['temp'],
                                    'humidity_percent' => $item['main']['humidity'],
                                    'rain_probability_percent' => isset($item['pop'])
                                        ? round($item['pop'] * 100, 1)
                                        : null,
                                    'description' => $item['weather'][0]['description'],
                                    'icon_code' => $item['weather'][0]['icon'],
                                ];
                            },
                            $data['list']
                        ),
                    ];
                }
            );
        }
}