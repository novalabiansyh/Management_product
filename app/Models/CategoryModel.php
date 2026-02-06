<?php
    namespace App\Models;

    use CodeIgniter\Model;

    class CategoryModel extends Model {
        protected $table = 'categories';
        protected $primaryKey = 'id';
        protected $allowedFields = ['name'];

        public function datatable(){
            return $this->select('id, name');
        }

        public function applySearch($builder, $search){
            if (empty($search)){
                return $builder;
            }
            
            $builder->groupStart();
            foreach($this->allowedFields as $col){
                $builder->like($col, $search, 'both', null, true);
            }
            $builder->groupEnd();
            return $builder;
        }

        public function getForSelect(){
            return $this->select('id, name')
                        ->orderBy('name', 'ASC')
                        ->findAll();
        }

        public function findData($search = null){
            $builder = $this->select('id, name');
            
            if (!empty($search)){
                return $builder->like('name', $search, 'both', null, true)
                                ->orderBy('name', 'ASC')
                                ->findAll();
            }
            
            return $builder->orderBy('name', 'ASC')
                            ->findAll();
        }

        public function getOneCategory($categoryFilter){
            return $this->select('name')
                        ->where('id', $categoryFilter)
                        ->first();
        }

        public function isUsed($id){
            return $this->db->table('products')
                            ->where('category_id', $id)
                            ->countAllResults() > 0;
        }

        public function addData($data){
            $this->insert($data);
            return $this->getInsertID();
        }

        public function updateData($id, $data){
            return $this->update($id, $data);
        }

        public function deleteData($id){
            return $this->delete($id);
        }
    }
?>