<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\OrderModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $productModel = new ProductModel();
        $customerModel = new CustomerModel();
        $orderModel   = new OrderModel();

        $data = [
            'total_products'   => $productModel->countAll(),
            'total_customers'  => $customerModel->countAll(),
            'total_orders'     => $orderModel->countAll(),
        ];

        return view('admin/dashboard', $data);
    }
}
