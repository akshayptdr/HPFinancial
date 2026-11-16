<?php
namespace App\Support;

/**
 * Config-driven definition of each service's job form.
 * field: [key, label, type, options?, subtypes?, required?]
 *   type: text|number|date|select|textarea|fy|period
 *   subtypes: only show field when sub_type is in this list
 * Column fields (stored on service_jobs columns): sub_type,title,financial_year,
 *   period_label,due_date,filing_date,file_status_id,fees_amount,comment.
 *   Any other field key is stored in the JSON `data` column.
 */
class ServiceConfig
{
    public static function all(): array
    {
        return [
            'income_tax' => [
                'label' => 'Income Tax',
                'subtypes' => ['itr'=>'ITR','tds'=>'TDS','audit'=>'Audit','other'=>'Other'],
                'fields' => [
                    ['title','Title (for Other)','text',null,['other']],
                    ['form_type','TDS Form Type','select',['24Q'=>'24Q','26Q'=>'26Q','both'=>'Both (24Q & 26Q)'],['tds']],
                    ['quarter','Financial Quarter','select',['Q1'=>'Q1 (Apr–Jun)','Q2'=>'Q2 (Jul–Sep)','Q3'=>'Q3 (Oct–Dec)','Q4'=>'Q4 (Jan–Mar)'],['tds']],
                    ['form_24g','Form 24G Applicable','select',['no'=>'No','yes'=>'Yes'],['tds']],
                    ['financial_year','Financial Year','fy'],
                ],
            ],
            'gst' => [
                'label' => 'GST',
                'subtypes' => ['registration'=>'Registration','returns'=>'Returns','audit'=>'Audit','other'=>'Other'],
                'fields' => [
                    ['title','Title (for Other)','text',null,['other']],
                    ['return_type','Return Type','select',['gstr1_3b'=>'GSTR-1 & GSTR-3B','composite'=>'Composite (CMP-08)','gstr7'=>'GSTR-7'],['returns']],
                    ['filing_type','Filing Type','select',['monthly'=>'Monthly','quarterly'=>'Quarterly'],['returns']],
                    ['gst_portal_user_id','User ID','text',null,['returns']],
                    ['gst_portal_password','Password','text',null,['returns']],
                    ['financial_year','Financial Year','fy',null,['audit']],
                    ['filing_date','Date of Filing','date',null,['returns']],
                    ['trn_number','TRN Number','text',null,['registration']],
                    ['arn_number','ARN Number','text',null,['registration']],
                    ['gst_user_id','GST User ID','text',null,['registration']],
                    ['gst_reg_password','Password','text',null,['registration']],
                    ['gst_number_received','GST Number Received','select',['yes'=>'Yes','no'=>'No'],['registration']],
                    ['reg_status','Status','select',['submitted'=>'Submitted','received'=>'GST Number Received'],['registration']],
                    ['audit_form_type','Form Type','select',['9'=>'GSTR-9','9_9c'=>'GSTR-9 & 9C','4'=>'GSTR-4 (Composition)'],['audit']],
                ],
            ],
            'accounting' => [
                'label' => 'Accounting',
                'fields' => [
                    ['frequency','Frequency','select',['monthly'=>'Monthly','quarterly'=>'Quarterly']],
                    ['financial_year','Financial Year','fy'],
                ],
            ],
            'loan_subsidy' => [
                'label' => 'Loan & Subsidy',
                'subtypes' => ['udyam'=>'Udyam Registration','cma_report'=>'CMA Report','loan'=>'Loan'],
                'fields' => [
                    ['udyam_registration','Udyam Registration','text',null,['udyam']],
                    ['bank_name','Bank Name','text',null,['cma_report','loan']],
                    ['loan_amount','Loan Amount','number',null,['cma_report','loan']],
                    ['loan_officer_name','Loan Officer Name','text',null,['cma_report','loan']],
                    ['loan_officer_number','Loan Officer Number','text',null,['cma_report','loan']],
                ],
            ],
            'mutual_fund' => [
                'label' => 'Mutual Fund',
                'fields' => [
                    ['account_open','Account Open?','select',['yes'=>'Yes','no'=>'No']],
                    ['account_open_due','Account Open Due Date','date',null,null,false,'account_open=yes'],
                    ['account_open_status_id','Account Open Status','filestatus',null,null,false,'account_open=yes'],
                    ['ucc_number','UCC Number','text'],
                ],
                'special' => 'investments',
            ],
            'insurance' => [
                'label' => 'Insurance',
                'subtypes' => ['insurance'=>'Insurance','claim'=>'Claim'],
                'fields' => [
                    ['company_name','Company Name','text'],
                    ['policy_type','Policy Type','select',['fresh'=>'Fresh','port'=>'Port'],['insurance']],
                    ['claim_type','Insurance Type','select',['own'=>'Own','third_party'=>'Third Party'],['claim']],
                    ['claim_amount','Claim Amount (₹)','number',null,['claim']],
                ],
                'special' => 'insurance_types',
            ],
            'govt_dept' => [
                'label' => 'Government Department',
                'subtypes' => ['audit'=>'Audit','gst'=>'GST','tds'=>'TDS','other'=>'Other'],
                'fields' => [
                    ['officer_name','Officer Name','text'],
                    ['officer_contact','Officer Contact No.','text'],
                    ['contact_person','Contact Person','text'],
                    ['audit_type','Type of Audit','text',null,['audit']],
                    ['gst_form_type','GST Form Type','select',['gstr7'=>'GSTR-7','other'=>'Other'],['gst']],
                    ['gst_user_id','GST User ID','text',null,['gst']],
                    ['gst_password','Password','text',null,['gst']],
                    ['tds_form_type','TDS Form Type','select',['24G'=>'24G','26Q'=>'26Q','24Q'=>'24Q'],['tds']],
                    ['tan_number','TAN Number','text',null,['tds']],
                    ['financial_year','Financial Year','fy'],
                ],
                'special' => 'credentials',
            ],
            'certificate' => [
                'label' => 'Certificate & Other Audit',
                'subtypes' => ['certificate'=>'Certificate','audit'=>'Other Audit','internal_audit'=>'Internal Audit'],
                'fields' => [
                    ['form_name','Form Name','text',null,['certificate']],
                    ['certificate_type','Certificate Type','text',null,['certificate']],
                    ['filing_type','Filing Type','select',['monthly'=>'Monthly','quarterly'=>'Quarterly'],['internal_audit']],
                    ['financial_year','Financial Year','fy',null,['audit','internal_audit']],
                ],
            ],
            'llp_compliance' => [
                'label' => 'LLP Compliances',
                'subtypes' => ['llp_registration'=>'LLP Registration','annual_filing'=>'Annual Filing','other'=>'Other'],
                'fields' => [
                    ['llpin','LLPIN','text',null,['llp_registration','annual_filing']],
                    ['form8_status_id','Form 8 Status','filestatus',null,['annual_filing']],
                    ['cs_name','CS Name','text',null,['llp_registration','annual_filing']],
                    ['cs_number','CS Number','text',null,['llp_registration','annual_filing']],
                    ['financial_year','Financial Year','fy',null,['annual_filing']],
                ],
            ],
            'company_compliance' => [
                'label' => 'Company Compliances',
                'subtypes' => ['company_registration'=>'Company Registration','compliances'=>'Compliances','audit'=>'Audit','other'=>'Other'],
                'fields' => [
                    ['cs_name','CS Name','text',null,['company_registration','compliances']],
                    ['cs_number','CS Number','text',null,['compliances']],
                    ['cs_contact','CS Contact Number','text',null,['company_registration']],
                    ['financial_year','Financial Year','fy',null,['audit','compliances']],
                ],
            ],
        ];
    }

    /** TDS return due dates: [month-day, years after the FY start year]. Q4 uses the statutory 31 May. */
    public const TDS_DUE = [
        'Q1' => ['07-31', 0],   // Apr–Jun  -> 31 Jul
        'Q2' => ['10-31', 0],   // Jul–Sep  -> 31 Oct
        'Q3' => ['01-31', 1],   // Oct–Dec  -> 31 Jan (next calendar year)
        'Q4' => ['05-31', 1],   // Jan–Mar  -> 31 May (next calendar year)
    ];

    /**
     * GST return due days in the month AFTER the period ends (month, or quarter-end month when filing quarterly).
     * GSTR-1 & GSTR-3B: 10th and 20th (two due dates). Composite (CMP-08): 15th, quarterly only. GSTR-7: 10th.
     */
    public const GST_DUE_DAYS = ['gstr1_3b' => [10, 20], 'composite' => [15], 'gstr7' => [10]];
    /**
     * Yearly jobs that renew themselves (service => sub-types). Everything here is recreated for the next
     * financial year 2 months before its next due date, and keeps doing so. (Monthly / quarterly Accounting is
     * handled by generateAccounting().) One-time work (registrations, loans, certificates, "other") is deliberately absent.
     */
    public const YEARLY_RENEWAL = [
        'income_tax'         => ['itr', 'audit'],
        'gst'                => ['audit'],
        'llp_compliance'     => ['annual_filing'],
        'company_compliance' => ['audit'],
        'certificate'        => ['audit'],              // Certificate & Other Audit: only the Other Audit sub-type renews
    ];

    /** Return types that can only be filed one way: Composite (CMP-08) quarterly, GSTR-7 monthly. */
    public const GST_FIXED_FILING = ['composite' => 'quarterly', 'gstr7' => 'monthly'];
    /** Quarterly filers: GSTR-1 on the 10th and GSTR-3B on the 22nd of the month after the quarter ends. */
    public const GST_DUE_DAYS_QUARTERLY = ['gstr1_3b' => [10, 22]];
    public const GST_RETURN_LABELS = ['gstr1_3b' => 'GSTR-1 & 3B', 'composite' => 'Composite', 'gstr7' => 'GSTR-7'];

    public static function gstFyOfMonth(int $y, int $m): string
    {
        $s = $m >= 4 ? $y : $y - 1;
        return $s . '-' . str_pad(($s + 1) % 100, 2, '0', STR_PAD_LEFT);
    }

    /** Resolve a GST return period to ['end'=>'YYYY-MM' (last month covered), 'label', 'fy'] or null. */
    public static function gstPeriod(?string $filing, ?string $month, ?string $fy, ?string $quarter): ?array
    {
        if ($filing === 'monthly' && $month && preg_match('/^(\d{4})-(\d{2})$/', $month, $m) && (int)$m[2] >= 1 && (int)$m[2] <= 12) {
            $y = (int)$m[1]; $mo = (int)$m[2];
            return ['end' => sprintf('%04d-%02d', $y, $mo), 'label' => date('M Y', mktime(0, 0, 0, $mo, 1, $y)), 'fy' => self::gstFyOfMonth($y, $mo)];
        }
        if ($filing === 'quarterly' && $fy && $quarter && preg_match('/^(\d{4})-\d{2}$/', $fy, $m)) {
            $map = ['Q1' => [0, 6], 'Q2' => [0, 9], 'Q3' => [0, 12], 'Q4' => [1, 3]];
            if (!isset($map[$quarter])) return null;
            return ['end' => sprintf('%04d-%02d', (int)$m[1] + $map[$quarter][0], $map[$quarter][1]),
                    'label' => $quarter . ' · ' . $fy, 'fy' => $fy];
        }
        return null;
    }

    /** Period details from its last month: ['end','label','fy','month'|null,'quarter'|null]. */
    public static function gstPeriodFromEnd(string $filing, string $end): ?array
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $end, $m)) return null;
        $y = (int)$m[1]; $mo = (int)$m[2];
        $fy = self::gstFyOfMonth($y, $mo);
        if ($filing === 'quarterly') {
            $q = [6 => 'Q1', 9 => 'Q2', 12 => 'Q3', 3 => 'Q4'][$mo] ?? null;
            return $q ? ['end' => $end, 'label' => $q . ' · ' . $fy, 'fy' => $fy, 'month' => null, 'quarter' => $q] : null;
        }
        return ['end' => $end, 'label' => date('M Y', mktime(0, 0, 0, $mo, 1, $y)), 'fy' => $fy, 'month' => $end, 'quarter' => null];
    }

    /** Period implied by a due date typed by the user: the month before it (rounded down to a quarter-end when quarterly). */
    public static function gstEndFromDue(string $filing, string $due): ?string
    {
        $t = strtotime($due);
        if (!$t) return null;
        $y = (int)date('Y', $t); $m = (int)date('n', $t) - 1;
        if ($m < 1) { $m = 12; $y--; }
        if ($filing === 'quarterly') {
            while (!in_array($m, [3, 6, 9, 12], true)) { $m--; if ($m < 1) { $m = 12; $y--; } }
        }
        return sprintf('%04d-%02d', $y, $m);
    }

    /** Monthly / quarterly Accounting is due on this day of the month after the period (month or quarter) ends. */
    public const ACCOUNTING_DUE_DAY = 5;

    /** Internal Audit (monthly / quarterly) is due on this day of the month after the period ends. */
    public const INTERNAL_AUDIT_DUE_DAY = 10;

    /** Due date (YYYY-MM-DD) of an Accounting period that ends in $endMonth ('YYYY-MM'). */
    public static function accDueDate(string $endMonth, ?int $day = null): ?string
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $endMonth, $m)) return null;
        $y = (int)$m[1]; $mo = (int)$m[2] + 1;
        if ($mo > 12) { $mo = 1; $y++; }
        return sprintf('%04d-%02d-%02d', $y, $mo, $day ?? self::ACCOUNTING_DUE_DAY);
    }

    /** The Accounting period whose due date is the next one on or after today. */
    public static function accUpcomingEnd(string $filing, ?string $today = null, ?int $day = null): ?string
    {
        $t = new \DateTime($today ?? 'today');
        $d = new \DateTime($t->format('Y-m-01'));
        $d->modify('-4 months');
        for ($i = 0; $i < 12; $i++, $d->modify('+1 month')) {
            if ($filing === 'quarterly' && !in_array((int)$d->format('n'), [3, 6, 9, 12], true)) continue;
            $due = self::accDueDate($d->format('Y-m'), $day);
            if ($due && $due >= $t->format('Y-m-d')) return $d->format('Y-m');
        }
        return null;
    }

    /** The period whose (first) due date is the next one on or after today. */
    public static function gstUpcomingEnd(string $rt, string $filing, ?string $today = null): ?string
    {
        if (!isset(self::GST_DUE_DAYS[$rt])) return null;
        $t = new \DateTime($today ?? 'today');
        $d = new \DateTime($t->format('Y-m-01'));
        $d->modify('-4 months');
        for ($i = 0; $i < 12; $i++, $d->modify('+1 month')) {
            if ($filing === 'quarterly' && !in_array((int)$d->format('n'), [3, 6, 9, 12], true)) continue;
            $due = self::gstDueDates($rt, $d->format('Y-m'), $filing);
            if ($due && $due[0] >= $t->format('Y-m-d')) return $d->format('Y-m');
        }
        return null;
    }

    /** [dueDate1, dueDate2|null] for a return type and period-end month, or null. */
    public static function gstDueDates(?string $returnType, string $endMonth, ?string $filing = null): ?array
    {
        $days = ($filing === 'quarterly' ? (self::GST_DUE_DAYS_QUARTERLY[$returnType] ?? null) : null)
            ?? (self::GST_DUE_DAYS[$returnType] ?? null);
        if (!$days || !preg_match('/^(\d{4})-(\d{2})$/', $endMonth, $m)) return null;
        $y = (int)$m[1]; $mo = (int)$m[2] + 1;
        if ($mo > 12) { $mo = 1; $y++; }
        return [sprintf('%04d-%02d-%02d', $y, $mo, $days[0]), isset($days[1]) ? sprintf('%04d-%02d-%02d', $y, $mo, $days[1]) : null];
    }

    /** Due date for a TDS quarter of a financial year like "2026-27", or null if inputs are not valid. */
    public static function tdsDueDate(?string $fy, ?string $quarter): ?string
    {
        if (!$fy || !$quarter || !isset(self::TDS_DUE[$quarter]) || !preg_match('/^(\d{4})-\d{2}$/', $fy, $m)) return null;
        [$md, $plus] = self::TDS_DUE[$quarter];
        return ((int)$m[1] + $plus) . '-' . $md;
    }

    public static function get(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }

    /** keys that map to real columns on service_jobs */
    public static function columnKeys(): array
    {
        return ['sub_type','title','financial_year','period_label','due_date','due_date_2',
                'filing_date','file_status_id','fees_amount','comment'];
    }
}
