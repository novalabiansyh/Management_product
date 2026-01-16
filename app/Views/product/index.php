<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Produk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h5>Data Produk</h5>
        <button class="btn btn-primary"
            onclick="openForm('<?= site_url('products/form') ?>')">
            Tambah Produk
        </button>
    </div>
    <div class="col-md-4">
        <select id="categoryFilter" class="form-select">
            <option value="">Semua Kategori</option>
                <?php 
                    $db = \Config\Database::connect();
                    $categories = $db->table('categories')->get()->getResultArray();
                    foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <br>

    <table id="tblProduct" class="table table-bordered table-striped w-100">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th width="15%">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Form Produk</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
let tbl;
$(function () {
    tbl = $('#tblProduct').DataTable({
        processing: true,
        serverSide: true,
        order: [[1, 'asc']],
        ajax: {
            url: "<?= site_url('products/datatable') ?>",
            type: "POST",
            data: function(d) {
                d.categoryFilter = $('#categoryFilter').val();
                d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
            }
        },
        columns: [
            { data: 'no', orderable: false, searchable: false },
            { data: 'name', name: 'p.name' },
            { data: 'category', name: 'c.name' },
            {
                data: 'price',
                name: 'p.price',
                render: function(data) {
                    return 'Rp ' + parseInt(data).toLocaleString('id-ID');
                }, searchable: false
            },
            {
                data: 'stock',
                name: 'p.stock',
                render: function(data) {
                    return data;
                }, searchable: false
            },
            { data: 'aksi', orderable: false, searchable: false }
        ],
        error: function(xhr, error, thrown) {
            console.error('DataTables error:', xhr.responseText);
            alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
        }
    });
});

function openForm(url) {
    $.ajax({
        url: url,
        type: 'GET',
        success: function(res) {
            try {
                let data;
                if (typeof res === 'string') {
                    data = JSON.parse(res);
                } else {
                    data = res;
                }

                $('#modalBody').html(data.view);
                $('#modalForm').modal('show');
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Terjadi kesalahan pada form.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', xhr.responseText);
            alert('Gagal memuat form. Error: ' + error);
        }
    });
}

function editForm(id) {
    openForm('<?= site_url('products/form/') ?>' + id);
}

function deleteData(id) {
    if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
        $.ajax({
            url: '<?= site_url('products/delete/') ?>' + id,
            type: 'POST',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(res) {
                try {
                    let data;
                    if (typeof res === 'string') {
                        data = JSON.parse(res);
                    } else {
                        data = res;
                    }
                    if (data.status === 'success') {
                        alert('Produk berhasil dihapus');
                        tbl.ajax.reload();
                    } else {
                        alert('Gagal menghapus produk');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Terjadi kesalahan saat menghapus.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr.responseText);
                alert('Gagal menghapus produk. Error: ' + error);
            }
        });
    }
}

$('#categoryFilter').on('change', function() {
        currentCategory = $(this).val();
        tbl.ajax.reload();
    });

</script>

</body>
</html>