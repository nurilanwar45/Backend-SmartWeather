<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ForecastWeatherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
    }

    public function test_it_returns_normalized_forecast(): void
    {
        Http::preventStrayRequests();

        $url = config('services.openweather.base_url').'/forecast';

        Http::fake([
            $url.'*' => Http::response([
                'city' => [
                    'name' => 'Kota Semarang',
                    'country' => 'ID',
                    'coord' => ['lat' => -6.99, 'lon' => 110.42],
                    'timezone' => 25200,
                ],
                'list' => [
                    [
                        'dt' => 1794160800,
                        'main' => [
                            'temp' => 28.44,
                            'humidity' => 77,
                        ],
                        'pop' => 0.7,
                        'weather' => [
                            [
                                'description' => 'awan tersebar',
                                'icon' => '03n',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->getJson(
            '/api/v1/weather/forecast?latitude=-6.99&longitude=110.42'
        )
            ->assertOk()
            ->assertJsonPath('data.location.name', 'Kota Semarang')
            ->assertJsonPath('data.interval_hours', 3)
            ->assertJsonCount(1, 'data.forecasts')
            ->assertJsonPath(
                'data.forecasts.0.temperature_celsius',
                28.44
            )
            ->assertJsonPath(
                'data.forecasts.0.rain_probability_percent',
                70
            );

        Http::assertSentCount(1);
    }

    public function test_it_rejects_invalid_coordinates(): void
    {
        Http::fake();

        $this->getJson(
            '/api/v1/weather/forecast?latitude=-6.99&longitude=200'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);

        Http::assertNothingSent();
    }

    public function test_it_handles_provider_failure(): void
    {
        Http::preventStrayRequests();

        $url = config('services.openweather.base_url').'/forecast';

        Http::fake([
            $url.'*' => Http::response([], 500),
        ]);

        $this->getJson(
            '/api/v1/weather/forecast?latitude=-6.99&longitude=110.42'
        )
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Prakiraan cuaca sementara tidak tersedia. Silakan coba lagi.',
            ]);
    }
}