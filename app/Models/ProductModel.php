<?php
    namespace App\Models;

    use CodeIgniter\Model;

    class ProductModel extends Model {
        protected $table = 'products';
        protected $primaryKey = 'id';
        protected $allowedFields = ['name', 'category_id', 'price', 'stock'];

        public function datatable(){
            return $this->db->table('products p')
                            ->select('p.*, c.name as category')
                            ->join('categories c', 'p.category_id = c.id');
        }

        public function getOne($id){
            return $this->find($id);
        }

        public function addData(array $data){
            $this->insert($data);
            return $this->getInsertID();
        }

        public function updateData($id, array $data){
            return $this->update($id, $data);
        }

        public function deleteData($id){
            return $this->delete($id);
        }
    }
?>