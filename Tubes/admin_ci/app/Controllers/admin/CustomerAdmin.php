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
            'keyword'   => '', 
            'filter'    => 'nama' 
        ];

        return view('admin/customer/index', $data);
    }

    // === FUNGSI SEARCH BARU ===
    public function search()
    {
        $keyword = $this->request->getGet('keyword');
        $filter = $this->request->getGet('filter') ?? 'nama'; 

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
                case 'provinsi': 
                    $customers = $this->customerModel->searchByProvince($keyword);
                    break;
                case 'kota': 
                    $customers = $this->customerModel->searchByCity($keyword);
                    break;
                // V TAMBAHAN V
                case 'kecamatan': 
                    $customers = $this->customerModel->searchByKecamatan($keyword);
                    break;
                // ^ TAMBAHAN ^
                default: // case 'nama'
                    $customers = $this->customerModel->searchByName($keyword);
                    break;
            }
        } else {
            $customers = $this->customerModel->findAll();
        }

        $data = [
            'title'     => 'Daftar Customer',
            'customers' => $customers,
            'keyword'   => $keyword,
            'filter'    => $filter,
        ];

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