<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';
    protected $useAutoIncrement = false; 

    // V PENTING: TAMBAHKAN DUA FIELD INI V
    protected $allowedFields = [
        'product_id',
        'nama_produk',
        'deskripsi_produk',
        'harga',
        'stok',
        'link_gambar',
        'category_id',
        'weight', // <-- TAMBAHAN
        'status_jual' // <-- TAMBAHAN
    ];

    public function getProductsWithCategory()
    {
        return $this->select('products.*, category.category')
                    ->join('category', 'category.category_id = products.category_id', 'left')
                    ->findAll();
    }

    public function searchByName($keyword)
    {
        return $this->select('products.*, category.category')
                    ->join('category', 'category.category_id = products.category_id', 'left')
                    ->like('products.nama_produk', $keyword)
                    ->orderBy('nama_produk', 'ASC')
                    ->findAll();
    }

    public function searchById($keyword)
    {
        return $this->select('products.*, category.category')
                    ->join('category', 'category.category_id = products.category_id', 'left')
                    ->like('products.product_id', $keyword)
                    ->orderBy('products.product_id', 'ASC')
                    ->findAll();
    }

    public function searchByCategory($keyword)
    {
        return $this->select('products.*, category.category')
                    ->join('category', 'category.category_id = products.category_id', 'left')
                    ->like('category.category', $keyword)
                    ->orderBy('category.category', 'ASC')
                    ->findAll();
    }
}