<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customer';

    protected $allowedFields = [
        'nama',
        'password',
        'email',
        'no_telepon',
        'provinsi', 
        'kota',     
        'kecamatan', // <-- TAMBAHAN
        'alamat',
        'last_login', 
        'last_reengagement_sent' 
    ];

    // === FUNGSI SEARCH BARU ===

    /**
     * Cari customer berdasarkan nama
     */
    public function searchByName($keyword)
    {
        return $this->like('nama', $keyword)
                    ->orderBy('nama', 'ASC')
                    ->findAll();
    }

    /**
     * Cari customer berdasarkan ID
     */
    public function searchById($keyword)
    {
        return $this->like('customer_id', $keyword)
                    ->orderBy('customer_id', 'ASC')
                    ->findAll();
    }

    /**
     * Cari customer berdasarkan Email
     */
    public function searchByEmail($keyword)
    {
        return $this->like('email', $keyword)
                    ->orderBy('email', 'ASC')
                    ->findAll();
    }

    /**
     * Cari customer berdasarkan No. Telepon
     */
    public function searchByPhone($keyword)
    {
        return $this->like('no_telepon', $keyword)
                    ->orderBy('no_telepon', 'ASC')
                    ->findAll();
    }
    /**
     * Cari customer berdasarkan Provinsi
     */
    public function searchByProvince($keyword)
    {
        return $this->like('provinsi', $keyword)
                    ->orderBy('provinsi', 'ASC')
                    ->findAll();
    }

    /**
     * Cari customer berdasarkan Kota
     */
    public function searchByCity($keyword)
    {
        return $this->like('kota', $keyword)
                    ->orderBy('kota', 'ASC')
                    ->findAll();
    }

    /**
     * Cari customer berdasarkan Kecamatan (BARU)
     */
    public function searchByKecamatan($keyword)
    {
        return $this->like('kecamatan', $keyword)
                    ->orderBy('kecamatan', 'ASC')
                    ->findAll();
    }
}