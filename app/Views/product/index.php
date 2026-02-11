<?= $this->extend('layout/main') ?>
<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/select2.min.css') ?>">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container mt-4">
<h5 class="mb-3">Data Produk</h5>
<div class="d-flex align-items-start justify-content-between mb-3 gap-3">
    <div class="flex-grow-1">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">From Date</label>
                <input type="date" id="fromDate" class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <label class="form-label small">To Date</label>
                <input type="date" id="toDate" class="form-control form-control-sm">
            </div>

            <div class="col-md-4">
                <label class="form-label small">Kategori</label>
                <select id="categoryFilter" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button id="btnFilter" class="btn btn-primary btn-sm px-4">
                Filter
            </button>
            <button id="btnReset" class="btn btn-secondary btn-sm px-4">
                Reset
            </button>
        </div>

    </div>

    <div class="d-flex gap-2 flex-shrink-0 align-items-center mt-4">
        <button class="btn btn-secondary btn-sm"
            onclick="openImportForm('<?= site_url('products/import') ?>')">
            Import Excel
        </button>

        <button type="button" id="btnExportExcel" class="btn btn-warning btn-sm">
            Export Excel
        </button>

        <a href="<?= site_url('products/printPdf') ?>?category="
           id="btnPrintPdf"
           class="btn btn-success btn-sm"
           target="_blank">
            Print PDF
        </a>

        <button class="btn btn-primary btn-sm"
            onclick="openForm('<?= site_url('products/form') ?>')">
            Tambah Produk
        </button>
    </div>
</div>
<div id="exportProgress" style="display:none;" class="mb-3">
    <div class="small mb-1">
        Exporting: <span id="progressText">0%</span>
    </div>
    <div style="width:100%; background:#ddd; height:8px; border-radius:4px;">
        <div id="progressBar"
            style="width:0%; height:100%; background:#4CAF50; border-radius:4px;">
        </div>
    </div>
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
                <th width="50px">Created_by</th>
                <th>Created_at</th>
                <th width="125px">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- MODAL FORM-->
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

<!--MODAL UPLOAD-->
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upload File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Dropzone area -->
        <form action="/files/upload" class="dropzone" id="myDropzone"></form>
        <div class="modal-footer d-flex justify-content-end">
            <button id="btnUpload" class="btn btn-primary">Upload</button>
        </div>

        <hr>
        <!-- Datatable files -->
        <table id="datatableFiles" class="table table-bordered table-striped" style="width: 100%;">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama File</th>
              <th>File Directory</th>
              <th>Created At</th>
              <th>Created By</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
<script>
const printPdfBaseUrl = "<?= site_url('products/printPdf') ?>";
let currentFromDate = '';
let currentToDate = '';
let currentCategory = '';
let tbl;
let currentProductId = null;
let isExporting = false;
let exportBtnText = $('#btnExportExcel').text();
let exportTimeout = null;
$(function () {
    initCategorySelect2Filter();
    tbl = $('#tblProduct').DataTable({
        processing: true,
        serverSide: true,
        language: {
            searchPlaceholder: 'cari nama produk...'
        },
        order: [],
        ajax: {
            url: "<?= base_url('products/datatable') ?>",
            type: "POST",
            data: function(d) {
                d.category = currentCategory;
                d.fromDate = currentFromDate;
                d.toDate = currentToDate;
                d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
            }
        },
        columns: [
            { data: 'no', orderable: false, searchable: false },
            { data: 'name', name: 'p.name', orderable: false },
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
            { data: 'created_by', name: 'u.username',
            render: function(data) {
                return data ?? '-';
            }
            },
            { data: 'created_at', name: 'p.created_at'},
            { data: 'aksi', orderable: false, searchable: false }
        ],
        error: function(xhr, error, thrown) {
            console.error('DataTables error:', xhr.responseText);
            alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
        }
    });
});

function showALert(message, type = ''){
    $('#alertModalContent')
        .removeClass('border-success border-danger');
    $('#alertModalTitle')
        .removeClass('text-success text-danger');

    let title = '';
    if (type === 'success'){
        title = 'Berhasil';
        $('#alertModalContent').addClass('border-success');
        $('#alertModalTitle').addClass('text-success');
    } else if (type === 'error'){
        title = 'Gagal';
        $('#alertModalContent').addClass('border-danger');
        $('#alertModalTitle').addClass('text-danger');
    }

    $('#alertModalTitle').text(title);
    $('#alertModalBody').text(message);
    $('#alertModal').modal('show');
}

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
                $('#modalForm .modal-title').text('Form Produk');
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

function openImportForm(url){
    $('#modalForm').off('shown.bs.modal');
    $('#modalBody').empty();
    $('#modalForm .modal-title').text('Import Excel');

    $.ajax({
        url : url,
        type : 'GET',
        success : function(res) {
            $('#modalBody').html(res);
            $('#modalForm').modal('show');
        },
        error: function(xhr){
            console.error(xhr.responseText);
            alert('Terjadi kesalahan');
        }
    });
}

function deleteData(id) { //parameter id dapat darimana?
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
                        showALert(data.message, 'success');
                        tbl.ajax.reload();
                    } else {
                        showALert(data.message, 'error');
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

$('#categoryFilter, #fromDate, #toDate').on('change', function() {
    currentCategory = $('#categoryFilter').val();
    currentFromDate = $('#fromDate').val();
    currentToDate = $('#toDate').val();

    let url = printPdfBaseUrl + `?category=${currentCategory}&fromDate=${currentFromDate}&toDate=${currentToDate}`;
    $('#btnPrintPdf').attr('href', url);
});

$('#btnFilter').on('click', function(){
    currentCategory = $('#categoryFilter').val();
    currentFromDate = $('#fromDate').val();
    currentToDate = $('#toDate').val();

    tbl.ajax.reload(null, true);

    let url = printPdfBaseUrl + `?category=${currentCategory}&from=${currentFromDate}&to=${currentToDate}`;

    $('#btnPrintPdf').attr('href', url);
});

$('#btnReset').on('click', function(){
    currentCategory = '';
    currentFromDate = '';
    currentToDate = '';

    $('#fromDate').val('');
    $('#toDate').val('');
    $('#categoryFilter').val(null).trigger('change');

    tbl.ajax.reload(null, true);

    $('#btnPrintPdf').attr('href', printPdfBaseUrl);
});

$('#btnExportExcel').on('click', function () {
    if (isExporting) return;

    isExporting = true;
    const $btn = $(this);
    exportBtnText = $btn.text();
    $btn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span> sedang memproses...');

    $('#exportProgress').show();
    $('#progressBar').css('width', '0%');
    $('#progressText').text('0%');

    let limit = 100;
    let offset = 0;
    let allData = [];
    let category = currentCategory;
    let fromDate = currentFromDate;
    let toDate = currentToDate;
    let totalData = 0;

    $.getJSON("<?= site_url('products/exportExcelCount') ?>",
    {
        category, fromDate, toDate
    }, 
    function (res) {
        totalData = res.total;
        
        if (totalData === 0) {
            exportExcel([]);
            return;
        }
        loadChunk();
    });

    function loadChunk() {
        console.log('Chunk offset:', offset);

        $.getJSON(
            "<?= site_url('products/exportExcelChunk') ?>",
            { limit, offset, category, fromDate, toDate },
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
                    $('#progressBar').css('width', '100%');
                    $('#progressText').text('100%');

                    setTimeout(() => {
                        exportExcel(allData);
                    }, 300);
                }
            }
        );
    }

    function exportExcel(data) {
        
        exportTimeout = setTimeout(() => {
            finishExport(); // hide loading, enable button
        }, 2000);

        $.ajax({
            url: "<?= site_url('products/exportExcel') ?>",
            type: 'POST',
            data: {
                rows: JSON.stringify(data),
                category: currentCategory,
                fromDate: currentFromDate,
                toDate: currentToDate,
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
            },
            error: function (xhr) {
                alert('Gagal export excel');
                console.error(xhr);
            },
            complete: function(){
                clearTimeout(exportTimeout);
                finishExport();         
            }
        });
    }

    function finishExport(){
        isExporting = false;
        $btn.prop('disabled', false)
            .text(exportBtnText);
        $('#exportProgress').hide();
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

function initCategorySelect2Filter(){
    $('#categoryFilter').select2({
        placeholder: 'Semua Kategori',
        allowClear: true,
        width: '100%',
        ajax: {
            url: "<?= site_url('products/categoryList') ?>",
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params){
                return {
                    search: params.term,
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                };
            },
            processResults: function (res){
                return {
                    results: res.items
                };
            }
        }
    });
}

function uploadForm(productId) {
    currentProductId = productId;

    $('#uploadModal').modal('show');
    $('#datatableFiles').DataTable().ajax.reload();
}

    $(document).ready(function () {
        $('#datatableFiles').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "<?= base_url('files/datatable') ?>",
                type: 'POST',
                data: function (d) {
                    d.refid = currentProductId;
                }
            },
            order: [],
            columns: [
                { data: 'no', orderable: false, searchable: false },
                { data: 'filerealname' },
                { data: 'filedirectory' },
                { data: 'created_date' },
                { data: 'created_by' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        });
    });

    Dropzone.autoDiscover = false;

    let myDropzone = new Dropzone("#myDropzone", {
        url: "<?= base_url('files/upload') ?>", // endpoint upload
        autoProcessQueue: false,
        paramName: "file",
        maxFilesize: 50, // MB
        chunking: true,
        forceChunking: true,
        chunkSize: 2000000, // 2MB
        retryChunks: true,
        retryChunksLimit: 3,
        parallelUploads: 1,
        addRemoveLinks: true,
        init: function () {
            this.on("sending", function (file, xhr, formData) {
                formData.append("refid", currentProductId);
            });

            this.on("success", function (file, response) {
                $('#datatableFiles').DataTable().ajax.reload();
            });

            document.querySelector("#btnUpload").addEventListener("click", () => {
            this.processQueue();
            });
        }
    });
  
    function deleteFile(fileId) {
        if (confirm("Yakin mau hapus file ini?")) {
            $.ajax({
                url: "<?= base_url('files/delete') ?>/" + fileId,
                type: "POST",
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        $('#datatableFiles').DataTable().ajax.reload();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert("Terjadi kesalahan saat menghapus file.");
                }
            });
        }
    }

</script>
<?= $this->endSection() ?>