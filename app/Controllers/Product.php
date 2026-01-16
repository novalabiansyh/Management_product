<?php
    namespace App\Controllers;

    use App\Models\ProductModel;
    use Hermawan\DataTables\DataTable;

    class Product extends BaseController{
        public function index(){
            $this->checkLogin();
            return view('product/index');
        }

        public function datatable()
        {
            $this->checkLogin();

            $categoryFilter = $this->request->getPost('categoryFilter');

            try {
                $model = new ProductModel();

                $builder = $model->datatable();

                if (!empty($categoryFilter)) {
                    $builder->where('p.category_id', $categoryFilter);
                }

                return DataTable::of($builder)
                    ->setSearchableColumns(false)
                    ->filter(function ($builder, $request) use ($model) {

                        $search = strtolower($request->search['value'] ?? '');

                        if (!empty($search)) {
                            $builder->groupStart();
                            foreach ($model->searchable() as $col) {
                                $builder->orWhere(
                                    "LOWER(CAST($col AS TEXT)) LIKE '%{$search}%'",
                                    null,
                                    false
                                );
                            }
                            $builder->groupEnd();
                        }
                    })
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

            } catch (\Exception $e) {

                log_message('error', 'DataTable Error: ' . $e->getMessage());

                return $this->response->setJSON([
                    'draw' => $this->request->getPost('draw') ?? 0,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => $e->getMessage()
                ]);
            }
        }

        public function add(){
            $this->checkLogin();
            $db = \Config\Database::connect();

            $model = new ProductModel();
            $name = $this->request->getPost('name');
            $category = $this->request->getPost('category_id');
            $price = $this->request->getPost('price');
            $stock = $this->request->getPost('stock');

            $data = [
                'name' => $name,
                'category_id' => $category,
                'price' => $price,
                'stock' => $stock
            ];

            if (empty($data['name']) || empty($data['category_id']) || empty($data['price']) || empty($data['stock'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Nama & kategori wajib diisi'
                ]);
            }

            $db->transBegin();

            $model->addData($data);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON(['status' => 'error']);
            } else {
                $db->transCommit();
                return $this->response->setJSON(['status' => 'success']);
            }
        }

        public function update($id){
            $this->checkLogin();
            $db = \Config\Database::connect();

            $model = new ProductModel();

            $name = $this->request->getPost('name');
            $category = $this->request->getPost('category_id');
            $price = $this->request->getPost('price');
            $stock = $this->request->getPost('stock');

            $data = [
                'name' => $name,
                'category_id' => $category,
                'price' => $price,
                'stock' => $stock
            ];

            $db->transBegin();

            $model->updateData($id, $data);

            if($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([
                    'status' => 'error'
                ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([
                    'status' => 'success'
                ]);
            }
        }

        public function delete($id){
            $this->checkLogin();

             $db = \Config\Database::connect();

            $model = new ProductModel();

            $db->transBegin();

            $model->deleteData($id);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON(['status' => 'error']);
            } else {
                $db->transCommit();
                return $this->response->setJSON(['status' => 'success']);
            }
        }

        public function forms($id = '')
        {
            $this->checkLogin();

            $model = new ProductModel();
            
            $form_type = empty($id) ? 'add' : 'edit';
            $row = [];
            $productid = '';

            if ($id != '') {
                $productid = $id;
                $row = $model->getOne($id);

                if (!$row) {
                    return $this->response->setJSON([
                        'error' => 'Data produk tidak ditemukan'
                    ]);
                }
            }

            $db = \Config\Database::connect();
            $categories = $db->table('categories')->get()->getResultArray();

            $view = view('product/form', [
                'form_type' => $form_type,
                'row' => $row,
                'categories' => $categories,
                'productid' => $productid
            ]);

            return $this->response->setJSON([
                'view' => $view,
                'form_type' => $form_type,
                'csrfToken' => csrf_hash()
            ]);
        }


    }
?>