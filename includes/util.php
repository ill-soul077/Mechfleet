<?php
// includes/util.php
// Common helper functions (escaping, validation) reused by public pages.

if (!function_exists('e')) {
    function e(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Validate that a SQL string is safe for the demo runner: only SELECT or EXPLAIN
function validate_demo_sql(string $sql): array {
    // Normalize whitespace and remove comments (simple, not a full parser)
    $trimmed = trim($sql);
    // Disallow multiple statements via semicolons (except trailing whitespace/semicolon)
    if (preg_match('/;\s*\S/m', $trimmed)) {
        return [false, 'Only a single statement is allowed. Remove extra statements.'];
    }

    // Basic guard: must start with SELECT or EXPLAIN (case-insensitive)
    if (!preg_match('/^(SELECT|EXPLAIN)\b/i', $trimmed)) {
        return [false, 'Only SELECT or EXPLAIN statements are allowed on the demo page.'];
    }

    // Disallow suspicious keywords that change state
    $blocked = [
        'INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'ALTER', 'DROP', 'TRUNCATE', 'CREATE',
        'GRANT', 'REVOKE', 'LOCK', 'UNLOCK', 'SET PASSWORD', 'SHUTDOWN', 'KILL', 'USE',
        'CALL', 'HANDLER', 'LOAD', 'INSTALL', 'UNINSTALL', 'RESET', 'PURGE', 'BACKUP',
        'RESTORE', 'ANALYZE TABLE', 'OPTIMIZE TABLE', 'CHECK TABLE'
    ];
    $upper = strtoupper($trimmed);
    foreach ($blocked as $kw) {
        if (strpos($upper, $kw) !== false) {
            return [false, 'Disallowed keyword detected: ' . $kw];
        }
    }

    return [true, 'OK'];
}

// Parse user-provided params into an array for prepared statements.
// Expect format: one "key=value" per line; values treated as strings unless prefixed with :int: or :float: or :bool:
function parse_params(?string $raw): array {
    $params = [];
    if (!$raw) { return $params; }
    $lines = preg_split("/\r?\n/", $raw);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) { continue; }
        if (!str_contains($line, '=')) { continue; }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if ($k === '') { continue; }
        $type = PDO::PARAM_STR;
        if (str_starts_with($v, ':int:')) { $type = PDO::PARAM_INT; $v = substr($v, 5); }
        elseif (str_starts_with($v, ':float:')) { $type = PDO::PARAM_STR; $v = (string) ((float) substr($v, 7)); }
        elseif (str_starts_with($v, ':bool:')) { $type = PDO::PARAM_BOOL; $v = strtolower(substr($v, 6)) === 'true' ? 1 : 0; }
        $params[$k] = ['value' => $v, 'type' => $type];
    }
    return $params;
}
