<?= $this->extend('layout/main') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/select2.min.css')?>">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Data Kategori</h5>
        <div>
            <a href="<?= site_url('category/exportPdf') ?>?category=" id="btnExportPdf" class="btn btn-success btn-sm me-2" target="_blank">
                Export PDF
            </a>
            <button class="btn btn-primary btn-sm me-2" onclick="openForm('<?= site_url('category/form') ?>')">
                Tambah Kategori
            </button>
        </div>
    </div>
    <br>

    <table id="tblCategory" class="table table-bordered table-striped w-100">
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th>Nama Kategori</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Form Kategori</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('js') ?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="<?= base_url('assets/js/select2.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    const exportPdfBaseUrl = "<?= site_url('category/exportPdf') ?>";
    let tbl;
    $(function () {
        tbl = $('#tblCategory').DataTable({
            processing: true,
            serverSide: true,
            language: {
                searchPlaceholder: 'Cari nama kategori...'
            },
            order: [1, 'asc'], //kenapa kotak nya ada 2 di product index
            ajax: {
                url: "<?= base_url('category/datatable') ?>",
                type: "POST",
                data: function(d) {
                    d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
                }
            },
            columns: [
                { data: 'no', orderable: false, searchable: false }, //data ini dapat darimana isinya
                { data: 'name', name: 'name' },
                { data: 'aksi', orderable: false, searchable: false }
            ],
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', xhr.responseText);
                alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
            }
        });
    });

function openForm(url){ //url dapat darimana?
    $.ajax({
        url: url,
        type: 'GET',
        success: function(res){
            try {
                let data;
                if (typeof res === 'string'){
                    data = JSON.parse(res);
                } else {
                    data = res;
                }

                $('#modalBody').html(data.view);
                $('#modalForm').modal('show');
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Terjadi kesalahan pada form');
            }
        },
        error: function(xhr, status, error){
            console.error('AJAX error:', xhr.responseText);
            alert('Gagal memuat form. Error: ' + error);
        }
    });
}

function editForm(id){ //id dapat darimana
    openForm('<?= site_url('category/form/') ?>' + id);
}

function deleteData(id){
    if (confirm("apakah anda yakin menghapus data ini?")) {
        $.ajax({
            url: '<?= site_url('category/delete/') ?>'+ id,
            type: 'POST',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function (res){
                try {
                    let data;
                    if(typeof res === 'string') {
                        data = JSON.parse(res);
                    } else {
                        data = res;
                    }

                    if (data.status === 'success') {
                        alert('kategori berhasil dihapus');
                        tbl.ajax.reload();
                    } else {
                        alert('Gagal menghapus kategori');
                    }
                } catch (e){
                    console.error('Error parsing response', e);
                    alert('terjadi kesalahan saat menghapus.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr.responseText);
                alert('Gagal menghapus kategori. Error: ' + error);
            }
        });
    }
}
$('#btnExportPdf').attr('href', url);
</script>
<?= $this->endSection(); ?>