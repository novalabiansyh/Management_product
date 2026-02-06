<?php
    namespace App\Controllers;

    use App\Models\ProductModel;
    use App\Models\CategoryModel;
    use Hermawan\DataTables\DataTable;
    use FPDF;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Reader\Xls;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    class Product extends BaseController{
        public function index(){
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }

            $categoryModel = new CategoryModel();

            return view('product/index', [
                'title' => 'Data Produk',
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

            if ($model->isProductExist($name)){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'nama produk sudah tersedia'
                ]);
            }

            $db->transBegin();

            $model->addData($data);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal menambah data' 
                ]);
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

            if ($model->isProductExist($name)){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'nama produk sudah tersedia'
                ]);
            }

            $db->transBegin();

            $model->updateData($id, $data);

            if ($db->transStatus() === false){
                $db->transRollback();
                return $this->response->setJSON([ 
                    'status' => 'error',
                    'message' => 'Gagal mengubah data'
                ]);
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
                return $this->response->setJSON([ 
                    'status' => 'error',
                    'message' => 'gagal menghapus data'
                ]);
            } else {
                $db->transCommit();
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Data Berhasil dihapus' 
                ]);
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
                $items = $categoryModel->findData($search);
            } else {
                $items = $categoryModel->findData();
            }

            $result = array_map(fn($c) => [
                'id' => $c['id'],
                'text' => $c['name']
            ], $items);

            return $this->response->setJSON([ 'items' => $result ]);
        }

        public function printPdf(){
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }
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

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(43, 25, '', 1, 0, 'C');  
            $pdf->Image('assets/upload/hyper_data.jpg', 20, 15, 21, 15);         
            $pdf->Cell(67, 25, 'FORM LAPORAN DATA PRODUK', 1, 0, 'C');

            $xRight = $pdf->GetX();
            $yTop   = $pdf->GetY();

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY($xRight, $yTop);
            $pdf->Cell(21, 6.3, 'Dokumen', 1, 1);
            $pdf->setX($xRight);
            $pdf->Cell(21, 6.3, 'Revisi', 1, 1);
            $pdf->setX($xRight);
            $pdf->Cell(21, 6.3, 'Tanggal Terbit', 1, 1);
            $pdf->setX($xRight);
            $pdf->Cell(21, 6.1, 'Halaman', 1, 0);

            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY($xRight + 21, $yTop);
            $pdf->Cell(29, 6.3, '04.1-FRM-MKT', 1, 1);
            $pdf->setX($xRight + 21);
            $pdf->Cell(29, 6.3, '001', 1, 1);
            $pdf->setX($xRight + 21);
            $pdf->Cell(29, 6.3, date('d F Y'), 1, 1);
            $pdf->setX($xRight + 21);
            $pdf->Cell(29, 6.1, '1', 1, 1);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY($xRight + 50, $yTop);
            $pdf->MultiCell(28, 3.1, "Disetujui oleh:\nManager Mutu", 1, 'C');

            $pdf->SetX($xRight + 50);
            $pdf->Cell(28, 12.8, '', 1, 1);

            $pdf->Image('assets/upload/tanda_tangan.png', $xRight + 55, $yTop + 8, 20, 10);

            $pdf->SetX($xRight + 50);
            $pdf->Cell(28, 6, 'Winna Oktavia P.', 1, 1, 'C');

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
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(0, 6, 'Noval Abiansyah', 0, 1);

            $pdf->Cell(35, 6, 'Email', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(0, 6, 'nopal@gmail.com', 0, 1);

            $pdf->Cell(35, 6, 'Telp', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(0, 6, '089531410074', 0, 1);

            $pdf->Cell(35, 6, 'Alamat', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(0, 6, 'Jl. Ciputat Raya, Kebayoran Lama, Jakarta Selatan', 0, 1);

            $pdf->Cell(35, 6, 'Kategori', 0, 0);
            $pdf->Cell(2, 6, ':', 0, 0);
            $pdf->Cell(0, 6, "$category", 0, 1);

            $pdf->Ln(5);

            $pdf->MultiCell(188, 5, "Deskripsi: \nMenampilkan Data Produk dengan kategori '$category'", 1);
            $pdf->MultiCell(188, 5, "Hasil Laporan: \nNew Data", 1);
            $pdf->Ln(5);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(10,8,'No',1, 0,'C');
            $pdf->Cell(60,8,'Nama Produk',1, 0, 'C');
            $pdf->Cell(60,8,'Kategori',1, 0, 'C');
            $pdf->Cell(38,8,'Harga',1, 0, 'C');
            $pdf->Cell(20,8,'Stok',1, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 10);
            $no = 1;
            foreach ($products as $p) {
                $pdf->Cell(10,8,$no++,1, 0, 'C');
                $pdf->Cell(60,8,$p['name'],1, 0, 'C');
                $pdf->Cell(60,8,$p['category'],1, 0, 'C');
                $pdf->Cell(38,8,number_format($p['price'],0, ',', '.'),1, 0, 'C');
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

        public function exportExcel()
        {
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }

            $rows = json_decode($this->request->getPost('rows'), true);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            /* ===== STYLE ===== */
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '4CAF50']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ];

            $dataStyle = [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ];

            /* ===== JUDUL ===== */
            $sheet->setCellValue('A1', 'DATA PRODUK');
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            /* ===== HEADER ===== */
            $sheet->setCellValue('A3', 'No');
            $sheet->setCellValue('B3', 'Nama Produk');
            $sheet->setCellValue('C3', 'Kategori');
            $sheet->setCellValue('D3', 'Price');
            $sheet->setCellValue('E3', 'Stok');
            $sheet->getStyle('A3:E3')->applyFromArray($headerStyle);

            /* ===== DATA ===== */
            $rowExcel = 4;
            $no = 1;

            foreach ($rows as $row) {
                $sheet->setCellValue("A$rowExcel", $no++);
                $sheet->setCellValue("B$rowExcel", $row['name']);
                $sheet->setCellValue("C$rowExcel", $row['category']);
                $sheet->setCellValue("D$rowExcel", $row['price']);
                $sheet->setCellValue("E$rowExcel", $row['stock']);

                $sheet->getStyle("D$rowExcel")->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');
                $sheet->getStyle("A$rowExcel:E$rowExcel")->applyFromArray($dataStyle);

                $rowExcel++;
            }

            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(10);

            $writer = new Xlsx($spreadsheet);
            $filename = 'Data_Produk.xlsx';

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment;filename="'.$filename.'"')
                ->setBody($writer->save('php://output'));
        }

        public function exportExcelChunk()
        {
            if ($redirect = $this->checkLogin()){
                return $redirect;
            }

            $limit = (int) $this->request->getGet('limit');
            $offset = (int) $this->request->getGet('offset');
            $category_id = $this->request->getGet('category');

            $model = new ProductModel();
            if(!empty($category_id)){
                $rows = $model->getData($limit, $offset, $category_id);
            } else {
                $rows = $model->getData($limit, $offset);
            }

            return $this->response->setJSON($rows);
        }

        public function exportExcelCount(){
            if($redirect = $this->checkLogin()){
                return $redirect;
            }

            $category_id = $this->request->getGet('category');
            $model = new ProductModel();

            if (!empty($category_id)){
                $total = $model->getDataCount($category_id);
            } else {
                $total = $model->getDataCount();
            }

            return $this->response->setJSON([
                'total' => $total
            ]);
        }

        public function import(){
            if ($this->request->getMethod() === 'GET'){
                return view('product/import_form');
            }

            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'File tidak valid'
                ]);
            }

            $ext = $file->getClientExtension();
            if (!in_array($ext, ['xlsx', 'xls'])){
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Format file tidak valid'
                ]);
            }

            $path = WRITEPATH . 'uploads/';
            $newName = $file->getRandomName();
            $file->move($path, $newName);

            $spreadsheet = IOFactory::load($path . $newName);
            $sheet = $spreadsheet->getActiveSheet()->toArray();

            unset($sheet[0]);
            $sheet = array_values($sheet);

            return $this->response->setJSON([
                'status' => 'success',
                'total' => count($sheet),
                'file' => $newName
            ]);
        }

        public function importChunk()
        {
            $file   = $this->request->getGet('file');
            $offset = (int) $this->request->getGet('offset');
            $limit  = (int) $this->request->getGet('limit');

            $path = WRITEPATH . 'uploads/' . $file;

            if (!file_exists($path)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'file tidak ditemukan'
                ]);
            }

            $spreadsheet = IOFactory::load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray();

            unset($rows[0]); // header
            $rows = array_values($rows);

            $chunk = array_slice($rows, $offset, $limit);

            $model = new ProductModel();
            $insertData = [];
            $success = 0;
            $failed = 0;

            foreach ($chunk as $row) {

                $name     = trim($row[1]); // Nama Produk
                $categoryName = trim(preg_replace('/\s+/u', ' ', $row[2])); // Kategori
                $price    = preg_replace('/[^0-9]/', '', $row[3]);
                $stock    = $row[4];

                $category = $model->getCategoryId($categoryName);

                if (!$category) {
                    $failed++;
                    continue;
                }

                $data = [
                    'name'        => $name,
                    'category_id'=> $category['id'],
                    'price'      => (float) $price,
                    'stock'      => (int) $stock,
                ];
                    
                if($model->isProductExist($name)){
                    $failed++;
                    continue;
                }

                if (empty($data['name']) || empty($data['category_id']) || !is_numeric($data['price']) || !is_numeric($data['stock'])){
                    $failed++;
                    continue;
                }

                $insertData[] = $data;
                $success++;
            }

            if (!empty($insertData)) {
                $model->insertBatchData($insertData);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'success' => $success,
                'failed'  => $failed,
                'rows' => $insertData
            ]);
        }
    }
?>