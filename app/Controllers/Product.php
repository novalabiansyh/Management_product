<?php
    namespace App\Controllers;

    use App\Models\ProductModel;
    use App\Models\CategoryModel;
    use Hermawan\DataTables\DataTable;
    use FPDF;

    class Product extends BaseController{
        public function index(){
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }

            $categoryModel = new CategoryModel();

            return view('product/index', [
                'categories' => $categoryModel->getForSelect()
            ]);
        }

        public function datatable()
        {
            $this->checkLogin();

            $categoryFilter = $this->request->getPost('categoryFilter'); //ambil filter category dari ajax view

            try {
                $model = new ProductModel();

                if (!empty($categoryFilter)){
                    $builder = $model->datatable('p.category_id', $categoryFilter);
                } else {
                    $builder = $model->datatable();
                }

                return DataTable::of($builder)
                    ->setSearchableColumns(false)
                    ->filter(function ($builder, $request) use ($model) {

                        $search = $request->search['value'] ?? '';

                        $model->applySearch($builder, $search);
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
                    'message' => 'data harus diisi!'
                ]);
            }

            $db->transBegin();

            $model->addData($data);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([ 'status' => 'error' ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([ 'status' => 'success' ]);
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

            if (empty($data['name']) || empty($data['category_id']) || empty($data['price']) || empty($data['stock'])){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'data harus diisi!'
                ]);
            }

            $db->transBegin();

            $model->updateData($id, $data);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([ 'status' => 'error' ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([ 'status' => 'success' ]);
            }
        }

        public function delete($id){
            $this->checkLogin();
            $db = \Config\Database::connect();

            $model = new ProductModel();
            $db->transBegin();

            $model->deleteData($id);

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON([ 'status' => 'error' ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([ 'status' => 'success' ]);
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
                $row = $model->getOneWithCategory($id);

                if (!$row) {
                    return $this->response->setJSON([
                        'error' => 'Data produk tidak ditemukan'
                    ]);
                }
            }

            $view = view('product/form', [
                'form_type' => $form_type,
                'row' => $row,
                'productid' => $productid
            ]);

            return $this->response->setJSON([
                'view' => $view,
                'row' => $row,
                'form_type' => $form_type,
                'csrfToken' => csrf_hash()
            ]);
        }

        public function categoryList(){
            $this->checkLogin();

            $search = $this->request->getPost('search');
            $categoryModel = new CategoryModel();

            if (!empty($search)){
                $items = $categoryModel->searchCategory($search);
            } else {
                $items = $categoryModel->findData();
            }

            $result = array_map(fn($c) => [
                'id' => $c['id'],
                'text' => $c['name']
            ], $items);

            return $this->response->setJSON([ 'items' => $result ]);
        }

        public function exportPdf(){
            $categoryFilter = $this->request->getGet('category'); //ambil filter kategory(dari url) kalo ada

            $model = new ProductModel();
            $categoryModel = new CategoryModel();
            if (!empty($categoryFilter)){
                $products = $model->datatable('p.category_id', $categoryFilter)->get()->getResultArray();
            } else {
                $products = $model->datatable()->get()->getResultArray();
            }

            require_once APPPATH.'ThirdParty/fpdf/fpdf.php';

            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 25, '', 1, 0, 'C');  
            $pdf->Image('upload/hyper_data.jpg', 20, 16, 20, 15);         
            $pdf->Cell(60, 25, 'FORM LAPORAN DATA PRODUK', 1, 0, 'C');

            $xRight = $pdf->GetX();
            $yTop   = $pdf->GetY();

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY($xRight, $yTop);
            $pdf->Cell(23, 6.3, 'Dokumen', 1, 0);
            $pdf->Cell(27, 6.3, '04.1-FRM-MKT', 1, 1);

            $pdf->SetX($xRight);
            $pdf->Cell(23, 6.3, 'Revisi', 1, 0);
            $pdf->Cell(27, 6.3, '001', 1, 1);

            $pdf->SetX($xRight);
            $pdf->Cell(23, 6.3, 'Tanggal Terbit', 1, 0);
            $pdf->Cell(27, 6.3, date('d M Y'), 1, 1);

            $pdf->SetX($xRight);
            $pdf->Cell(23, 6, 'Halaman', 1, 0);
            $pdf->Cell(27, 6, '1', 1, 1);

            $pdf->SetXY($xRight + 50, $yTop);
            $pdf->MultiCell(35, 3.1, "Disetujui oleh:\nManager Mutu", 1, 'C');

            $pdf->SetX($xRight + 50);
            $pdf->Cell(35, 12.8, '', 1, 1);

            $pdf->Image('upload/tanda_tangan.png', $xRight + 55, $yTop + 10, 22, 10);

            $pdf->SetX($xRight + 50);
            $pdf->Cell(35, 6, 'Winna Oktavia P.', 1, 1, 'C');

            $pdf->Ln(2);

            if (!empty($categoryFilter)){
                $categoryName = $categoryModel->getOneCategory($categoryFilter);
                $category = $categoryName['name'];
            } else {
                $category = "Semua Kategori";
            }

            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'Laporan Data Produk', 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(35, 6, 'Nama Customer', 0, 0);
            $pdf->Cell(5, 6, ':', 0, 0);
            $pdf->Cell(0, 6, '', 0, 1);

            $pdf->Cell(35, 6, 'Telepon', 0, 0);
            $pdf->Cell(5, 6, ':', 0, 0);
            $pdf->Cell(0, 6, '', 0, 1);

            $pdf->Cell(35, 6, 'Alamat', 0, 0);
            $pdf->Cell(5, 6, ':', 0, 0);
            $pdf->Cell(0, 6, '', 0, 1);

            $pdf->Cell(35, 6, 'Kategori', 0, 0);
            $pdf->Cell(5, 6, ':', 0, 0);
            $pdf->Cell(0, 6, "$category", 0, 1);

            $pdf->Cell(35, 6, 'Tanggal', 0, 0);
            $pdf->Cell(5, 6, ':', 0, 0);
            $pdf->Cell(0, 6, date('d M Y'), 0, 1);
            $pdf->Ln(5);

            $pdf->MultiCell(190, 5, "Deskripsi: \nLaporan Data Produk dengan kategori '$category'", 1);
            $pdf->MultiCell(190, 5, "Hasil Laporan: \nNew Data Produk", 1);
            $pdf->Ln(5);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(10,8,'No',1, 0,'C');
            $pdf->Cell(60,8,'Nama Produk',1, 0, 'C');
            $pdf->Cell(60,8,'Kategori',1, 0, 'C');
            $pdf->Cell(40,8,'Harga',1, 0, 'C');
            $pdf->Cell(20,8,'Stok',1, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 10);
            $no = 1;
            foreach ($products as $p) {
                $pdf->Cell(10,8,$no++,1, 0, 'C');
                $pdf->Cell(60,8,$p['name'],1, 0, 'C');
                $pdf->Cell(60,8,$p['category'],1, 0, 'C');
                $pdf->Cell(40,8,number_format($p['price'],0, ',', '.'),1, 0, 'C');
                $pdf->Cell(20,8,$p['stock'],1, 0, 'C');
                $pdf->Ln();
            }
            $pdf->Ln(5);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 6, 'Jakarta, 22 Januari 2026', 0, 1, 'C');

            $pdf->Cell(60, 6, 'Diterima oleh,', 0, 1, 'C');

            $pdf->Ln(15);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 6, 'DIAN MEDINA', 0, 1, 'C');

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="produk.pdf"')
                ->setBody($pdf->Output('S'));
        }
    }
?>