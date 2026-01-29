<?php
    namespace App\Controllers;

    use App\Models\CategoryModel;
    use Hermawan\DataTables\DataTable;
    use FPDF;

    class Category extends BaseController{
        public function index(){
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }
            return view('category/index',[
                'title' => 'Data Kategori'
            ]);
        }

        public function datatable(){
            $this->checkLogin();
            $categoryModel = new CategoryModel();

            $builder = $categoryModel->datatable();
            $search = $this->request->getPost('search')['value'] ?? '';
            $categoryModel->applySearch($builder, $search);
            return DataTable::of($builder)
                ->addNumbering('no', false)
                ->add('aksi', function ($row) {
                    return '
                        <button class="btn btn-warning btn-sm"
                                onclick="editForm(\'' . $row->id . '\')">Edit</button>
                        <button class="btn btn-danger btn-sm"
                                onclick="deleteData(\'' . $row->id . '\')">Hapus</button>
                    ';
                })
            ->toJson(true);
        }
    }
?>