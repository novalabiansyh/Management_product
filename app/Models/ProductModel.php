<?php
    namespace App\Models;

    use CodeIgniter\Model;

    class ProductModel extends Model {
        protected $table = 'products';
        protected $primaryKey = 'id';
        protected $allowedFields = ['name', 'category_id', 'price', 'stock', 'created_at', 'created_by'];

        
        public function datatable($filter = [], $limit = null, $offset = null){
            $builder = $this->db->table('products p')
                                ->select('p.id as id, p.name as name, p.category_id as category_id, p.price as price, p.stock as stock, p.created_at AS created_at, c.name as category, u.username as created_by')
                                ->join('categories c', 'p.category_id = c.id')
                                ->join('users u', 'p.created_by = u.id', 'left');

            if (!empty($filter['category'])) {
                    $builder->where('p.category_id', $filter['category']);
            }

            if (!empty($filter['fromDate'])) {
                $builder->where('p.created_at >=', $filter['fromDate'] . ' 00:00:00');
            }

            if (!empty($filter['toDate'])) {
                $builder->where('p.created_at <=', $filter['toDate'] . ' 23:59:59');
            }

            if ($limit !== null && $offset !== null){
                $builder->limit($limit, $offset);
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
                    "u.username"
                ];
            }

        public function getOneWithCategory($id){
            return $this->select('products.*, categories.name as category_name')
                        ->join('categories', 'categories.id = products.category_id')
                        ->find($id);
        }

        public function getCategoryId($categoryName){
            return $this->db->table('categories')
                            ->select('id')
                            ->where('LOWER(TRIM(name))', strtolower(trim($categoryName)))
                            ->get()
                            ->getRowArray();
        }

        public function insertBatchData($data)
        {
            return $this->insertBatch($data);
        }

        public function isProductExist($name)
        {
            return $this->where('LOWER(TRIM(name))', strtolower(trim($name)))
                        ->countAllResults() > 0;
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