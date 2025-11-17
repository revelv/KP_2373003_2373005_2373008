<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CustomerModel;

class CustomerAdmin extends BaseController
{
    protected $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
    }

    // === READ (Tampilan Awal) ===
    public function index()
    {
        $data = [
            'title'     => 'Daftar Customer',
            'customers' => $this->customerModel->findAll(),
            'keyword'   => '', // Tambahkan ini
            'filter'    => 'nama' // Tambahkan ini (filter default)
        ];

        return view('admin/customer/index', $data);
    }

    // === FUNGSI SEARCH BARU ===
    public function search()
    {
        $keyword = $this->request->getGet('keyword');
        $filter = $this->request->getGet('filter') ?? 'nama'; // Default filter 'nama'

        if ($keyword) {
            switch ($filter) {
                case 'customer_id':
                    $customers = $this->customerModel->searchById($keyword);
                    break;
                case 'email':
                    $customers = $this->customerModel->searchByEmail($keyword);
                    break;
                case 'no_telepon':
                    $customers = $this->customerModel->searchByPhone($keyword);
                    break;
                default: // case 'nama'
                    $customers = $this->customerModel->searchByName($keyword);
                    break;
            }
        } else {
            // Jika keyword kosong, tampilkan semua
            $customers = $this->customerModel->findAll();
        }

        $data = [
            'title'     => 'Daftar Customer',
            'customers' => $customers,
            'keyword'   => $keyword,
            'filter'    => $filter,
        ];

        // Tampilkan hasil ke view index
        return view('admin/customer/index', $data);
    }


    // === DELETE (testing only) ===
    public function delete($id = null)
    {
        if ($id) {
            $this->customerModel->delete($id);
            session()->setFlashdata('success', 'Customer berhasil dihapus (testing only)');
        }
        return redirect()->to(base_url('admin/customer'));
    }
}