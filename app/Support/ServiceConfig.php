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
                    ['form_type','TDS Form Type','select',['24Q'=>'24Q','26Q'=>'26Q'],['tds']],
                    ['financial_year','Financial Year','fy'],
                ],
            ],
            'gst' => [
                'label' => 'GST',
                'subtypes' => ['returns'=>'Returns','registration'=>'Registration','audit'=>'Audit'],
                'fields' => [
                    ['return_type','Return Type','select',['gstr1'=>'GSTR-1 (10th)','gstr3b'=>'GSTR-3B (20th)'],['returns']],
                    ['filing_type','Filing Type','select',['monthly'=>'Monthly','quarterly'=>'Quarterly'],['returns']],
                    ['period_label','Period','period',null,['returns']],
                    ['filing_date','Date of Filing','date',null,['returns']],
                    ['trn_number','TRN Number','text',null,['registration']],
                    ['arn_number','ARN Number','text',null,['registration']],
                    ['gst_user_id','GST User ID','text',null,['registration']],
                    ['reg_status','Status','select',['submitted'=>'Submitted','received'=>'GST Number Received'],['registration']],
                    ['audit_form_type','Form Type','select',['9'=>'GSTR-9','9_9c'=>'GSTR-9 & 9C'],['audit']],
                    ['financial_year','Financial Year','fy',null,['audit']],
                ],
            ],
            'accounting' => [
                'label' => 'Accounting',
                'fields' => [
                    ['frequency','Frequency','select',['monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly']],
                    ['financial_year','Financial Year','fy'],
                    ['period_label','Period','period'],
                ],
            ],
            'loan_subsidy' => [
                'label' => 'Loan & Subsidy',
                'subtypes' => ['udyam'=>'Udyam Registration','cma_report'=>'CMA Report','loan'=>'Loan'],
                'fields' => [
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
                    ['ucc_number','UCC Number','text'],
                ],
                'special' => 'investments',
            ],
            'insurance' => [
                'label' => 'Insurance',
                'fields' => [
                    ['referred_by','Referred By','text'],
                    ['company_name','Company Name','text'],
                    ['policy_type','Policy Type','select',['fresh'=>'Fresh','port'=>'Port']],
                    ['status_text','Status','text'],
                    ['claim_amount','Claim Amount (Claim Assist)','number'],
                ],
                'special' => 'insurance_types',
            ],
            'govt_dept' => [
                'label' => 'Government Department',
                'subtypes' => ['audit'=>'Audit','gst'=>'GST','tds'=>'TDS'],
                'fields' => [
                    ['officer_name','Officer Name','text'],
                    ['officer_contact','Officer Contact No.','text'],
                    ['contact_person','Contact Person','text'],
                    ['audit_type','Type of Audit','text',null,['audit']],
                    ['gst_form_type','GST Form Type','text',null,['gst']],
                    ['gst_user_id','GST User ID','text',null,['gst']],
                    ['tds_form_type','TDS Form Type','select',['24G'=>'24G','26Q'=>'26Q','24R'=>'24R'],['tds']],
                    ['tan_number','TAN Number','text',null,['tds']],
                    ['financial_year','Financial Year','fy'],
                ],
                'special' => 'credentials',
            ],
            'certificate' => [
                'label' => 'Certificate',
                'fields' => [
                    ['form_name','Form Name','text'],
                    ['certificate_type','Certificate Type','text'],
                ],
            ],
            'deeds_agreement' => [
                'label' => 'Deeds & Agreement',
                'fields' => [
                    ['deed_type','Type','text'],
                ],
            ],
            'company_compliance' => [
                'label' => 'Company Compliances',
                'subtypes' => ['audit'=>'Audit','company_registration'=>'Company Registration','compliances'=>'Compliances','other'=>'Other'],
                'fields' => [
                    ['form_name','Form Name','text',null,['audit','compliances','other']],
                    ['cs_number','CS Number','text',null,['company_registration']],
                    ['cs_name','CS Name','text',null,['company_registration']],
                    ['cs_contact','CS Contact Number','text',null,['company_registration']],
                    ['financial_year','Financial Year','fy',null,['audit','compliances']],
                ],
            ],
        ];
    }

    public static function get(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }

    /** keys that map to real columns on service_jobs */
    public static function columnKeys(): array
    {
        return ['sub_type','title','financial_year','period_label','due_date',
                'filing_date','file_status_id','fees_amount','comment'];
    }
}
