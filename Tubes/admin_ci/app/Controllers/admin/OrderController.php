<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class OrderController extends BaseController
{
    protected $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    public function index()
    {
        // Ubah bagian ini
        $data = [
            'orders'  => $this->orderModel->getAllOrders(),
            'keyword' => '',
            'filter'  => 'nama_customer' // Filter default
        ];
        return view('admin/orders/index', $data);
    }

    // === FUNGSI SEARCH BARU ===
    public function search()
    {
        $keyword = $this->request->getGet('keyword');
        $filter = $this->request->getGet('filter') ?? 'nama_customer';

        if ($keyword) {
            switch ($filter) {
                case 'order_id':
                    $orders = $this->orderModel->searchById($keyword);
                    break;
                case 'status':
                    $orders = $this->orderModel->searchByStatus($keyword);
                    break;
                default: // 'nama_customer'
                    $orders = $this->orderModel->searchByCustomerName($keyword);
                    break;
            }
        } else {
            $orders = $this->orderModel->getAllOrders();
        }

        $data = [
            'orders'  => $orders,
            'keyword' => $keyword,
            'filter'  => $filter
        ];

        return view('admin/orders/index', $data);
    }
    // === AKHIR FUNGSI SEARCH ===

    public function updateStatus($order_id)
    {
        $status = $this->request->getPost('status');
        if ($this->orderModel->updateStatus($order_id, $status)) {
            return redirect()->to('/admin/orders')->with('success', 'Status berhasil diperbarui!');
        }
        return redirect()->back()->with('error', 'Gagal memperbarui status.');
    }

    public function detail($order_id)
    {
        $data['order'] = $this->orderModel->getOrderById($order_id);
        return view('admin/orders/detail', $data);
    }
}