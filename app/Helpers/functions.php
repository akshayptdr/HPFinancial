<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function base_url(): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($script, '/');
}

function url(string $path = '/'): string
{
    if (preg_match('#^https?://#', $path)) return $path;
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }

function csrf_field(): string { return Csrf::field(); }

function old(string $key, $default = '')
{
    $old = Session::get('_old', []);
    return $old[$key] ?? $default;
}

function flash(string $type) { return Session::flash($type); }

function can(string $perm): bool { return Auth::can($perm); }

function auth_user(): ?array { return Auth::user(); }

function active(string $page, string $current): string { return $page === $current ? 'active' : ''; }

function money($n): string
{
    $n = (float)$n;
    return '₹' . number_format($n, ($n == floor($n)) ? 0 : 2);
}

function inr_short($n): string
{
    $n = (float)$n;
    if ($n >= 10000000) return '₹' . rtrim(rtrim(number_format($n/10000000, 2), '0'), '.') . 'Cr';
    if ($n >= 100000)   return '₹' . rtrim(rtrim(number_format($n/100000, 2), '0'), '.') . 'L';
    if ($n >= 1000)     return '₹' . rtrim(rtrim(number_format($n/1000, 1), '0'), '.') . 'K';
    return '₹' . number_format($n, 0);
}

function date_h(?string $d): string
{
    if (!$d) return '—';
    $ts = strtotime($d);
    return $ts ? date('d M Y', $ts) : '—';
}

function days_until(?string $d): ?int
{
    if (!$d) return null;
    $ts = strtotime($d);
    if (!$ts) return null;
    return (int) floor(($ts - strtotime('today')) / 86400);
}

function due_pill(?string $d): string
{
    $n = days_until($d);
    if ($n === null) return '<span class="muted">—</span>';
    if ($n < 0)  return '<span class="pill pill--red">' . abs($n) . 'd overdue</span>';
    if ($n === 0) return '<span class="pill pill--red">Today</span>';
    if ($n <= 3) return '<span class="pill pill--amber">In ' . $n . 'd</span>';
    return '<span class="pill pill--blue">' . date_h($d) . '</span>';
}

function status_pill(?string $status): string
{
    $map = [
        'new' => 'blue', 'contacted' => 'sky', 'qualified' => 'amber',
        'won' => 'green', 'lost' => 'gray',
        'Pending' => 'gray', 'In-Progress' => 'amber', 'Filed' => 'blue', 'Completed' => 'green',
        'active' => 'green', 'inactive' => 'red',
    ];
    $c = $map[$status] ?? 'gray';
    return '<span class="pill pill--' . $c . '">' . e(ucfirst((string)$status)) . '</span>';
}

function initials(string $name): string
{
    $p = preg_split('/\s+/', trim($name));
    return strtoupper(substr($p[0] ?? '', 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}

function set_old(array $data): void { Session::set('_old', $data); }
function clear_old(): void { Session::forget('_old'); }
