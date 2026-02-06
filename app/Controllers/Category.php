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
            return DataTable::of($builder)
                ->setSearchableColumns(false)
                ->filter(function ($builder, $request) use ($categoryModel) {
                    $search = $request->search['value'] ?? '';
                    $categoryModel->applySearch($builder, $search);
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
        }

        public function add(){
            $this->checkLogin();

            $db = \Config\Database::connect();
            $categoryModel = new CategoryModel();
            $category = $this->request->getPost('name');

            $data = [
                'name' => $category
            ];

            if (empty($data['name'])){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'data harus diisi!'
                ]);
            }
            $db->transBegin();

            $categoryModel->addData($data);

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

            $categoryModel = new CategoryModel();
            $category = $this->request->getPost('name');
            $data = [
                'name' => $category
            ];

            if (empty($data['name'])){
                return $this->response->setJSON([ 'status' => 'error', 'message' => 'data harus diisi!' ]);
            }

            $db->transBegin();

            $categoryModel->updateData($id, $data);

            if($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([ 'status' => 'error' ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([ 'status' => 'success' ]);
            }
        }

        public function delete($id) {
            $this->checkLogin();

            $db = \Config\Database::connect();
            $categoryModel = new CategoryModel();

            if ($categoryModel->isUsed($id)){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Kategori tidak dapat dihapus karena beberapa produk masih menggunakan kategori ini'
                ]);
            }

            $db->transBegin();

            $categoryModel->deleteData($id);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal menghapus kategori' 
                ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Kategori berhasil dihapus' 
                ]);
            }
        }

        public function forms ($id = ''){
            $this->checkLogin();

            $categoryModel = new CategoryModel();

            $form_type = empty($id) ? 'add' : 'edit';
            $row = [];
            $categoryid = '';

            if ($id != ''){
                $categoryid = $id;
                $row = $categoryModel->getOneCategory($id);
            }

            $view = view('category/form', [
                'form_type' => $form_type,
                'row' => $row,
                'categoryid' => $categoryid
            ]);

            return $this->response->setJSON([
                'view' => $view,
                'row' => $row,
                'form_type' => $form_type,
                'csrfToken' => csrf_hash()
            ]);
        }

        public function printPdf(){
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }

            $categoryModel = new CategoryModel();
            $category = $categoryModel->datatable()->get()->getResultArray();
            require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(43, 25, '', 1, 0, 'C');
            $pdf->Image('assets/upload/hyper_data.jpg', 21, 14, 20, 16);
            $pdf->Cell(67, 25, 'FORM LAPORAN DATA KATEGORI', 1, 0, 'C');

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(21, 6.3, 'Dokumen', 1, 0);
            $pdf->Cell(27, 6.3, '04.1-FRM-MKT', 1, 1);

            $pdf->SetX($x);
            $pdf->Cell(21, 6.3, 'Revisi', 1, 0);
            $pdf->Cell(27, 6.3, '001', 1, 1);

            $pdf->SetX($x);
            $pdf->Cell(21, 6.3, 'Tanggal Terbit', 1, 0);
            $pdf->Cell(27, 6.3, date('d F Y'), 1, 1);

            $pdf->SetX($x);
            $pdf->Cell(21, 6, 'Halaman', 1, 0);
            $pdf->Cell(27, 6, '1', 1, 1);

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetXY($x + 48, $y);
            $pdf->MultiCell(28, 3.1, "Disetujui oleh: \nManager Skariga", 1, 'C');

            $pdf->SetX($x + 48);
            $pdf->Cell(28, 12.7, '', 1, 1);
            $pdf->Image('assets/upload/tanda_tangan.png', $x + 52, $y + 8, 20, 10);
            
            $pdf->SetX($x + 48);
            $pdf->Cell(28, 6, 'Noval Abiansyah T.', 1, 0, 'C');
            $pdf->Ln(8);

            $pdf->SetFont('Arial', 'B', 13);
            $pdf->Cell(0, 10, 'Laporan Data Kategori', 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(35, 6, 'Nama Customer', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(35, 6, 'Mirza Adliansyah', 0, 1);

            $pdf->Cell(35, 6, 'Email', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(35, 6, 'mirza@gmail.com', 0, 1);

            $pdf->Cell(35, 6, 'Telp', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(35, 6, '088989282839', 0, 1);

            $pdf->Cell(35, 6, 'Alamat', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(35, 6, 'Jl. Sidomulyo, Batu', 0, 1);
            $pdf->Ln(5);

            $pdf->MultiCell(187, 5, "Deskripsi: \nInformasi data kategori", 1);
            $pdf->MultiCell(187, 5, "Laporan: \n...", 1);
            $pdf->Ln(5);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(50, 8, 'No', 1, 0, 'C');
            $pdf->Cell(137, 8, 'Nama Kategori', 1, 1, 'C');
            
            $pdf->SetFont('Arial', '', 10);
            $no = 1;
            foreach ($category as $row){
                $pdf->Cell(50, 8, $no++, 1, 0, 'C');
                $pdf->Cell(137, 8, $row['name'], 1, 1, 'C');
            }
            $pdf->Ln(5);
            $pdf->Cell(60, 6, 'Tangerang, '. date('d M Y'), 0, 1, 'C');
            $pdf->Cell(60, 6, 'Diterima Oleh:', 0, 0, 'C');

            $pdf->Ln(19);

            $pdf->Cell(60, 6, 'DZULHAQ REZA', 0, 0, 'C');
            
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="produk.pdf"')
                ->setBody($pdf->Output('S'));
        }
    }
?>