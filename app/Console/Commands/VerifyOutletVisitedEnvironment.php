<?php

namespace App\Console\Commands;

use App\Services\Diagnostics\OutletVisitedEnvironmentDiagnostic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Temporary read-only diagnostic for Outlet Visited environment readiness.
 *
 * Credentials must come from VERIFY_DB_* env vars (or .env.verify loaded manually).
 * Never pass passwords on the CLI argv.
 */
class VerifyOutletVisitedEnvironment extends Command
{
  protected $signature = 'outlet-visited:verify-environment
                            {--local-only : Scan only the local DB_* connection}
                            {--write-docs : Write comparison markdown into the Flutter workspace docs path}';

  protected $description = 'Read-only LOCAL vs ONLINE database verification for Outlet Visited (no schema changes)';

  public function handle(OutletVisitedEnvironmentDiagnostic $diagnostic): int
  {
    $this->info('Outlet Visited — Environment Diagnostic (READ-ONLY)');
    $this->line('No INSERT/UPDATE/DELETE/ALTER/CREATE will be executed.');
    $this->newLine();

    $verifyCreds = $this->resolveVerifyCredentials();

    $localCreds = [
      'host' => (string) env('DB_HOST', '127.0.0.1'),
      'port' => (int) env('DB_PORT', 3306),
      'username' => (string) env('DB_USERNAME', 'root'),
      'password' => (string) env('DB_PASSWORD', ''),
    ];

    $this->info('Scanning LOCAL...');
    $local = $diagnostic->run($localCreds, 'local');
    $this->printDbSummary($local);

    $online = null;
    $comparison = null;
    $localOnly = (bool) $this->option('local-only');

    $verifyHost = trim((string) ($verifyCreds['host'] ?? ''));
    $verifyUser = trim((string) ($verifyCreds['username'] ?? ''));

    if (! $localOnly && $verifyHost !== '' && $verifyUser !== '') {
      $this->newLine();
      $this->info('Scanning ONLINE via VERIFY_DB_* ...');
      $online = $diagnostic->run([
        'host' => $verifyHost,
        'port' => (int) ($verifyCreds['port'] ?? 3306),
        'username' => $verifyUser,
        'password' => (string) ($verifyCreds['password'] ?? ''),
      ], 'online');
      $this->printDbSummary($online);
      $comparison = $diagnostic->compare($local, $online);
      $this->newLine();
      $this->info('Recommendation: ' . ($comparison['recommendation']['verdict'] ?? 'UNKNOWN'));
    } else {
      $this->newLine();
      $this->warn('ONLINE scan skipped.');
      $this->line('Create gitignored `.env.verify` from `.env.verify.example` with:');
      $this->line('  VERIFY_DB_HOST / VERIFY_DB_PORT / VERIFY_DB_USERNAME / VERIFY_DB_PASSWORD');
      $this->line('Then re-run: php artisan outlet-visited:verify-environment --write-docs');
      $this->line('Or use temporary UI: /diagnostics/outlet-visited-environment');
    }

    $storageDir = storage_path('app/diagnostics/outlet_visited');
    File::ensureDirectoryExists($storageDir);

    $payload = [
      'local' => $local,
      'online' => $online,
      'comparison' => $comparison,
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    File::put($storageDir . '/last_report.json', $json);

    $markdown = $diagnostic->toMarkdown($local, $online, $comparison);
    File::put($storageDir . '/last_report.md', $markdown);

    if ($this->option('write-docs')) {
      $docsPath = 'd:/Projects/Adnroid Studio Projects/Artisan HRIS/OUTLET_VISITED_ENVIRONMENT_COMPARISON.md';
      File::put($docsPath, $markdown);
      $this->info('Wrote: ' . $docsPath);
    }

    $this->newLine();
    $this->info('Saved (no secrets):');
    $this->line(' - ' . $storageDir . '/last_report.json');
    $this->line(' - ' . $storageDir . '/last_report.md');

    if ($online === null) {
      return self::FAILURE;
    }

    $blocked = false;
    foreach ($online['readiness'] ?? [] as $status) {
      if ($status !== 'READY') {
        $blocked = true;
      }
    }

    return $blocked ? self::FAILURE : self::SUCCESS;
  }

  /**
   * Resolve VERIFY_DB_* from gitignored `.env.verify` first, then process env.
   * Does not modify production `.env`. Never prints password values.
   *
   * @return array{host:?string,port:int,username:?string,password:?string}
   */
  private function resolveVerifyCredentials(): array
  {
    $values = [
      'VERIFY_DB_HOST' => null,
      'VERIFY_DB_PORT' => '3306',
      'VERIFY_DB_USERNAME' => null,
      'VERIFY_DB_PASSWORD' => null,
    ];

    $path = base_path('.env.verify');
    if (is_file($path)) {
      $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
          continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (! array_key_exists($key, $values)) {
          continue;
        }
        $value = trim($value);
        if (
          (str_starts_with($value, '"') && str_ends_with($value, '"'))
          || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
          $value = substr($value, 1, -1);
        }
        $values[$key] = $value;
      }
      $this->line('Loaded VERIFY_DB_* from .env.verify (gitignored).');
    }

    foreach ($values as $key => $current) {
      if ($current !== null && $current !== '') {
        continue;
      }
      $fromEnv = getenv($key);
      if ($fromEnv !== false && $fromEnv !== '') {
        $values[$key] = $fromEnv;
      }
    }

    return [
      'host' => $values['VERIFY_DB_HOST'],
      'port' => (int) ($values['VERIFY_DB_PORT'] ?: 3306),
      'username' => $values['VERIFY_DB_USERNAME'],
      'password' => $values['VERIFY_DB_PASSWORD'] ?? '',
    ];
  }

  /**
   * @param  array<string, mixed>  $report
   */
  private function printDbSummary(array $report): void
  {
    $this->line('Host: ' . ($report['host_masked'] ?? ''));
    $this->line('User: ' . ($report['username_masked'] ?? ''));
    foreach ($report['databases'] ?? [] as $name => $db) {
      $access = (($db['database_accessible'] ?? false) === true) ? 'PASS' : 'FAIL';
      $this->line(sprintf(
        '  %-16s Connection=%-4s Auth=%-4s DB=%-4s',
        $name,
        $db['connection'] ?? '?',
        $db['authentication'] ?? '?',
        $access
      ));
      if (! empty($db['error'])) {
        $this->line('    error: ' . $db['error']);
      }
    }
  }
}
