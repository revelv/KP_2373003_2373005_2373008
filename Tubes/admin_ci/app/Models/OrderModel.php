<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';

    // V PENTING: ID BUKAN ANGKA AUTO_INCREMENT LAGI V
    protected $useAutoIncrement = false;
    protected $returnType = 'array'; // Pastikan ini array

    // V PENTING: TAMBAHKAN SEMUA KOLOM BARU V
    protected $allowedFields = [
        'order_id', // Karena bukan auto-increment, harus ada di sini
        'customer_id', 
        'tgl_order', 
        'total_harga', 
        'status',
        'provinsi',       // <-- BARU
        'kota',           // <-- BARU
        'alamat',         // <-- BARU
        'code_courier',   // <-- BARU
        'ongkos_kirim'    // <-- BARU
    ];

    // Helper function untuk query dasar dengan join
    private function getBaseQuery()
    {
        // Ambil semua kolom dari orders (orders.*)
        return $this->select('orders.*, customer.nama as nama_customer, customer.email') 
                    ->join('customer', 'customer.customer_id = orders.customer_id', 'left');
    }

    public function getAllOrders()
    {
        return $this->getBaseQuery() 
                    ->orderBy('orders.tgl_order', 'DESC') // Urutkan berdasarkan tanggal
                    ->findAll();
    }

    public function getOrderById($id)
    {
        return $this->getBaseQuery() 
                    ->where('orders.order_id', $id)
                    ->first();
    }

    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    // === FUNGSI SEARCH BARU ===

    /**
     * Cari berdasarkan Order ID
     */
    public function searchById($keyword)
    {
        return $this->getBaseQuery()
                    ->like('orders.order_id', $keyword)
                    ->orderBy('orders.tgl_order', 'DESC')
                    ->findAll();
    }

    /**
     * Cari berdasarkan Nama Customer
     */
    public function searchByCustomerName($keyword)
    {
        return $this->getBaseQuery()
                    ->like('customer.nama', $keyword)
                    ->orderBy('orders.tgl_order', 'DESC')
                    ->findAll();
    }

    /**
     * Cari berdasarkan Status
     */
    public function searchByStatus($keyword)
    {
        return $this->getBaseQuery()
                    ->like('orders.status', $keyword)
                    ->orderBy('orders.tgl_order', 'DESC')
                    ->findAll();
    }
}