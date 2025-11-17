<?php

namespace App\Models;
use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    protected $allowedFields = ['order_id', 'metode', 'jumlah_dibayar', 'tanggal_bayar', 'payment_proof', 'payment_status'];

    public function getAllPayments()
    {
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->orderBy('payments.tanggal_bayar', 'DESC')
                    ->findAll();
    }

    public function searchByCustomer($keyword)
    {
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->like('customer.nama', $keyword)
                    ->orderBy('payments.tanggal_bayar', 'DESC')
                    ->findAll();
    }

    public function getPaymentById($id)
    {
        return $this->select('payments.*, orders.tgl_order, customer.nama, customer.alamat')
                    ->join('orders', 'orders.order_id = payments.order_id')
                    ->join('customer', 'customer.customer_id = orders.customer_id')
                    ->where('payment_id', $id)
                    ->first();
    }
}
