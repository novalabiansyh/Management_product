<?php
    namespace App\Models;

    use CodeIgniter\Model;

    class CategoryModel extends Model {
        protected $table = 'categories';
        protected $primaryKey = 'id';
        protected $allowedFields = ['name'];

        public function getForSelect(){
            return $this->select('id, name')
                        ->orderBy('name', 'ASC')
                        ->findAll();
        }

        public function searchCategory($search, $limit = 10){
            return $this->select('id, name')
                        ->like('name', $search, 'both', null, true)
                        ->orderBy('name', 'ASC')
                        ->findAll($limit);
        }
    }
?>