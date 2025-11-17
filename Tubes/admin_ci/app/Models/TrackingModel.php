<?php

namespace App\Models;

use CodeIgniter\Model;

class TrackingModel extends Model
{
    protected $table = 'order_tracking';
    protected $primaryKey = 'tracking_id';
    
    // Kita biarkan saja, meskipun tidak terpakai untuk insert
    protected $allowedFields = ['order_id', 'status', 'description', 'timestamp'];
    
    protected $useTimestamps = true; 
    protected $createdField  = 'timestamp'; 
    protected $updatedField  = ''; 

    /**
     * Mengambil semua riwayat tracking untuk 1 order, diurutkan
     */
    public function getHistory($order_id)
    {
        return $this->where('order_id', $order_id)
                    ->orderBy('timestamp', 'ASC')
                    ->findAll();
    }

    /* // Fungsi addStatus() DIHAPUS 
    // karena admin tidak boleh menambah status baru
    */
}