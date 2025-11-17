<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class LoginController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['form', 'url', 'session']);
    }

    // tampil halaman login
    public function index()
    {
        return view('admin/login');
    }

    // proses login
    public function process()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $builder = $this->db->table('admin');
        $builder->where('username', $username);
        $user = $builder->get()->getRow();

        if ($user && password_verify($password, $user->password)) {
            session()->set([
                'admin_id' => $user->admin_id,
                'admin_username' => $user->username,
                'is_admin_logged_in' => true
            ]);
            return redirect()->to('/admin');
        } else {
            return redirect()->back()->with('error', 'Username atau password salah!');
        }
    }

    // logout
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/admin');
    }
}
