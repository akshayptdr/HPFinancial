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

/** Public URL of a file under assets/. CSS and JS get ?v=<file time> so browsers fetch the new copy after an update. */
function asset(string $path): string
{
    $rel = 'assets/' . ltrim($path, '/');
    $url = url($rel);
    if (preg_match('/\.(css|js)$/', $rel)) {
        $file = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/' . $rel;
        if (is_file($file)) $url .= '?v=' . filemtime($file);
    }
    return $url;
}

function csrf_field(): string { return Csrf::field(); }

function old(string $key, $default = '')
{
    $old = Session::get('_old', []);
    return $old[$key] ?? $default;
}

function flash(string $type) { return Session::flash($type); }

function can(string $perm): bool { return Auth::can($perm); }

/** True for Admin and Manager: the only roles that may see fees, payments and money totals. */
function sees_money(): bool { return Auth::seesMoney(); }

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

/** Extra label for a job row: GST return type or Form 24G, e.g. " · GSTR-7". */
function job_kind_label(array $j): string
{
    if (($j['task'] ?? '') === 'account_open') return ' · ACCOUNT OPENING';
    if (($j['task'] ?? '') === 'llp_form8') return ' · FORM 8';
    if (($j['service_code'] ?? '') === 'llp_compliance' && ($j['sub_type'] ?? '') === 'annual_filing') return ' · FORM 11';
    if (!empty($j['auto_key']) && strpos($j['auto_key'], 'tds_24g') === 0) return ' · FORM 24G';
    if (($j['service_code'] ?? '') === 'certificate' && ($j['sub_type'] ?? '') === 'internal_audit' && !empty($j['data'])) {
        $f = (json_decode($j['data'], true) ?: [])['filing_type'] ?? null;
        if ($f) return ' · ' . ucfirst($f);
    }
    if (($j['service_code'] ?? '') === 'insurance' && !empty($j['renewed_from_job_id'])) return ' · RENEWAL';
    if (($j['service_code'] ?? '') === 'insurance' && ($j['sub_type'] ?? '') === 'claim' && !empty($j['data'])) {
        $t = (json_decode($j['data'], true) ?: [])['claim_type'] ?? null;
        $names = ['own' => 'Own', 'third_party' => 'Third Party'];
        if ($t && isset($names[$t])) return ' · ' . $names[$t];
    }
    if (($j['service_code'] ?? '') === 'accounting' && !empty($j['data'])) {
        $f = (json_decode($j['data'], true) ?: [])['frequency'] ?? null;
        if ($f) return ' · ' . ucfirst($f);
    }
    if (($j['service_code'] ?? '') === 'gst' && ($j['sub_type'] ?? '') === 'audit' && !empty($j['data'])) {
        $f = (json_decode($j['data'], true) ?: [])['audit_form_type'] ?? null;
        $names = ['9' => 'GSTR-9', '9_9c' => 'GSTR-9 & 9C', '4' => 'GSTR-4'];
        if ($f && isset($names[$f])) return ' · ' . $names[$f];
    }
    if (($j['sub_type'] ?? '') === 'returns' && !empty($j['data'])) {
        $d = json_decode($j['data'], true) ?: [];
        $rt = $d['return_type'] ?? null;
        $f = $d['filing_type'] ?? null;
        if ($rt && isset(\App\Support\ServiceConfig::GST_RETURN_LABELS[$rt])) {
            return ' · ' . \App\Support\ServiceConfig::GST_RETURN_LABELS[$rt] . ($f ? ' (' . ucfirst($f) . ')' : '');
        }
    }
    return '';
}

/** Human label for a payment mode: cash / upi / bank. */
function pay_mode_label(?string $m): string
{
    return ['cash' => 'Cash', 'upi' => 'UPI', 'bank' => 'Bank Transfer'][$m] ?? ($m ? ucfirst($m) : '—');
}

/** 1500000 -> "15,00,000" (Indian digit grouping); decimals only when needed. */
function indian_number($n): string
{
    $n = round((float)$n, 2);
    $neg = $n < 0; $n = abs($n);
    $int = (string) floor($n);
    $dec = ($n == floor($n)) ? '' : '.' . str_pad((string) round(($n - floor($n)) * 100), 2, '0', STR_PAD_LEFT);
    if (strlen($int) > 3) {
        $last3 = substr($int, -3); $rest = substr($int, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $int = $rest . ',' . $last3;
    }
    return ($neg ? '-' : '') . $int . $dec;
}

/** 15000 -> "Rupees Fifteen Thousand Only" (Indian system: lakh / crore). */
function amount_in_words($amount): string
{
    $amount = round((float)$amount, 2);
    $r = (int) floor($amount);
    $paise = (int) round(($amount - $r) * 100);
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen',
             'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $two = function (int $n) use ($ones, $tens): string {
        return $n < 20 ? $ones[$n] : trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
    };
    $three = function (int $n) use ($ones, $two): string {
        $s = '';
        if ($n >= 100) { $s = $ones[intdiv($n, 100)] . ' Hundred'; $n %= 100; if ($n) $s .= ' '; }
        return $s . ($n ? $two($n) : '');
    };
    $parts = [];
    if ($r === 0) { $parts[] = 'Zero'; }
    else {
        $crore = intdiv($r, 10000000); $lakh = intdiv($r % 10000000, 100000);
        $thousand = intdiv($r % 100000, 1000); $rest = $r % 1000;
        if ($crore)    $parts[] = $three($crore) . ' Crore';
        if ($lakh)     $parts[] = $two($lakh) . ' Lakh';
        if ($thousand) $parts[] = $two($thousand) . ' Thousand';
        if ($rest)     $parts[] = $three($rest);
    }
    return 'Rupees ' . implode(' ', $parts) . ($paise ? ' and ' . $two($paise) . ' Paise' : '') . ' Only';
}

function set_old(array $data): void { Session::set('_old', $data); }
function clear_old(): void { Session::forget('_old'); }
