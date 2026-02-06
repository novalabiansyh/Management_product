<form id="formCategory">

<?= csrf_field() ?>
<input type="hidden" id="form_type" value="<?= $form_type ?>">
<input type="hidden" id="categoryid" value="<?= $categoryid ?>">

<div class="mb-2">
    <label class="form-label">Nama Kategori</label>
    <input type="text" name="name" class="form-control"
        value="<?= $row['name'] ?? '' ?>" required>
</div>
<div class="text-end">
    <button type="button" class="btn btn-primary" onclick="submitCategory()">
        Simpan
    </button>
</div>

</form>

<script>
function submitCategory() {
    let formType = $('#form_type').val();
    let id = $('#categoryid').val();

    let url = (formType === 'add')
        ? '<?= site_url('category/add') ?>'
        : '<?= site_url('category/update') ?>/' + id;

    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: $('#formCategory').serialize(),
        success: function(res) {
            if (res.status === 'success') {
                $('#modalForm').modal('hide');
                $('#tblCategory').DataTable().ajax.reload(null, false);
            } else {
                alert('Gagal menyimpan data');
            }
        }
    });
}
</script>
