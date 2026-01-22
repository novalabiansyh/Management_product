<form id="formProduct">

<?= csrf_field() ?>
<input type="hidden" id="form_type" value="<?= $form_type ?>">
<input type="hidden" id="productid" value="<?= $productid ?>">

<div class="mb-2">
    <label class="form-label">Nama Produk</label>
    <input type="text" name="name" class="form-control"
        value="<?= $row['name'] ?? '' ?>" required>
</div>

<div class="mb-2">
    <label class="form-label">Kategori</label>
    <select name="category_id" id="category_id" class="form-control" required></select>
</div>

<div class="mb-2">
    <label class="form-label">Harga</label>
    <input type="number" name="price" class="form-control"
        value="<?= $row['price'] ?? '' ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Stok</label>
    <input type="number" name="stock" class="form-control"
        value="<?= $row['stock'] ?? '' ?>" required>
</div>

<div class="text-end">
    <button type="button" class="btn btn-primary" onclick="submitProduct()">
        Simpan
    </button>
</div>

</form>

<script>
function submitProduct() {
    let formType = $('#form_type').val();
    let id = $('#productid').val();

    let url = (formType === 'add')
        ? '<?= site_url('products/add') ?>'
        : '<?= site_url('products/update') ?>/' + id;

    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: $('#formProduct').serialize(),
        success: function(res) {
            if (res.status === 'success') {
                $('#modalForm').modal('hide');
                $('#tblProduct').DataTable().ajax.reload(null, false);
            } else {
                alert('Gagal menyimpan data');
            }
        }
    });
}
</script>
