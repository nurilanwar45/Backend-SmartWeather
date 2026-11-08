<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrentWeatherTest extends TestCase
{
    public function test_it_returns_normalized_current_weather(): void
    {
        Http::preventStrayRequests();

        $url = config('services.openweather.base_url').'/weather';

        Http::fake([
            $url.'*' => Http::response([
                'name' => 'Kota Semarang',
                'sys' => ['country' => 'ID'],
                'coord' => ['lat' => -6.99, 'lon' => 110.42],
                'main' => [
                    'temp' => 28.9,
                    'feels_like' => 34.89,
                    'humidity' => 82,
                ],
                'wind' => ['speed' => 1.3],
                'weather' => [
                    [
                        'description' => 'awan pecah',
                        'icon' => '04n',
                    ],
                ],
                'dt' => 1794074920,
                'timezone' => 25200,
            ], 200),
        ]);

        $response = $this->getJson(
            '/api/v1/weather/current?latitude=-6.99&longitude=110.42'
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.location.name', 'Kota Semarang')
            ->assertJsonPath('data.current.temperature_celsius', 28.9)
            ->assertJsonPath('data.observed_at', 1794074920);

        Http::assertSent(function ($request) use ($url) {
            return $request->method() === 'GET'
                && strtok($request->url(), '?') === $url
                && (float) $request['lat'] === -6.99
                && (float) $request['lon'] === 110.42
                && $request['units'] === 'metric';
        });
    }

    public function test_it_rejects_invalid_coordinates(): void
    {
        Http::fake();

        $response = $this->getJson(
            '/api/v1/weather/current?latitude=100&longitude=110.42'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);

        Http::assertNothingSent();
    }

    public function test_it_returns_service_unavailable_when_provider_fails(): void
    {
        Http::preventStrayRequests();

        $url = config('services.openweather.base_url').'/weather';

        Http::fake([
            $url.'*' => Http::response([
                'message' => 'Provider unavailable',
            ], 500),
        ]);

        $response = $this->getJson(
            '/api/v1/weather/current?latitude=-6.99&longitude=110.42'
        );

        $response
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Data cuaca sementara tidak tersedia. Silakan coba lagi.',
            ]);
    }

         protected function setUp(): void
        {
            parent::setUp();

            config(['cache.default' => 'array']);
        }
    public function test_it_caches_weather_for_the_same_coordinates(): void
            {
                Http::preventStrayRequests();

                $url = config('services.openweather.base_url').'/weather';

                Http::fake([
                    $url.'*' => Http::response([
                        'name' => 'Kota Semarang',
                        'sys' => ['country' => 'ID'],
                        'coord' => ['lat' => -6.99, 'lon' => 110.42],
                        'main' => [
                            'temp' => 28.9,
                            'feels_like' => 34.89,
                            'humidity' => 82,
                        ],
                        'wind' => ['speed' => 1.3],
                        'weather' => [
                            [
                                'description' => 'awan pecah',
                                'icon' => '04n',
                            ],
                        ],
                        'dt' => 1794074920,
                        'timezone' => 25200,
                    ], 200),
                ]);

                $endpoint = '/api/v1/weather/current'
                    .'?latitude=-6.99&longitude=110.42';

                // Request pertama mengambil data provider.
                $firstResponse = $this->getJson($endpoint);

                // Request kedua memakai cache.
                $secondResponse = $this->getJson($endpoint);

                $firstResponse->assertOk();
                $secondResponse->assertOk();

                $this->assertSame(
                    $firstResponse->json(),
                    $secondResponse->json()
                );

                Http::assertSentCount(1);

                // Setelah lima menit, cache kedaluwarsa.
                $this->travel(5)->minutes();

                $this->getJson($endpoint)->assertOk();

                Http::assertSentCount(2);

                $this->travelBack();
            }
            public function test_it_limits_weather_requests_per_ip(): void
            {
                Http::fake();

                $endpoint = '/api/v1/weather/current'
                    .'?latitude=100&longitude=110.42';

                for ($attempt = 0; $attempt < 10; $attempt++) {
                    $this->getJson($endpoint)
                        ->assertUnprocessable();
                }

                $this->getJson($endpoint)
                    ->assertStatus(429)
                    ->assertHeader('Retry-After');

                Http::assertNothingSent();
            }
         }