<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// ========================================================
// ADMIN AREA (Semua rute admin digabung di sini)
// ========================================================
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
    
    // --- Rute Auth (Tanpa Filter) ---
    $routes->get('login', 'LoginController::index');
    $routes->post('login', 'LoginController::process');
    $routes->get('logout', 'LoginController::logout');

    // --- Rute Terproteksi (Dengan Filter 'adminAuth') ---
    $routes->group('', ['filter' => 'adminAuth'], static function ($routes) {
        
        // Dashboard
        $routes->get('/', 'Dashboard::index'); // Ini akan menangani rute 'admin'

        // Products
        $routes->get('products', 'ProductAdmin::index');
        $routes->get('products/create', 'ProductAdmin::create');
        $routes->post('products/store', 'ProductAdmin::store');
        $routes->get('products/edit/(:segment)', 'ProductAdmin::edit/$1');
        $routes->post('products/update/(:segment)', 'ProductAdmin::update/$1');
        $routes->get('products/delete/(:segment)', 'ProductAdmin::delete/$1');
        $routes->get('products/search', 'ProductAdmin::search');

        // Customer
        $routes->get('customer', 'CustomerAdmin::index');
        $routes->get('customer/search', 'CustomerAdmin::search');
        $routes->get('customer/delete/(:num)', 'CustomerAdmin::delete/$1');

        // Orders
        $routes->get('orders', 'OrderController::index');
        $routes->post('orders/updateStatus/(:num)', 'OrderController::updateStatus/$1');
        $routes->get('orders/detail/(:num)', 'OrderController::detail/$1');

        // Struk
        $routes->get('struk', 'StrukAdmin::index');
        $routes->get('orders/search', 'OrderController::search');
        $routes->get('struk/search', 'StrukAdmin::search');
        $routes->get('struk/print/(:num)', 'StrukAdmin::print/$1');

        // Payment (Menggantikan 'paymentadmin')
        $routes->get('payment', 'PaymentAdmin::index');
        $routes->get('payment/search', 'PaymentAdmin::search');
        $routes->get('payment/print/(:num)', 'PaymentAdmin::print/$1');

        // --- Rute Tracking Baru ---
    $routes->post('tracking/update/(:num)', 'TrackingController::update/$1');
    $routes->get('tracking/show/(:any)', 'TrackingController::show/$1');
    });
});

// Catatan: Rute-rute di bawah ini sudah tidak diperlukan 
// karena telah digabung ke dalam grup 'admin' di atas.
//
// $routes->get('admin', 'Admin\Dashboard::index', ['filter' => 'adminAuth']);
// $routes->get('admin/login', 'Admin\LoginController::index');
// $routes->post('admin/login', 'Admin\LoginController::process');
// $routes->get('admin/logout', 'Admin\LoginController::logout');
// $routes->group('productadmin', ...);
// $routes->get('customeradmin', ...);
// $routes->group('paymentadmin', ...);
// $routes->group('admin', ...) // <-- semua grup admin yang terpisah-pisah