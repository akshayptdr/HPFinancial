<?php
namespace App\Models;
use App\Core\Model;
use App\Core\Auth;

class Family extends Model
{
    protected static string $table = 'families';

    /** Families list with member count and money rollup. Non-admins only see families with a customer assigned to them. */
    public static function listing(?int $mineUserId = null): array
    {
        $sql = "SELECT f.*,
            (SELECT COUNT(*) FROM customers c WHERE c.family_id = f.id)
              + (SELECT COUNT(*) FROM leads l WHERE l.family_id = f.id AND l.status <> 'won') AS member_count
            FROM families f WHERE 1=1";
        $p = [];
        if ($mineUserId) {
            $sql .= " AND f.id IN (SELECT family_id FROM customers WHERE assigned_to = ? AND family_id IS NOT NULL)";
            $p[] = $mineUserId;
        }
        $sql .= " ORDER BY f.name";
        $rows = self::query($sql, $p);
        foreach ($rows as &$r) {
            $r['summary'] = self::summary((int)$r['id']);
            $r['contact'] = self::contact($r);
            $r['payer']   = $r['payer_customer_id'] ? Customer::find($r['payer_customer_id']) : null;
        }
        return $rows;
    }

    public static function canView(array $family): bool
    {
        if (Auth::isAdmin()) return true;
        return (int) self::scalar("SELECT COUNT(*) FROM customers WHERE family_id = ? AND assigned_to = ?",
            [$family['id'], Auth::id()]) > 0;
    }

    /** Customers of the family with their own Billed / Received / Discount / Outstanding. */
    public static function customers(int $fid): array
    {
        $rows = self::query("SELECT c.* FROM customers c WHERE c.family_id = ? ORDER BY c.name", [$fid]);
        foreach ($rows as &$r) {
            $t = Customer::totals((int)$r['id']);
            $r['billed'] = $t['billed']; $r['received'] = $t['received'];
            $r['discount'] = $t['discount']; $r['outstanding'] = $t['outstanding'];
        }
        return $rows;
    }

    /** Leads in the family that have not yet become customers. */
    public static function leads(int $fid): array
    {
        return self::query("SELECT * FROM leads WHERE family_id = ? AND status <> 'won' ORDER BY name", [$fid]);
    }

    /** Family roll-up: what is still to collect is the sum of members' positive outstandings. */
    public static function summary(int $fid): array
    {
        $billed = $received = $discount = $outstanding = $advance = 0.0;
        foreach (self::customers($fid) as $c) {
            $billed += $c['billed']; $received += $c['received']; $discount += $c['discount'];
            if ($c['outstanding'] > 0) $outstanding += $c['outstanding']; else $advance += -$c['outstanding'];
        }
        return ['billed' => $billed, 'received' => $received, 'discount' => $discount,
                'outstanding' => $outstanding, 'advance' => $advance];
    }

    /** Resolved contact person: ['type','id','name','mobile'] or null. */
    public static function contact(array $family): ?array
    {
        if (empty($family['contact_type']) || empty($family['contact_id'])) return null;
        $row = $family['contact_type'] === 'lead' ? Lead::find($family['contact_id']) : Customer::find($family['contact_id']);
        if (!$row) return null;
        return ['type' => $family['contact_type'], 'id' => (int)$row['id'], 'name' => $row['name'], 'mobile' => $row['mobile']];
    }

    /** Everything a customer/lead page needs to render its family box. */
    public static function boxFor(string $type, array $member): array
    {
        $box = ['type' => $type, 'member' => $member, 'family' => null, 'families' => [], 'matches' => []];
        if (!empty($member['family_id'])) {
            $f = self::find($member['family_id']);
            if ($f) {
                $box['family']  = $f;
                $box['contact'] = self::contact($f);
                $box['payer']   = $f['payer_customer_id'] ? Customer::find($f['payer_customer_id']) : null;
                $box['summary'] = self::summary((int)$f['id']);
                return $box;
            }
        }
        $box['families'] = self::query("SELECT id, name FROM families ORDER BY name");
        $box['matches']  = self::matches($type, $member);
        return $box;
    }

    /** Possible relatives: same mobile / contact-person mobile / address as someone already in a family. */
    public static function matches(string $type, array $m): array
    {
        $mobiles = array_values(array_filter([$m['mobile'] ?? null, $m['contact_person_mobile'] ?? null]));
        $address = trim((string)($m['address'] ?? ''));
        if (!$mobiles && $address === '') return [];
        $out = [];
        foreach (['customers', 'leads'] as $t) {
            $conds = []; $p = [];
            if ($mobiles) {
                $in = implode(',', array_fill(0, count($mobiles), '?'));
                $conds[] = "x.mobile IN ($in)"; $conds[] = "x.contact_person_mobile IN ($in)";
                $p = array_merge($p, $mobiles, $mobiles);
            }
            if ($address !== '') { $conds[] = "x.address = ?"; $p[] = $address; }
            $sql = "SELECT DISTINCT f.id, f.name FROM $t x JOIN families f ON f.id = x.family_id
                    WHERE x.family_id IS NOT NULL AND (" . implode(' OR ', $conds) . ")";
            if ($t === ($type === 'customer' ? 'customers' : 'leads')) { $sql .= " AND x.id <> ?"; $p[] = $m['id']; }
            foreach (self::query($sql, $p) as $r) $out[$r['id']] = $r;
        }
        return array_values($out);
    }

    public static function setMemberFamily(string $type, int $id, ?int $familyId): void
    {
        $table = $type === 'lead' ? 'leads' : 'customers';
        self::query("UPDATE $table SET family_id = ? WHERE id = ?", [$familyId, $id]);
    }
}
