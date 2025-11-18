<?php

namespace App\Models;

use CodeIgniter\Model;

class StrukModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    protected $allowedFields = [
        'order_id',
        'metode',
        'jumlah_dibayar',
        'tanggal_bayar',
        'payment_proof',
        'payment_status'
    ];

    public function getAllPayments()
    {
        // === PERUBAHAN DI SINI ===
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat, customer.kota, customer.provinsi')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->orderBy('payments.tanggal_bayar', 'DESC')
                    ->findAll();
    }

    public function searchByCustomer($keyword)
    {
        // === PERUBAHAN DI SINI ===
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat, customer.kota, customer.provinsi')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->like('customer.nama', $keyword)
                    ->orderBy('payments.tanggal_bayar', 'DESC')
                    ->findAll();
    }

    public function getPaymentById($id)
    {
        // === PERUBAHAN DI SINI ===
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat, customer.kota, customer.provinsi')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->where('payment_id', $id)
                    ->first();
    }

    public function searchByOrderId($keyword)
    {
        // === PERUBAHAN DI SINI ===
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat, customer.kota, customer.provinsi')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->like('payments.order_id', $keyword)
                    ->orderBy('payments.tanggal_bayar', 'DESC')
                    ->findAll();
    }

    public function searchByStrukId($keyword)
    {
        // === PERUBAHAN DI SINI ===
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat, customer.kota, customer.provinsi')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->like('payments.payment_id', $keyword)
                    ->orderBy('payments.tanggal_bayar', 'DESC')
                    ->findAll();
    }
}