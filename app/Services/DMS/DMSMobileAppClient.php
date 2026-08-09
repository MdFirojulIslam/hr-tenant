<?php


namespace App\Services\Dms;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Server-side client for the DMS Mobile App Outlet API.
 * Never expose the API key to Flutter or browser responses.
 */
class DMSMobileAppClient
{
  public function listDistributors(): array
  {
    return $this->get('/v2.0/mobile-app/distributors')['data'] ?? [];
  }

  /**
   * @param  array{q?: string|null, page?: int, per_page?: int, active?: int|string|null}  $filters
   * @return array{data: array<int, array>, meta: array}
   */
  public function listOutlets(int $distributorId, array $filters = []): array
  {
    $query = array_filter([
      'q' => $filters['q'] ?? null,
      'page' => $filters['page'] ?? 1,
      'per_page' => $filters['per_page'] ?? 25,
      'active' => $filters['active'] ?? 1,
    ], fn($v) => $v !== null && $v !== '');

    $payload = $this->get(
      '/v2.0/mobile-app/distributors/' . $distributorId . '/outlets',
      $query
    );

    return [
      'data' => $payload['data'] ?? [],
      'meta' => $payload['meta'] ?? [
        'current_page' => 1,
        'per_page' => 25,
        'total' => 0,
        'last_page' => 1,
      ],
    ];
  }

  /**
   * @param  array<string, mixed>  $query
   * @return array<string, mixed>
   */
  private function get(string $path, array $query = []): array
  {
    $base = config('dms_mobile_app.base_url');
    $key = (string) config('dms_mobile_app.api_key', '');
    $header = (string) config('dms_mobile_app.api_key_header', 'X-Mobile-App-Key');

    if ($base === '' || $key === '') {
      throw new RuntimeException('DMS Mobile App API is not configured.', 503);
    }

    $url = $base . $path;
    $started = microtime(true);

    try {
      $response = Http::timeout((int) config('dms_mobile_app.timeout', 45))
        ->acceptJson()
        ->withHeaders([$header => $key])
        ->get($url, $query);

      if ($response->status() === 401) {
        throw new RuntimeException('DMS Mobile App API authentication failed.', 502);
      }

      if ($response->status() === 404) {
        throw new RuntimeException($response->json('error') ?: 'Distributor not found.', 404);
      }

      if ($response->status() === 503) {
        throw new RuntimeException($response->json('error') ?: 'Tenant unavailable.', 503);
      }

      if ($response->failed()) {
        throw new RuntimeException(
          $response->json('error') ?: 'DMS Mobile App API request failed.',
          $response->status() >= 400 ? $response->status() : 502
        );
      }

      $json = $response->json();
      if (! is_array($json) || ($json['success'] ?? false) !== true) {
        throw new RuntimeException('Unexpected DMS Mobile App API response.', 502);
      }

      Log::info('dms_mobile_app.ok', [
        'path' => $path,
        'distributor_hint' => $query['distributor_id'] ?? null,
        'duration_ms' => (int) ((microtime(true) - $started) * 1000),
      ]);

      return $json;
    } catch (RequestException $e) {
      Log::warning('dms_mobile_app.http_error', [
        'path' => $path,
        'duration_ms' => (int) ((microtime(true) - $started) * 1000),
      ]);
      throw new RuntimeException('DMS Mobile App API unreachable.', 502);
    }
  }
}
