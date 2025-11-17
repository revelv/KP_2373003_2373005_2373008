<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StrukModel;

class StrukAdmin extends BaseController
{
    protected $strukModel;

    public function __construct()
    {
        $this->strukModel = new StrukModel();
    }

    // === LIST STRUK ===
    public function index()
    {
        $data = [
            'title' => 'Riwayat Pembayaran',
            'payments' => $this->strukModel->getAllPayments(),
            'keyword' => ''
        ];
        return view('admin/struk/index', $data);
    }

public function search()
{
    $keyword = $this->request->getGet('keyword');
    $filter = $this->request->getGet('filter') ?? 'nama';

    $payments = [];

    if ($keyword) {
        switch ($filter) {
            case 'order_id':
                $payments = $this->strukModel->searchByOrderId($keyword);
                break;
            case 'payment_id':
                $payments = $this->strukModel->searchByStrukId($keyword);
                break;
            default:
                $payments = $this->strukModel->searchByCustomer($keyword);
                break;
        }
    } else {
        $payments = $this->strukModel->getAllPayments();
    }

    $data = [
        'title' => 'Pencarian Struk',
        'keyword' => $keyword,
        'filter' => $filter,
        'payments' => $payments,
    ];

    return view('admin/struk/index', $data);
}



    // === CETAK STRUK ===
    public function print($id)
    {
        $data = [
            'title' => 'Cetak Struk',
            'payment' => $this->strukModel->getPaymentById($id)
        ];
        return view('admin/struk/print', $data);
    }
}
