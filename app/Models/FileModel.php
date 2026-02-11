<?php
    namespace App\Models;
    use CodeIgniter\Model;

    class FileModel extends Model {
        protected $table = 'files';
        protected $primaryKey = 'fileid';
        protected $allowedFields = [
            'refid',
            'filename',
            'filerealname',
            'filedirectory',
            'created_date',
            'created_by',
            'update_date',
            'update_by',
            'isActive'
        ];

        public function datatable($filter = [], $limit = null, $offset = null)
        {
            $builder = $this->db->table('files f')
                ->select('f.fileid as fileid, f.refid as refid, f.filename as filename, f.filerealname as filerealname, f.filedirectory as filedirectory, f.created_date as created_date, u.username as created_by')
                ->join('users u', 'f.created_by = u.id', 'left')
                ->where('f.isactive', true);

            if (!empty($filter['refid'])) {
                $builder->where('f.refid', $filter['refid']);
            }

            if ($limit !== null && $offset !== null) {
                $builder->limit($limit, $offset);
            }

            return $builder;
        }

        public function applySearch($builder, $search)
        {
            if (empty($search)) {
                return $builder;
            }

            $builder->groupStart();
            foreach ($this->searchable() as $col) {
                $builder->orLike($col, $search, 'both', null, true);
            }
            $builder->groupEnd();
            return $builder;
        }

        public function searchable()
        {
            return [
                "f.filerealname",
                "f.filename",
                "u.username"
            ];
        }

        public function getFileById($id){
            return $this->find($id);
        }

        public function addFile($data)
        {
            return $this->insert($data);
        }

        public function deleteFile($fileId)
        {
            return $this->delete($fileId);
        }

    }
?>