<?php
use App\Core\Router;

return function (Router $r) {
    // Auth
    $r->add('GET',  '/login',  'AuthController@showLogin', ['auth' => false]);
    $r->add('POST', '/login',  'AuthController@login',     ['auth' => false]);
    $r->add('POST', '/logout', 'AuthController@logout');
    $r->add('GET',  '/password/change', 'AuthController@showChangePassword');
    $r->add('POST', '/password/change', 'AuthController@changePassword');

    // Dashboard
    $r->add('GET', '/', 'DashboardController@index');

    // Employees
    $r->add('GET',  '/employees',                 'EmployeeController@index',         ['perm' => 'employees.view']);
    $r->add('POST', '/employees',                 'EmployeeController@store',          ['perm' => 'employees.manage']);
    $r->add('POST', '/employees/{id}/update',     'EmployeeController@update',         ['perm' => 'employees.manage']);
    $r->add('POST', '/employees/{id}/status',     'EmployeeController@toggleStatus',   ['perm' => 'employees.manage']);
    $r->add('POST', '/employees/{id}/reset',      'EmployeeController@resetPassword',  ['perm' => 'employees.manage']);

    // Roles
    $r->add('GET',  '/roles',        'RoleController@index', ['perm' => 'roles.manage']);
    $r->add('POST', '/roles',        'RoleController@save',  ['perm' => 'roles.manage']);

    // Leads
    $r->add('GET',  '/leads',                 'LeadController@index',       ['perm' => 'leads.view']);
    $r->add('GET',  '/leads/create',          'LeadController@create',      ['perm' => 'leads.create']);
    $r->add('POST', '/leads',                 'LeadController@store',       ['perm' => 'leads.create']);
    $r->add('GET',  '/leads/{id}',            'LeadController@show',        ['perm' => 'leads.view']);
    $r->add('GET',  '/leads/{id}/edit',       'LeadController@edit',        ['perm' => 'leads.edit']);
    $r->add('POST', '/leads/{id}',            'LeadController@update',      ['perm' => 'leads.edit']);
    $r->add('POST', '/leads/{id}/activity',   'LeadController@addActivity', ['perm' => 'leads.edit']);
    $r->add('POST', '/leads/{id}/convert',    'LeadController@convert',     ['perm' => 'customers.create']);
    $r->add('POST', '/leads/{id}/delete',     'LeadController@destroy',     ['perm' => 'leads.edit']);
    // Lead masters
    $r->add('GET',  '/lead-masters',          'LeadController@masters',     ['perm' => 'masters.manage']);
    $r->add('POST', '/lead-masters',          'LeadController@saveMaster',  ['perm' => 'masters.manage']);
    // AJAX
    $r->add('GET',  '/api/tehsils',           'LeadController@apiTehsils');
    $r->add('GET',  '/api/search',            'SearchController@suggest');

    // Customers
    $r->add('GET',  '/customers',                 'CustomerController@index',         ['perm' => 'customers.view']);
    $r->add('GET',  '/customers/create',          'CustomerController@create',        ['perm' => 'customers.create']);
    $r->add('POST', '/customers',                 'CustomerController@store',         ['perm' => 'customers.create']);
    $r->add('GET',  '/customers/{id}',            'CustomerController@show',          ['perm' => 'customers.view']);
    $r->add('POST', '/customers/{id}',            'CustomerController@update',        ['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/activity',   'CustomerController@addActivity',   ['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/payments',   'CustomerController@addPayment',    ['perm' => 'payments.record']);
    $r->add('POST', '/customers/{id}/discounts',  'CustomerController@addDiscount',   ['perm' => 'payments.discount']);
    $r->add('POST', '/ledger/{id}/delete',        'CustomerController@deleteEntry',   ['perm' => 'payments.record']);
    $r->add('POST', '/customers/{id}/documents',  'CustomerController@uploadDoc',     ['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/services',   'CustomerController@assignServices',['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/services/{sid}/stop',   'CustomerController@stopService',   ['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/services/{sid}/resume', 'CustomerController@resumeService', ['perm' => 'customers.edit']);
    $r->add('GET',  '/documents/{id}',            'CustomerController@download',      ['perm' => 'customers.view']);

    // Service jobs (config-driven, any service)
    $r->add('GET',  '/customers/{cid}/jobs/{service}/create', 'ServiceJobController@create', ['perm' => 'services.edit']);
    $r->add('POST', '/customers/{cid}/jobs/{service}',        'ServiceJobController@store',  ['perm' => 'services.edit']);
    $r->add('GET',  '/jobs/{id}/edit',                        'ServiceJobController@edit',   ['perm' => 'services.edit']);
    $r->add('GET',  '/jobs/{id}/copy',                        'ServiceJobController@copy',   ['perm' => 'services.edit']);
    $r->add('POST', '/jobs/{id}',                             'ServiceJobController@update', ['perm' => 'services.edit']);
    $r->add('POST', '/jobs/{id}/delete',                      'ServiceJobController@destroy', ['perm' => 'services.edit']);
    $r->add('POST', '/payments/{id}/delete',                  'ServiceJobController@deletePayment', ['perm' => 'payments.record']);
    $r->add('GET',  '/work-board',                            'ServiceJobController@board',  ['perm' => 'services.view']);

    // Families (household grouping + split payments)
    $r->add('GET',  '/families',                      'FamilyController@index',         ['perm' => 'customers.view']);
    $r->add('POST', '/families/assign',               'FamilyController@assign',        ['perm' => 'customers.edit']);
    $r->add('GET',  '/families/{id}',                 'FamilyController@show',          ['perm' => 'customers.view']);
    $r->add('POST', '/families/{id}/roles',           'FamilyController@updateRoles',   ['perm' => 'customers.edit']);
    $r->add('POST', '/families/{id}/remove-member',   'FamilyController@removeMember',  ['perm' => 'customers.edit']);

    // Expenses & cash in hand (Admin + Manager)
    $r->add('GET',  '/expenses',                  'ExpenseController@index',       ['perm' => 'expenses.manage']);
    $r->add('POST', '/expenses',                  'ExpenseController@store',       ['perm' => 'expenses.manage']);
    $r->add('POST', '/expenses/opening',          'ExpenseController@setOpening',  ['perm' => 'expenses.manage']);
    $r->add('POST', '/expenses/cash',             'ExpenseController@addCash',     ['perm' => 'expenses.manage']);
    $r->add('POST', '/expenses/{id}/delete',      'ExpenseController@destroy',     ['perm' => 'expenses.manage']);
    $r->add('POST', '/cash-adjustments/{id}/delete', 'ExpenseController@destroyCash', ['perm' => 'expenses.manage']);

    // Employee attendance: Admin/Manager mark + see all; every employee sees their own (no permission needed to view own)
    $r->add('GET',  '/attendance',         'AttendanceController@index');
    $r->add('POST', '/attendance',         'AttendanceController@save',   ['perm' => 'attendance.manage']);
    $r->add('GET',  '/attendance/export',  'AttendanceController@export', ['perm' => 'attendance.manage']);

    // Invoices (Admin + Manager)
    $r->add('GET',  '/invoices',                    'InvoiceController@index',   ['perm' => 'invoices.manage']);
    $r->add('GET',  '/invoice-logo',                'InvoiceController@logo', ['auth' => false]);   // the firm logo is printed on the public copy too
    $r->add('GET',  '/i/{token}',                   'InvoiceController@publicShow', ['auth' => false]);
    $r->add('POST', '/invoices/{id}/reset-link',    'InvoiceController@resetLink', ['perm' => 'invoices.manage']);
    $r->add('GET',  '/invoices/{id}',               'InvoiceController@show',    ['perm' => 'invoices.manage']);
    $r->add('POST', '/invoices/{id}/delete',        'InvoiceController@destroy', ['perm' => 'invoices.manage']);
    $r->add('GET',  '/customers/{id}/invoices/new', 'InvoiceController@create',  ['perm' => 'invoices.manage']);
    $r->add('POST', '/customers/{id}/invoices',     'InvoiceController@store',   ['perm' => 'invoices.manage']);
    $r->add('GET',  '/families/{id}/invoices/new',  'InvoiceController@createGroup', ['perm' => 'invoices.manage']);
    $r->add('POST', '/families/{id}/invoices',      'InvoiceController@storeGroup',  ['perm' => 'invoices.manage']);

    // Reports
    $r->add('GET', '/reports',        'ReportController@index', ['perm' => 'reports.view']);
    $r->add('GET', '/reports/{type}',        'ReportController@show',   ['perm' => 'reports.view']);
    $r->add('GET', '/reports/{type}/export', 'ReportController@export', ['perm' => 'reports.view']);

    // Notifications
    $r->add('GET',  '/notifications',          'NotificationController@index');
    $r->add('POST', '/notifications/read-all', 'NotificationController@readAll');

    // Settings & masters
    $r->add('GET',  '/settings',         'SettingController@index', ['perm' => 'settings.manage']);
    $r->add('POST', '/settings',         'SettingController@save',  ['perm' => 'settings.manage']);
    $r->add('POST', '/settings/invoice', 'SettingController@saveInvoice', ['perm' => 'settings.manage']);
    $r->add('POST', '/masters/service',  'SettingController@saveService',    ['perm' => 'masters.manage']);
    $r->add('POST', '/masters/status',   'SettingController@saveFileStatus', ['perm' => 'masters.manage']);
};
