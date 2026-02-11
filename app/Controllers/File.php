<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FileModel;
use Hermawan\DataTables\DataTable;

class File extends BaseController
{
    protected $db;
    protected $fileModel;

    public function __construct()
    {
        $this->fileModel = new FileModel();
        $this->db = \Config\Database::connect();
    }

    public function datatable()
    {
        $this->checkLogin();

        $refid = $this->request->getPost('refid');

        try {
            $builder = $this->fileModel->datatable(['refid' => $refid]);

            return DataTable::of($builder)
                ->setSearchableColumns(false)
                ->edit('created_date', function ($row) {
                    if (empty($row->created_date)) {
                        return '-';
                    }
                    return date('d F Y H:i:s', strtotime($row->created_date));
                })
                ->filter(function ($builder, $request) {
                    if (!empty($request->search['value'])) {
                        $this->fileModel->applySearch(
                            $builder,
                            $request->search['value']
                        );
                    }
                })
                ->addNumbering('no', false)
                ->add('aksi', function ($row) {
                    return '
                        <a href="' . base_url($row->filedirectory . '/' . $row->filename) . '" target="_blank" class="btn btn-info btn-sm">Lihat</a>
                        <a href="' . site_url('files/download/' . $row->fileid) . '" class="btn btn-success btn-sm">Download</a>
                        <button class="btn btn-danger btn-sm" onclick="deleteFile(' . $row->fileid . ')">Hapus</button>
                    ';
                })
                ->toJson(true);

        } catch (\Exception $e) {
            log_message('error', 'DataTable Files Error: ' . $e->getMessage());

            return $this->response->setJSON([
                'draw' => $this->request->getPost('draw') ?? 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function upload(){
        $this->checkLogin();

        $refid = $this->request->getPost('refid');
        $file = $this->request->getFile('file');

        if (!$file->isValid()){
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $file->getErrorString()
            ]);
        }

        $newName = $file->getRandomName();

        $subdir = 'uploads/' . $newName; // contoh: uploads/2026/02/11
        $path   = FCPATH . $subdir;
        $file->move($path, $newName);

        $data = [
            'refid'         => $refid,
            'filename'      => $newName,
            'filerealname'  => $file->getClientName(),
            'filedirectory' => $subdir,
            'created_date'  => date('Y-m-d H:i:s'),
            'created_by'    => session()->get('id'),
            'isactive'      => true
        ];

        $this->db->transBegin();

        $this->fileModel->addFile($data);

        if ($this->db->transStatus() === false){
            $this->db->transRollback();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menyimpan file'
            ]);
        } else {
            $this->db->transCommit();
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'File berhasil diupload'
            ]);
        }
    }

    public function download($id)
    {
        $this->checkLogin();

        $file = $this->fileModel->getFileById($id);

        if (!$file) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'File tidak ditemukan'
            ]);
        }

        $path = FCPATH . $file['filedirectory'] . '/' . $file['filename'];

        if (!file_exists($path)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'File tidak ada di server'
            ]);
        }

        return $this->response->download($path, null);
    }

    public function delete($id){
        $this->checkLogin();

        $file = $this->fileModel->getFileById($id);

        if (!$file){
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'File tidak ditemukan'
            ]);
        }

        $path = FCPATH . $file['filedirectory'] . '/' . $file['filename'];

        $this->db->transBegin();

        $this->fileModel->deleteFile($id);

        if (file_exists($path)){
            unlink($path);
        }

        if ($this->db->transStatus() === false){
            $this->db->transRollback();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menghapus file'
            ]);
        } else {
            $this->db->transCommit();
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'File Berhasil dihapus'
            ]);
        }
    }
}