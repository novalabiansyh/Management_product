<?= $this->extend('layout/main') ?>
<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/select2.min.css') ?>">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Data Produk</h5>
        <div>
            <button type="button" id="btnExportExcel" class="btn btn-warning btn-sm me-2">
                Export Excel
            </button>
            <a href="<?= site_url('products/printPdf') ?>?category=" id="btnExportPdf" class="btn btn-success btn-sm me-2" target="_blank">
                Export PDF
            </a>
            <button class="btn btn-primary btn-sm me-2"
                onclick="openForm('<?= site_url('products/form') ?>')">
                Tambah Produk
            </button>
            <div id="exportProgress" style="display:none;">
                <div style="margin-bottom:5px;">
                    Exporting: <span id="progressText">0%</span>
                </div>
                <div style="width:100%; background:#ddd; height:20px;">
                    <div id="progressBar"
                        style="width:0%; height:100%; background:#4CAF50;">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <select id="categoryFilter" class="form-select">
            <option value="">Semua Kategori</option>
                <?php 
                    foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <br>

    <table id="tblProduct" class="table table-bordered table-striped w-100">
        <thead>
            <tr>
                <th width="10px">No</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th width="125px">Aksi</th>
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
<?= $this->endSection(); ?>

<?= $this->section('js'); ?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="<?= base_url('assets/js/select2.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
const exportPdfBaseUrl = "<?= site_url('products/printPdf') ?>";
let currentCategory = '';
let tbl;
$(function () {
    tbl = $('#tblProduct').DataTable({
        processing: true,
        serverSide: true,
        language: {
            searchPlaceholder: 'cari nama produk...'
        },
        order: [[1, 'asc']],
        ajax: {
            url: "<?= base_url('products/datatable') ?>",
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
                searchable: false
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
                $('#modalForm')
                .off('shown.bs.modal')
                .on('shown.bs.modal', function () {
                initCategorySelect2(data);
                })
                .modal('show');
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
    currentCategory = $(this).val(); //this itu elemen yg memicu event change

    tbl.ajax.reload();

    // update link export pdf
    let url = exportPdfBaseUrl;

    if (currentCategory) {
        url += '?category=' + currentCategory;
    }

    $('#btnExportPdf').attr('href', url);
});

$('#btnExportExcel').on('click', function () {
    $('#exportProgress').show();

    let limit = 500;
    let offset = 0;
    let allData = [];
    let category = currentCategory;
    let totalData = 0;

    $.getJSON('products/exportExcelCount', function (res) {
        totalData = res.total;
        loadChunk();
    });

    function loadChunk() {
        console.log('Chunk offset:', offset);

        $.getJSON(
            "<?= site_url('products/exportExcelChunk') ?>",
            { limit, offset, category },
            function (res) {
                if (res.length > 0) {
                    allData = allData.concat(res);
                    offset += res.length;

                    let persen = Math.round((offset / totalData) * 100);
                    persen = Math.min(persen, 100);
                    $('#progressBar').css('width', persen + '%');
                    $('#progressText').text(persen + '%');

                    loadChunk();
                } else {
                    exportExcel(allData);
                }
            }
        );
    }

    function exportExcel(data) {
        $.ajax({
            url: "<?= site_url('products/exportExcel') ?>",
            type: 'POST',
            data: {
                rows: JSON.stringify(data),
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (blob) {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'Data_Produk.xlsx';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                $('#exportProgress').hide();
            }
        });
    }
});

function initCategorySelect2(data) {

  $('#category_id').select2({
    dropdownParent: $('#modalForm'),
    placeholder: '-- pilih kategori --',
    minimumResultsForSearch: 0,
    ajax: {
      url: '<?= site_url('products/categoryList') ?>',
      type: 'POST',
      dataType: 'json',
      delay: 250,
      data: params => ({
        search: params.term,
        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
      }),
      processResults: res => ({
        results: res.items
      })
    }
  });

  //kalau form edit
  if (data.form_type === 'edit') {
    let opt = new Option(
      data.row.category_name, //text
      data.row.category_id, //value
      true, //default
      true //selected
    );
    $('#category_id').append(opt).trigger('change');
  }
}
</script>
<?= $this->endSection() ?>