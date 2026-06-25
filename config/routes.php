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
    // Lead masters
    $r->add('GET',  '/lead-masters',          'LeadController@masters',     ['perm' => 'masters.manage']);
    $r->add('POST', '/lead-masters',          'LeadController@saveMaster',  ['perm' => 'masters.manage']);
    // AJAX
    $r->add('GET',  '/api/tehsils',           'LeadController@apiTehsils');

    // Customers
    $r->add('GET',  '/customers',                 'CustomerController@index',         ['perm' => 'customers.view']);
    $r->add('GET',  '/customers/create',          'CustomerController@create',        ['perm' => 'customers.create']);
    $r->add('POST', '/customers',                 'CustomerController@store',         ['perm' => 'customers.create']);
    $r->add('GET',  '/customers/{id}',            'CustomerController@show',          ['perm' => 'customers.view']);
    $r->add('POST', '/customers/{id}',            'CustomerController@update',        ['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/documents',  'CustomerController@uploadDoc',     ['perm' => 'customers.edit']);
    $r->add('POST', '/customers/{id}/services',   'CustomerController@assignServices',['perm' => 'customers.edit']);
    $r->add('GET',  '/documents/{id}',            'CustomerController@download',      ['perm' => 'customers.view']);

    // Service jobs (config-driven, any service)
    $r->add('GET',  '/customers/{cid}/jobs/{service}/create', 'ServiceJobController@create', ['perm' => 'services.edit']);
    $r->add('POST', '/customers/{cid}/jobs/{service}',        'ServiceJobController@store',  ['perm' => 'services.edit']);
    $r->add('GET',  '/jobs/{id}/edit',                        'ServiceJobController@edit',   ['perm' => 'services.edit']);
    $r->add('POST', '/jobs/{id}',                             'ServiceJobController@update', ['perm' => 'services.edit']);
    $r->add('POST', '/jobs/{id}/payments',                    'ServiceJobController@addPayment', ['perm' => 'payments.record']);
    $r->add('GET',  '/work-board',                            'ServiceJobController@board',  ['perm' => 'services.view']);

    // Reports
    $r->add('GET', '/reports',        'ReportController@index', ['perm' => 'reports.view']);
    $r->add('GET', '/reports/{type}', 'ReportController@show',  ['perm' => 'reports.view']);

    // Notifications
    $r->add('GET',  '/notifications',          'NotificationController@index');
    $r->add('POST', '/notifications/read-all', 'NotificationController@readAll');

    // Settings & masters
    $r->add('GET',  '/settings',         'SettingController@index', ['perm' => 'settings.manage']);
    $r->add('POST', '/settings',         'SettingController@save',  ['perm' => 'settings.manage']);
    $r->add('POST', '/masters/service',  'SettingController@saveService',    ['perm' => 'masters.manage']);
    $r->add('POST', '/masters/status',   'SettingController@saveFileStatus', ['perm' => 'masters.manage']);
};
