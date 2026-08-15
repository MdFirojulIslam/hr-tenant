<?php

namespace App\Services\Diagnostics;

use PDO;
use PDOException;
use Throwable;

/**
 * Temporary, read-only environment diagnostic for Outlet Visited readiness.
 * Never writes to target databases. Never returns or logs passwords.
 */
class OutletVisitedEnvironmentDiagnostic
{
  public const DATABASES = [
    'hr_db',
    'artisan_admin',
    'abnb_ds_nbsmt',
  ];

  /** Columns of interest for employee relationship checks. */
  private const HR_EMPLOYEE_COLUMNS = [
    'id',
    'Employee_Id',
    'Full_Name',
    'Company_Id',
    'created_at',
    'updated_at',
  ];

  private const HR_USER_COLUMNS = [
    'id',
    'name',
    'email',
    'EID',
    'role_id',
    'active',
  ];

  private const OUTLET_COLUMNS = [
    'id',
    'code',
    'short_name',
    'name',
    'address',
    'latitude',
    'longitude',
    'active_1',
    'deleted_at',
    'created_at',
    'updated_at',
  ];

  private const PERMISSION_TABLES = [
    'navigations',
    'pages',
    'user_has_pages',
    'user_has_page',
    'permissions',
    'roles',
    'model_has_permissions',
    'model_has_roles',
    'role_has_permissions',
    'brands',
    'admin_roles',
    'admin_role_policies',
    'admin_users',
    'settings',
  ];

  /**
   * @param  array{host:string,port?:int|string,username:string,password?:string}  $credentials
   * @return array<string, mixed>
   */
  public function run(array $credentials, string $label = 'target'): array
  {
    $host = trim((string) ($credentials['host'] ?? ''));
    $port = (int) ($credentials['port'] ?? 3306);
    $username = (string) ($credentials['username'] ?? '');
    $password = (string) ($credentials['password'] ?? '');

    $report = [
      'label' => $label,
      'scanned_at' => now()->toIso8601String(),
      'host_masked' => $this->maskHost($host),
      'port' => $port,
      'username_masked' => $this->maskUsername($username),
      'databases' => [],
      'employee_structure' => [],
      'outlet_structure' => [],
      'permission_structure' => [],
      'schema_fingerprint' => [],
      'open_questions' => [
        'Week 5 behavior — UNKNOWN / BUSINESS DECISION REQUIRED',
        'Single vs multiple visits per assignment — BUSINESS RULE STILL OPEN',
        'Delivery week_no representation (NULL vs 0) — TBD after uniqueness review',
        'Outlet Visited page permission ID(s) — UNKNOWN / TBD (do not invent)',
        'Physical cross-DB FK to abnb_ds_nbsmt.outlets — not recommended unless infra supports it',
      ],
      'errors' => [],
    ];

    if ($host === '' || $username === '') {
      $report['errors'][] = 'Host and username are required.';
      return $report;
    }

    foreach (self::DATABASES as $database) {
      $report['databases'][$database] = $this->probeDatabase(
        $host,
        $port,
        $username,
        $password,
        $database
      );
    }

    if (($report['databases']['hr_db']['database_accessible'] ?? false) === true) {
      $pdo = $this->connect($host, $port, $username, $password, 'hr_db');
      if ($pdo) {
        $report['employee_structure'] = $this->inspectEmployees($pdo);
        $report['permission_structure']['hr_db'] = $this->inspectPermissions($pdo, 'hr_db');
        $report['schema_fingerprint']['hr_db'] = $this->fingerprintTables($pdo, 'hr_db', [
          'employees',
          'users',
          'navigations',
          'pages',
          'user_has_pages',
          'permissions',
          'roles',
        ]);
        $this->safeClose($pdo);
      }
    }

    if (($report['databases']['artisan_admin']['database_accessible'] ?? false) === true) {
      $pdo = $this->connect($host, $port, $username, $password, 'artisan_admin');
      if ($pdo) {
        $report['permission_structure']['artisan_admin'] = $this->inspectPermissions($pdo, 'artisan_admin');
        $report['schema_fingerprint']['artisan_admin'] = $this->fingerprintTables($pdo, 'artisan_admin', [
          'navigations',
          'brands',
          'admin_roles',
          'admin_users',
          'settings',
        ]);
        $this->safeClose($pdo);
      }
    }

    if (($report['databases']['abnb_ds_nbsmt']['database_accessible'] ?? false) === true) {
      $pdo = $this->connect($host, $port, $username, $password, 'abnb_ds_nbsmt');
      if ($pdo) {
        $report['outlet_structure'] = $this->inspectOutlets($pdo);
        $report['schema_fingerprint']['abnb_ds_nbsmt'] = $this->fingerprintTables($pdo, 'abnb_ds_nbsmt', [
          'outlets',
        ]);
        $this->safeClose($pdo);
      }
    }

    $report['readiness'] = $this->evaluateReadiness($report);

    return $report;
  }

  /**
   * Compare two diagnostic reports (typically local vs online).
   *
   * @param  array<string, mixed>  $local
   * @param  array<string, mixed>  $online
   * @return array<string, mixed>
   */
  public function compare(array $local, array $online): array
  {
    $items = [];

    foreach (self::DATABASES as $db) {
      $localOk = ($local['databases'][$db]['database_accessible'] ?? false) === true;
      $onlineOk = ($online['databases'][$db]['database_accessible'] ?? false) === true;
      $items[] = [
        'item' => "{$db} access",
        'local' => $localOk ? 'accessible' : 'not accessible',
        'online' => $onlineOk ? 'accessible' : 'not accessible',
        'status' => $this->statusMatch($localOk, $onlineOk, true),
      ];
    }

    $columnChecks = [
      ['employees.id', $local['employee_structure']['columns']['id'] ?? null, $online['employee_structure']['columns']['id'] ?? null],
      ['employees.Employee_Id', $local['employee_structure']['columns']['Employee_Id'] ?? null, $online['employee_structure']['columns']['Employee_Id'] ?? null],
      ['employees.Full_Name', $local['employee_structure']['columns']['Full_Name'] ?? null, $online['employee_structure']['columns']['Full_Name'] ?? null],
      ['users.EID', $local['employee_structure']['user_columns']['EID'] ?? null, $online['employee_structure']['user_columns']['EID'] ?? null],
      ['outlets.id', $local['outlet_structure']['columns']['id'] ?? null, $online['outlet_structure']['columns']['id'] ?? null],
      ['outlets.code', $local['outlet_structure']['columns']['code'] ?? null, $online['outlet_structure']['columns']['code'] ?? null],
      ['outlets.name', $local['outlet_structure']['columns']['name'] ?? null, $online['outlet_structure']['columns']['name'] ?? null],
      ['outlets.latitude', $local['outlet_structure']['columns']['latitude'] ?? null, $online['outlet_structure']['columns']['latitude'] ?? null],
      ['outlets.longitude', $local['outlet_structure']['columns']['longitude'] ?? null, $online['outlet_structure']['columns']['longitude'] ?? null],
      ['outlets.active_1', $local['outlet_structure']['columns']['active_1'] ?? null, $online['outlet_structure']['columns']['active_1'] ?? null],
      ['outlets.deleted_at', $local['outlet_structure']['columns']['deleted_at'] ?? null, $online['outlet_structure']['columns']['deleted_at'] ?? null],
    ];

    foreach ($columnChecks as [$name, $localCol, $onlineCol]) {
      $items[] = $this->compareColumnMeta($name, $localCol, $onlineCol);
    }

    $items[] = [
      'item' => 'users.EID → employees.id compatibility',
      'local' => $local['employee_structure']['eid_to_employees_id']['status'] ?? 'UNKNOWN',
      'online' => $online['employee_structure']['eid_to_employees_id']['status'] ?? 'UNKNOWN',
      'status' => $this->statusText(
        $local['employee_structure']['eid_to_employees_id']['status'] ?? null,
        $online['employee_structure']['eid_to_employees_id']['status'] ?? null
      ),
    ];

    $items[] = [
      'item' => 'outlets row count',
      'local' => (string) ($local['outlet_structure']['row_count'] ?? 'UNKNOWN'),
      'online' => (string) ($online['outlet_structure']['row_count'] ?? 'UNKNOWN'),
      'status' => $this->countStatus(
        $local['outlet_structure']['row_count'] ?? null,
        $online['outlet_structure']['row_count'] ?? null
      ),
    ];

    foreach (['navigations', 'pages', 'user_has_pages', 'brands'] as $table) {
      $localHr = $local['permission_structure']['hr_db']['tables'][$table] ?? null;
      $onlineHr = $online['permission_structure']['hr_db']['tables'][$table] ?? null;
      $localAdmin = $local['permission_structure']['artisan_admin']['tables'][$table] ?? null;
      $onlineAdmin = $online['permission_structure']['artisan_admin']['tables'][$table] ?? null;

      if ($localHr !== null || $onlineHr !== null) {
        $items[] = [
          'item' => "hr_db.{$table} exists",
          'local' => $localHr['exists'] ?? false ? 'yes' : 'no',
          'online' => $onlineHr['exists'] ?? false ? 'yes' : 'no',
          'status' => $this->statusMatch(
            (bool) ($localHr['exists'] ?? false),
            (bool) ($onlineHr['exists'] ?? false),
            true
          ),
        ];
      }

      if ($localAdmin !== null || $onlineAdmin !== null) {
        $items[] = [
          'item' => "artisan_admin.{$table} exists",
          'local' => $localAdmin['exists'] ?? false ? 'yes' : 'no',
          'online' => $onlineAdmin['exists'] ?? false ? 'yes' : 'no',
          'status' => $this->statusMatch(
            (bool) ($localAdmin['exists'] ?? false),
            (bool) ($onlineAdmin['exists'] ?? false),
            true
          ),
        ];
      }
    }

    $items[] = [
      'item' => 'Outlet Visited navigation/page ID',
      'local' => $local['permission_structure']['hr_db']['outlet_visited_pages'] ?? 'NOT FOUND — expected',
      'online' => $online['permission_structure']['hr_db']['outlet_visited_pages'] ?? 'NOT FOUND — expected',
      'status' => '❓ UNKNOWN',
    ];

    $differences = array_values(array_filter($items, function ($item) {
      return in_array($item['status'], ['⚠️ DIFFERENCE', '❌ MISSING'], true);
    }));

    return [
      'compared_at' => now()->toIso8601String(),
      'items' => $items,
      'differences' => $differences,
      'local_readiness' => $local['readiness'] ?? [],
      'online_readiness' => $online['readiness'] ?? [],
      'recommendation' => $this->recommendation($local, $online, $differences),
    ];
  }

  /**
   * Build markdown comparison report content (no secrets).
   *
   * @param  array<string, mixed>  $local
   * @param  array<string, mixed>|null  $online
   * @param  array<string, mixed>|null  $comparison
   */
  public function toMarkdown(array $local, ?array $online = null, ?array $comparison = null): string
  {
    $lines = [];
    $lines[] = '========================================';
    $lines[] = 'ARTISAN HRIS — ONLINE ENVIRONMENT CHECK';
    $lines[] = '========================================';
    $lines[] = '';
    $lines[] = 'Generated: ' . now()->toDateTimeString();
    $lines[] = 'Mode: READ-ONLY diagnostic (no DML/DDL executed on target DBs)';
    $lines[] = '';
    $lines[] = 'DATABASES';
    $lines[] = '---------';
    $lines[] = '';

    foreach (self::DATABASES as $db) {
      $lines[] = "{$db}:";
      $this->appendDbBlock($lines, 'Local', $local['databases'][$db] ?? []);
      if ($online) {
        $this->appendDbBlock($lines, 'Online', $online['databases'][$db] ?? []);
      } else {
        $lines[] = 'Online:';
        $lines[] = 'Connection: ❓ UNKNOWN (credentials not supplied yet)';
        $lines[] = 'Access: ❓ UNKNOWN';
        $lines[] = 'Status: ❓ UNKNOWN';
      }
      $lines[] = '';
    }

    $lines[] = 'EMPLOYEE STRUCTURE';
    $lines[] = '------------------';
    $lines[] = '';
    $lines[] = 'Local:';
    $lines[] = $this->formatEmployeeBlock($local['employee_structure'] ?? []);
    $lines[] = '';
    if ($online) {
      $lines[] = 'Online:';
      $lines[] = $this->formatEmployeeBlock($online['employee_structure'] ?? []);
    } else {
      $lines[] = 'Online: ❓ UNKNOWN — awaiting VERIFY_DB_* credentials';
    }
    $lines[] = '';
    $lines[] = 'Comparison:';
    $lines[] = $comparison
      ? $this->statusForItem($comparison, 'users.EID → employees.id compatibility')
      : '❓ UNKNOWN';
    $lines[] = 'Status: ' . ($comparison
      ? $this->statusForItem($comparison, 'employees.id')
      : '❓ UNKNOWN');
    $lines[] = '';

    $lines[] = 'OUTLET STRUCTURE';
    $lines[] = '----------------';
    $lines[] = '';
    $lines[] = 'Local:';
    $lines[] = $this->formatOutletBlock($local['outlet_structure'] ?? []);
    $lines[] = '';
    if ($online) {
      $lines[] = 'Online:';
      $lines[] = $this->formatOutletBlock($online['outlet_structure'] ?? []);
    } else {
      $lines[] = 'Online: ❓ UNKNOWN — awaiting VERIFY_DB_* credentials';
    }
    $lines[] = '';
    $lines[] = 'Comparison: ' . ($comparison
      ? $this->statusForItem($comparison, 'outlets.id')
      : '❓ UNKNOWN');
    $lines[] = 'Status: ' . ($online ? 'see IMPORTANT COLUMNS' : '❓ UNKNOWN');
    $lines[] = '';

    $lines[] = 'ARTISAN ADMIN / PERMISSIONS';
    $lines[] = '---------------------------';
    $lines[] = '';
    $lines[] = 'Local:';
    $lines[] = $this->formatPermissionBlock($local['permission_structure'] ?? []);
    $lines[] = '';
    if ($online) {
      $lines[] = 'Online:';
      $lines[] = $this->formatPermissionBlock($online['permission_structure'] ?? []);
    } else {
      $lines[] = 'Online: ❓ UNKNOWN — awaiting VERIFY_DB_* credentials';
    }
    $lines[] = 'Status: ' . ($online ? 'compared below' : '❓ UNKNOWN');
    $lines[] = '';

    $lines[] = 'IMPORTANT TABLES';
    $lines[] = '----------------';
    $lines[] = '';
    if ($comparison) {
      foreach ($comparison['items'] as $item) {
        if (str_contains($item['item'], ' exists') || str_contains($item['item'], ' access')) {
          $lines[] = 'Table/Access: ' . $item['item'];
          $lines[] = 'Local: ' . $item['local'];
          $lines[] = 'Online: ' . $item['online'];
          $lines[] = 'Status: ' . $item['status'];
          $lines[] = '';
        }
      }
    } else {
      $lines[] = 'Online comparison not available yet.';
      $lines[] = '';
    }

    $lines[] = 'IMPORTANT COLUMNS';
    $lines[] = '-----------------';
    $lines[] = '';
    if ($comparison) {
      foreach ($comparison['items'] as $item) {
        if (str_contains($item['item'], '.') && ! str_contains($item['item'], ' exists') && ! str_contains($item['item'], 'row count')) {
          $lines[] = 'Column: ' . $item['item'];
          $lines[] = 'Local: ' . $item['local'];
          $lines[] = 'Online: ' . $item['online'];
          $lines[] = 'Status: ' . $item['status'];
          $lines[] = '';
        }
      }
    } else {
      $lines[] = 'Online comparison not available yet.';
      $lines[] = '';
    }

    $lines[] = 'ROW COUNTS';
    $lines[] = '----------';
    $lines[] = '';
    $lines[] = 'Local:';
    $lines[] = '- employees: ' . ($local['employee_structure']['row_count'] ?? 'UNKNOWN');
    $lines[] = '- users: ' . ($local['employee_structure']['users_row_count'] ?? 'UNKNOWN');
    $lines[] = '- outlets: ' . ($local['outlet_structure']['row_count'] ?? 'UNKNOWN');
    $lines[] = '- outlets active: ' . ($local['outlet_structure']['active_row_count'] ?? 'UNKNOWN');
    $lines[] = '';
    if ($online) {
      $lines[] = 'Online:';
      $lines[] = '- employees: ' . ($online['employee_structure']['row_count'] ?? 'UNKNOWN');
      $lines[] = '- users: ' . ($online['employee_structure']['users_row_count'] ?? 'UNKNOWN');
      $lines[] = '- outlets: ' . ($online['outlet_structure']['row_count'] ?? 'UNKNOWN');
      $lines[] = '- outlets active: ' . ($online['outlet_structure']['active_row_count'] ?? 'UNKNOWN');
    } else {
      $lines[] = 'Online: ❓ UNKNOWN';
    }
    $lines[] = '';

    $lines[] = 'SCHEMA DIFFERENCES';
    $lines[] = '------------------';
    $lines[] = '';
    if (! $online) {
      $lines[] = '1. Online scan not executed — VERIFY_DB_* credentials not provided.';
      $lines[] = '2. Local `user_has_pages` table may be missing even though model/migration exist.';
      $lines[] = '3. Local `pages` table exists but may be empty; permissions appear driven by `navigations` + `user_has_pages`.';
    } elseif (empty($comparison['differences'])) {
      $lines[] = '1. No critical schema differences detected in checked columns.';
    } else {
      $i = 1;
      foreach ($comparison['differences'] as $diff) {
        $lines[] = "{$i}. {$diff['item']}: LOCAL={$diff['local']} ONLINE={$diff['online']} ({$diff['status']})";
        $i++;
      }
    }
    $lines[] = '';

    $lines[] = 'OUTLET VISITED READINESS';
    $lines[] = '------------------------';
    $lines[] = '';
    foreach (self::DATABASES as $db) {
      $localReady = $local['readiness'][$db] ?? 'BLOCKED';
      $onlineReady = $online['readiness'][$db] ?? 'BLOCKED';
      $lines[] = "{$db}:";
      $lines[] = 'Local: ' . $localReady;
      $lines[] = 'Online: ' . ($online ? $onlineReady : 'BLOCKED (not verified)');
      $lines[] = '';
    }

    $lines[] = 'OPEN BUSINESS QUESTIONS';
    $lines[] = '-----------------------';
    $lines[] = '';
    $lines[] = '1. Week 5 behavior — UNKNOWN / BUSINESS DECISION REQUIRED';
    $lines[] = '2. Single vs multiple visits per assignment — BUSINESS RULE STILL OPEN';
    $lines[] = '3. Delivery representation (`week_no` NULL vs 0) — TBD';
    $lines[] = '4. Page permission IDs — UNKNOWN / TBD (do not invent)';
    $lines[] = '5. Whether production uses only `abnb_ds_nbsmt` or multiple DS databases';
    $lines[] = '6. Cross-database physical FK support for outlets — logical reference preferred';
    $lines[] = '';

    $lines[] = 'RECOMMENDATION';
    $lines[] = '--------------';
    $lines[] = '';
    if (! $online) {
      $lines[] = 'BLOCKED — FIX THESE ITEMS FIRST';
      $lines[] = '- Provide online VERIFY_DB_* credentials (or use temporary diagnostic UI).';
      $lines[] = '- Re-run `php artisan outlet-visited:verify-environment`.';
      $lines[] = '- Do not implement Outlet Visited module until online comparison is reviewed.';
    } else {
      $lines[] = $comparison['recommendation']['verdict'] ?? 'BLOCKED — FIX THESE ITEMS FIRST';
      foreach (($comparison['recommendation']['reasons'] ?? []) as $reason) {
        $lines[] = '- ' . $reason;
      }
    }
    $lines[] = '';
    $lines[] = 'SECURITY NOTES';
    $lines[] = '--------------';
    $lines[] = '- Passwords were not written to this report.';
    $lines[] = '- Host/username are masked.';
    $lines[] = '- Diagnostic uses SELECT-only / information_schema queries.';
    $lines[] = '';

    return implode("\n", $lines);
  }

  /**
   * @return array<string, mixed>
   */
  private function probeDatabase(
    string $host,
    int $port,
    string $username,
    string $password,
    string $database
  ): array {
    $result = [
      'database' => $database,
      'connection' => 'FAIL',
      'authentication' => 'FAIL',
      'database_accessible' => false,
      'select_1' => 'FAIL',
      'server_version' => null,
      'tables_discovered' => 0,
      'relevant_tables' => [],
      'error' => null,
    ];

    try {
      $pdo = $this->connect($host, $port, $username, $password, $database);
      if (! $pdo) {
        $result['error'] = 'Unable to open PDO connection.';
        return $result;
      }

      $result['connection'] = 'PASS';
      $result['authentication'] = 'PASS';

      $pdo->query('SELECT 1');
      $result['select_1'] = 'PASS';

      $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
      if ((string) $dbName === $database) {
        $result['database_accessible'] = true;
      } else {
        $result['error'] = 'Connected but DATABASE() mismatch.';
      }

      $result['server_version'] = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

      $stmt = $pdo->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME'
      );
      $stmt->execute([$database]);
      $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
      $result['tables_discovered'] = count($tables);
      $result['relevant_tables'] = array_values(array_intersect($tables, array_merge(
        self::PERMISSION_TABLES,
        ['employees', 'users', 'outlets']
      )));

      $this->safeClose($pdo);
    } catch (PDOException $e) {
      $result['error'] = $this->sanitizeError($e->getMessage());
      $msg = strtolower($e->getMessage());
      if (str_contains($msg, 'access denied')) {
        $result['authentication'] = 'FAIL';
        $result['connection'] = 'FAIL';
      } elseif (str_contains($msg, 'unknown database')) {
        $result['connection'] = 'PASS';
        $result['authentication'] = 'PASS';
        $result['database_accessible'] = false;
        $result['error'] = 'Database does not exist or is not visible to this user.';
      }
    } catch (Throwable $e) {
      $result['error'] = $this->sanitizeError($e->getMessage());
    }

    return $result;
  }

  private function connect(
    string $host,
    int $port,
    string $username,
    string $password,
    string $database
  ): ?PDO {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

    $pdo = new PDO($dsn, $username, $password, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_TIMEOUT => 8,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Force read-only session intent where supported (best-effort; not a security boundary).
    try {
      $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    } catch (Throwable) {
      // Ignore if not supported.
    }

    return $pdo;
  }

  /**
   * @return array<string, mixed>
   */
  private function inspectEmployees(PDO $pdo): array
  {
    $out = [
      'table_exists' => false,
      'primary_key' => null,
      'columns' => [],
      'user_columns' => [],
      'row_count' => null,
      'users_row_count' => null,
      'eid_to_employees_id' => [
        'users_eid_exists' => false,
        'employees_id_exists' => false,
        'orphan_eid_count' => null,
        'status' => 'FAIL',
        'detail' => '',
      ],
      'checks' => [],
    ];

    if (! $this->tableExists($pdo, 'hr_db', 'employees')) {
      $out['checks'][] = 'employees.id exists: FAIL';
      return $out;
    }

    $out['table_exists'] = true;
    $cols = $this->describeColumns($pdo, 'hr_db', 'employees');
    $out['columns'] = $cols;
    $out['primary_key'] = $this->primaryKey($pdo, 'hr_db', 'employees');
    $out['row_count'] = $this->safeCount($pdo, 'employees');

    $out['checks'][] = 'employees.id exists: ' . (isset($cols['id']) ? 'PASS' : 'FAIL');
    $out['checks'][] = 'employees.Employee_Id exists: ' . (isset($cols['Employee_Id']) ? 'PASS' : 'FAIL');

    if ($this->tableExists($pdo, 'hr_db', 'users')) {
      $userCols = $this->describeColumns($pdo, 'hr_db', 'users');
      $out['user_columns'] = $userCols;
      $out['users_row_count'] = $this->safeCount($pdo, 'users');
      $out['checks'][] = 'users.EID exists: ' . (isset($userCols['EID']) ? 'PASS' : 'FAIL');

      $out['eid_to_employees_id']['users_eid_exists'] = isset($userCols['EID']);
      $out['eid_to_employees_id']['employees_id_exists'] = isset($cols['id']);

      if (isset($userCols['EID'], $cols['id'])) {
        try {
          $orphan = (int) $pdo->query(
            'SELECT COUNT(*) FROM users u
                         LEFT JOIN employees e ON e.id = u.EID
                         WHERE u.EID IS NOT NULL AND e.id IS NULL'
          )->fetchColumn();
          $out['eid_to_employees_id']['orphan_eid_count'] = $orphan;
          $out['eid_to_employees_id']['status'] = $orphan === 0 ? 'PASS' : 'WARN';
          $out['eid_to_employees_id']['detail'] = $orphan === 0
            ? 'All non-null users.EID values resolve to employees.id'
            : "{$orphan} users.EID value(s) do not resolve to employees.id";
          $out['checks'][] = 'users.EID → employees.id compatibility: ' .
            $out['eid_to_employees_id']['status'];
        } catch (Throwable $e) {
          $out['eid_to_employees_id']['status'] = 'FAIL';
          $out['eid_to_employees_id']['detail'] = $this->sanitizeError($e->getMessage());
          $out['checks'][] = 'users.EID → employees.id compatibility: FAIL';
        }
      } else {
        $out['checks'][] = 'users.EID → employees.id compatibility: FAIL';
      }
    } else {
      $out['checks'][] = 'users.EID exists: FAIL';
      $out['checks'][] = 'users.EID → employees.id compatibility: FAIL';
    }

    return $out;
  }

  /**
   * @return array<string, mixed>
   */
  private function inspectOutlets(PDO $pdo): array
  {
    $out = [
      'table_exists' => false,
      'primary_key' => null,
      'columns' => [],
      'row_count' => null,
      'active_row_count' => null,
      'checks' => [],
    ];

    if (! $this->tableExists($pdo, 'abnb_ds_nbsmt', 'outlets')) {
      $out['checks'][] = 'outlets table exists: FAIL';
      return $out;
    }

    $out['table_exists'] = true;
    $cols = $this->describeColumns($pdo, 'abnb_ds_nbsmt', 'outlets');
    $out['columns'] = $cols;
    $out['primary_key'] = $this->primaryKey($pdo, 'abnb_ds_nbsmt', 'outlets');
    $out['row_count'] = $this->safeCount($pdo, 'outlets');

    if (isset($cols['active_1'], $cols['deleted_at'])) {
      try {
        $out['active_row_count'] = (int) $pdo->query(
          'SELECT COUNT(*) FROM outlets WHERE active_1 = 1 AND deleted_at IS NULL'
        )->fetchColumn();
      } catch (Throwable) {
        $out['active_row_count'] = null;
      }
    }

    foreach (self::OUTLET_COLUMNS as $col) {
      $out['checks'][] = "outlets.{$col} exists: " . (isset($cols[$col]) ? 'PASS' : 'FAIL');
    }

    return $out;
  }

  /**
   * @return array<string, mixed>
   */
  private function inspectPermissions(PDO $pdo, string $schema): array
  {
    $out = [
      'schema' => $schema,
      'tables' => [],
      'outlet_visited_pages' => 'NOT FOUND — expected',
      'known_mobile_permission_ids' => [],
    ];

    foreach (self::PERMISSION_TABLES as $table) {
      $exists = $this->tableExists($pdo, $schema, $table);
      $info = [
        'exists' => $exists,
        'row_count' => null,
        'columns' => [],
      ];
      if ($exists) {
        $info['columns'] = array_keys($this->describeColumns($pdo, $schema, $table));
        $info['row_count'] = $this->safeCount($pdo, $table);
      }
      $out['tables'][$table] = $info;
    }

    if ($schema === 'hr_db' && ($out['tables']['navigations']['exists'] ?? false)) {
      try {
        $stmt = $pdo->query(
          "SELECT id, name FROM navigations
                     WHERE LOWER(name) LIKE '%outlet%visit%'
                        OR LOWER(name) LIKE '%visit%outlet%'
                        OR LOWER(name) LIKE '%outlet visited%'
                     ORDER BY id"
        );
        $rows = $stmt->fetchAll();
        if ($rows) {
          $out['outlet_visited_pages'] = array_map(function ($r) {
            return ['id' => $r['id'], 'name' => $r['name']];
          }, $rows);
        }

        $knownIds = [15, 28, 29, 32, 47, 48, 50, 66, 67];
        $in = implode(',', $knownIds);
        $known = $pdo->query("SELECT id, name FROM navigations WHERE id IN ({$in}) ORDER BY id")->fetchAll();
        $out['known_mobile_permission_ids'] = $known;
      } catch (Throwable $e) {
        $out['outlet_visited_pages'] = 'ERROR: ' . $this->sanitizeError($e->getMessage());
      }
    }

    return $out;
  }

  /**
   * @param  list<string>  $tables
   * @return array<string, mixed>
   */
  private function fingerprintTables(PDO $pdo, string $schema, array $tables): array
  {
    $fp = [];
    foreach ($tables as $table) {
      if (! $this->tableExists($pdo, $schema, $table)) {
        $fp[$table] = ['exists' => false];
        continue;
      }
      $cols = $this->describeColumns($pdo, $schema, $table);
      $normalized = [];
      foreach ($cols as $name => $meta) {
        $normalized[$name] = [
          'type' => $meta['type'] ?? null,
          'nullable' => $meta['nullable'] ?? null,
          'key' => $meta['key'] ?? null,
          'default' => $meta['default'] ?? null,
        ];
      }
      ksort($normalized);
      $fp[$table] = [
        'exists' => true,
        'primary_key' => $this->primaryKey($pdo, $schema, $table),
        'columns' => $normalized,
        'fingerprint' => substr(hash('sha256', json_encode($normalized)), 0, 16),
      ];
    }

    return $fp;
  }

  private function tableExists(PDO $pdo, string $schema, string $table): bool
  {
    $stmt = $pdo->prepare(
      'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $stmt->execute([$schema, $table]);

    return (int) $stmt->fetchColumn() > 0;
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private function describeColumns(PDO $pdo, string $schema, string $table): array
  {
    $stmt = $pdo->prepare(
      'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([$schema, $table]);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $row) {
      $out[$row['COLUMN_NAME']] = [
        'type' => $row['COLUMN_TYPE'],
        'nullable' => $row['IS_NULLABLE'] === 'YES',
        'key' => $row['COLUMN_KEY'],
        'default' => $row['COLUMN_DEFAULT'],
        'extra' => $row['EXTRA'],
      ];
    }

    return $out;
  }

  private function primaryKey(PDO $pdo, string $schema, string $table): ?string
  {
    $stmt = $pdo->prepare(
      "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
             ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute([$schema, $table]);
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $cols ? implode(',', $cols) : null;
  }

  private function safeCount(PDO $pdo, string $table): ?int
  {
    try {
      // Table names come only from our allowlists / information_schema.
      return (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $table) . '`')->fetchColumn();
    } catch (Throwable) {
      return null;
    }
  }

  /**
   * @param  array<string, mixed>  $report
   * @return array<string, string>
   */
  private function evaluateReadiness(array $report): array
  {
    $ready = [];

    $hr = $report['databases']['hr_db'] ?? [];
    $emp = $report['employee_structure'] ?? [];
    $ready['hr_db'] = (
      ($hr['database_accessible'] ?? false)
      && ($emp['table_exists'] ?? false)
      && isset($emp['columns']['id'], $emp['columns']['Employee_Id'])
      && (($emp['eid_to_employees_id']['status'] ?? '') === 'PASS'
        || ($emp['eid_to_employees_id']['status'] ?? '') === 'WARN')
    ) ? 'READY' : 'BLOCKED';

    $admin = $report['databases']['artisan_admin'] ?? [];
    $ready['artisan_admin'] = ($admin['database_accessible'] ?? false) ? 'READY' : 'BLOCKED';

    $outletDb = $report['databases']['abnb_ds_nbsmt'] ?? [];
    $outlet = $report['outlet_structure'] ?? [];
    $ready['abnb_ds_nbsmt'] = (
      ($outletDb['database_accessible'] ?? false)
      && ($outlet['table_exists'] ?? false)
      && isset($outlet['columns']['id'], $outlet['columns']['code'], $outlet['columns']['name'])
    ) ? 'READY' : 'BLOCKED';

    return $ready;
  }

  /**
   * @param  array<string, mixed>  $local
   * @param  array<string, mixed>  $online
   * @param  list<array<string, mixed>>  $differences
   * @return array{verdict:string,reasons:list<string>}
   */
  private function recommendation(array $local, array $online, array $differences): array
  {
    $reasons = [];
    foreach (self::DATABASES as $db) {
      if (($online['readiness'][$db] ?? 'BLOCKED') !== 'READY') {
        $reasons[] = "Online {$db} is BLOCKED.";
      }
    }

    foreach ($differences as $diff) {
      if ($diff['status'] === '❌ MISSING') {
        $reasons[] = "Missing online: {$diff['item']}";
      }
    }

    if ($reasons === []) {
      return [
        'verdict' => 'SAFE TO PROCEED (schema verification only — business questions still open)',
        'reasons' => [
          'Online databases accessible and required columns match for checked items.',
          'Still resolve open business questions before migrations.',
          'Do not invent permission page IDs.',
        ],
      ];
    }

    return [
      'verdict' => 'BLOCKED — FIX THESE ITEMS FIRST',
      'reasons' => $reasons,
    ];
  }

  /**
   * @param  array<string, mixed>|null  $localCol
   * @param  array<string, mixed>|null  $onlineCol
   * @return array{item:string,local:string,online:string,status:string}
   */
  private function compareColumnMeta(string $name, ?array $localCol, ?array $onlineCol): array
  {
    $localText = $localCol ? ($localCol['type'] ?? 'exists') : 'MISSING';
    $onlineText = $onlineCol ? ($onlineCol['type'] ?? 'exists') : 'MISSING';

    if ($localCol === null && $onlineCol === null) {
      $status = '❓ UNKNOWN';
    } elseif ($localCol === null) {
      $status = '⚠️ DIFFERENCE';
    } elseif ($onlineCol === null) {
      $status = '❌ MISSING';
    } elseif (($localCol['type'] ?? null) === ($onlineCol['type'] ?? null)) {
      $status = '✅ MATCH';
    } else {
      $status = '⚠️ DIFFERENCE';
    }

    return [
      'item' => $name,
      'local' => $localText,
      'online' => $onlineText,
      'status' => $status,
    ];
  }

  private function statusMatch(bool $local, bool $online, bool $expectTrue): string
  {
    if (! $online && $expectTrue) {
      return '❌ MISSING';
    }
    if ($local === $online) {
      return '✅ MATCH';
    }

    return '⚠️ DIFFERENCE';
  }

  private function statusText(?string $local, ?string $online): string
  {
    if ($local === null || $online === null) {
      return '❓ UNKNOWN';
    }
    if ($local === $online) {
      return '✅ MATCH';
    }

    return '⚠️ DIFFERENCE';
  }

  private function countStatus(mixed $local, mixed $online): string
  {
    if ($local === null || $online === null) {
      return '❓ UNKNOWN';
    }
    if ((int) $local === (int) $online) {
      return '✅ MATCH';
    }

    return '⚠️ DIFFERENCE';
  }

  /**
   * @param  list<string>  $lines
   * @param  array<string, mixed>  $db
   */
  private function appendDbBlock(array &$lines, string $label, array $db): void
  {
    $lines[] = "{$label}:";
    $lines[] = 'Connection: ' . ($db['connection'] ?? 'UNKNOWN');
    $lines[] = 'Authentication: ' . ($db['authentication'] ?? 'UNKNOWN');
    $lines[] = 'Access: ' . ((($db['database_accessible'] ?? false) === true) ? 'PASS' : 'FAIL');
    $lines[] = 'Status: ' . ((($db['database_accessible'] ?? false) === true) ? 'PASS' : 'FAIL');
    if (! empty($db['server_version'])) {
      $lines[] = 'Server: ' . $db['server_version'];
    }
    if (! empty($db['error'])) {
      $lines[] = 'Error: ' . $db['error'];
    }
  }

  /**
   * @param  array<string, mixed>  $emp
   */
  private function formatEmployeeBlock(array $emp): string
  {
    if ($emp === []) {
      return '(not scanned)';
    }

    $lines = [
      '- table_exists: ' . (($emp['table_exists'] ?? false) ? 'yes' : 'no'),
      '- primary_key: ' . ($emp['primary_key'] ?? 'UNKNOWN'),
      '- row_count: ' . ($emp['row_count'] ?? 'UNKNOWN'),
      '- eid_compatibility: ' . ($emp['eid_to_employees_id']['status'] ?? 'UNKNOWN'),
    ];
    foreach (($emp['checks'] ?? []) as $check) {
      $lines[] = '- ' . $check;
    }

    return implode("\n", $lines);
  }

  /**
   * @param  array<string, mixed>  $outlet
   */
  private function formatOutletBlock(array $outlet): string
  {
    if ($outlet === []) {
      return '(not scanned)';
    }

    $lines = [
      '- table_exists: ' . (($outlet['table_exists'] ?? false) ? 'yes' : 'no'),
      '- primary_key: ' . ($outlet['primary_key'] ?? 'UNKNOWN'),
      '- row_count: ' . ($outlet['row_count'] ?? 'UNKNOWN'),
      '- active_row_count: ' . ($outlet['active_row_count'] ?? 'UNKNOWN'),
    ];
    foreach (['id', 'code', 'name', 'address', 'latitude', 'longitude', 'active_1', 'deleted_at'] as $col) {
      $meta = $outlet['columns'][$col] ?? null;
      $lines[] = '- ' . $col . ': ' . ($meta ? $meta['type'] : 'MISSING');
    }

    return implode("\n", $lines);
  }

  /**
   * @param  array<string, mixed>  $perm
   */
  private function formatPermissionBlock(array $perm): string
  {
    if ($perm === []) {
      return '(not scanned)';
    }

    $lines = [];
    foreach ($perm as $schema => $data) {
      $lines[] = "[{$schema}]";
      foreach (($data['tables'] ?? []) as $table => $info) {
        if (! ($info['exists'] ?? false)) {
          continue;
        }
        $lines[] = "- {$table}: exists, rows=" . ($info['row_count'] ?? '?');
      }
      if (isset($data['outlet_visited_pages'])) {
        $ov = $data['outlet_visited_pages'];
        $lines[] = '- outlet_visited pages: ' . (is_array($ov) ? json_encode($ov) : $ov);
      }
    }

    return implode("\n", $lines);
  }

  /**
   * @param  array<string, mixed>  $comparison
   */
  private function statusForItem(array $comparison, string $needle): string
  {
    foreach ($comparison['items'] as $item) {
      if ($item['item'] === $needle || str_contains($item['item'], $needle)) {
        return $item['status'];
      }
    }

    return '❓ UNKNOWN';
  }

  private function maskHost(string $host): string
  {
    if ($host === '') {
      return '';
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
      $parts = explode('.', $host);
      if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.*.*';
      }
    }
    $len = strlen($host);
    if ($len <= 4) {
      return str_repeat('*', $len);
    }

    return substr($host, 0, 2) . str_repeat('*', max(3, $len - 4)) . substr($host, -2);
  }

  private function maskUsername(string $username): string
  {
    $len = strlen($username);
    if ($len <= 2) {
      return str_repeat('*', $len);
    }

    return substr($username, 0, 1) . str_repeat('*', max(2, $len - 2)) . substr($username, -1);
  }

  private function sanitizeError(string $message): string
  {
    // Strip potential credential fragments if drivers echo DSNs.
    $message = preg_replace('/password=[^;\s]+/i', 'password=***', $message) ?? $message;
    $message = preg_replace('/pwd=[^;\s]+/i', 'pwd=***', $message) ?? $message;

    return mb_substr($message, 0, 300);
  }

  private function safeClose(?PDO $pdo): void
  {
    $pdo = null;
  }
}
