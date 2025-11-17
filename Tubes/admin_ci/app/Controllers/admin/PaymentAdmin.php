<?php

namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\PaymentModel;

class PaymentAdmin extends BaseController
{
    protected $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
    }

    public function index()
    {
        $data['payments'] = $this->paymentModel->getAllPayments();
        return view('admin/payments/index', $data);
    }

    public function search()
    {
        $keyword = $this->request->getGet('keyword');
        $data['keyword'] = $keyword;
        $data['payments'] = $this->paymentModel->searchByCustomer($keyword);
        return view('admin/payments/index', $data);
    }

    public function print($id)
    {
        $data['payment'] = $this->paymentModel->getPaymentById($id);
        return view('admin/payments/print', $data);
    }
}
