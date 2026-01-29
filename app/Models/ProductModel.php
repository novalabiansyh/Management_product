<?php
    namespace App\Models;

    use CodeIgniter\Model;

    class ProductModel extends Model {
        protected $table = 'products';
        protected $primaryKey = 'id';
        protected $allowedFields = ['name', 'category_id', 'price', 'stock'];

        
        public function datatable($field = null, $value = null){
            $builder = $this->db->table('products p')
                                ->select('p.id as id, p.name as name, p.category_id as category_id, p.price as price, p.stock as stock, c.name as category')
                                ->join('categories c', 'p.category_id = c.id');

            if ($field !== null && $value !== null) {
                    $builder->where($field, $value);
                }
                return $builder;
        }

        public function applySearch($builder, $search){
            if (empty($search)){
                return $builder;
            }

            $builder->groupStart(); //buka kurung
            foreach ($this->searchable() as $col){
                $builder->orLike($col, $search, 'both', null, true);
            }
            $builder->groupEnd(); //tutup kurung
            return $builder;
        }

        public function searchable(){
                return [
                    "p.name",
                    "c.name",
                ];
            }

        public function getOneWithCategory($id){
            return $this->select('products.*, categories.name as category_name')
                        ->join('categories', 'categories.id = products.category_id')
                        ->find($id);
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