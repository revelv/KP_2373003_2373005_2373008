<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TrackingModel;
use App\Models\OrderModel; 

class TrackingController extends BaseController
{
    protected $trackingModel;
    protected $orderModel;

    public function __construct()
    {
        $this->trackingModel = new TrackingModel();
        $this->orderModel = new OrderModel(); 
    }

    // === Menampilkan Halaman Tracking (GET) ===
    // Disederhanakan menjadi read-only
    public function show($order_id)
    {
        $order = $this->orderModel->getOrderById($order_id);
        if (!$order) {
            return redirect()->to('admin/orders')->with('error', 'Order tidak ditemukan');
        }

        // Ambil riwayat tracking dari model
        $tracking_history = $this->trackingModel->getHistory($order_id);

        $data = [
            'title'             => 'Lacak Pesanan #' . $order_id,
            'order'             => $order,
            'tracking_history'  => $tracking_history
        ];

        // Arahkan ke View 'show' (File ini akan kita buat di langkah 3)
        return view('admin/tracking/show', $data); 
    }

    /* // Method update() DIHAPUS 
    // karena admin tidak boleh mengubah status tracking
    */
}