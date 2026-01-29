<?php
    namespace App\Controllers;

    use App\Models\UserModel;

    class Login extends BaseController{
        public function login() {
            return view('auth/login');
        }
        
        public function processLogin() {
            $userModel = new UserModel();

            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            $user = $userModel->getData($username);

            if (!$user){
                return redirect()->back()->with('error', 'username tidak ditemukan');
            }

            if($password !== $user['password']){
                return redirect()->back()->with('error', 'password salah');
            }

            session()->set([
                'isLogin' => true,
                'id' => $user['id'],
                'username' => $user['username']
            ]);

            return redirect()->to('/products');
        }

        public function logout(){
            session()->destroy();
            return redirect()->to('/')->with('success', 'berhasil logout');
        }
    }
?>