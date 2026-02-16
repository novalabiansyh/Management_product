<?= $this->extend('layout/main') ?>
<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/select2.min.css') ?>">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" />
<?= $this->endSection(); ?>

<?= $this->section('content') ?>
<div class="container">
    <h3>Edit Produk</h3>
    <?php echo view('product/form', [
        'form_type' => $form_type,
        'row' => $row,
        'productid' => $productid
    ]); ?>

    <hr>
    <h4>Files</h4>
    <div class="d-flex justify-content-end">
        <button class="btn btn-warning mb-2 me-2" onclick="window.location.href='<?= site_url('products') ?>'">Kembali</button>
        <button class="btn btn-primary mb-2" onclick="openUploadModal()">Add File</button>
    </div>
    <table id="tblFiles" class="table table-bordered table-striped" style="width:100%;">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama File</th>
                <th>File Directory</th>
                <th>Created At</th>
                <th>Created By</th>
                <th style="width:120px;">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upload File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="<?= base_url('files/upload') ?>" class="dropzone" id="myDropzone"></form>
        <div class="modal-footer d-flex justify-content-end">
            <button id="btnUpload" class="btn btn-primary">Upload</button>
        </div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let currentProductId = "<?= $productid ?>";
    let isUploadCanceled = false;

    let tblFiles = $('#tblFiles').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "<?= base_url('files/datatable') ?>",
            type: 'POST',
            data: function(d) {
                d.refid = currentProductId;
            }
        },
        order: [],
        columns: [
            {data: 'no', orderable: false},
            { data: 'filerealname' },
            { data: 'filedirectory' },
            { data: 'created_date' },
            { data: 'created_by' },
            { data: 'aksi', orderable: false, searchable: false }
        ]
    });

    function initCategorySelect2() {
        $('#category_id').select2({
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

    <?php if ($form_type === 'edit' && !empty($row)) : ?>
        let opt = new Option(
        "<?= $row['category_name'] ?>",
        "<?= $row['category_id'] ?>",
        true,
        true
        );
        $('#category_id').append(opt).trigger('change');
    <?php endif; ?>
    }

    $(document).ready(function() {
        initCategorySelect2();
    });

    function openUploadModal(){
        $('#uploadModal').modal('show');
    }

    Dropzone.autoDiscover = false;

let myDropzone = new Dropzone("#myDropzone", {
    url: "<?= base_url('files/upload') ?>",
    autoProcessQueue: false,
    paramName: "file",
    maxFilesize: 50,
    chunking: true,
    forceChunking: true,
    chunkSize: 2000000,
    retryChunks: true,
    retryChunksLimit: 3,
    parallelUploads: 1,
    addRemoveLinks: true,
    init: function () {
        let dz = this;
        let modalEl = $('#uploadModal');
        let btnUpload = $("#btnUpload");
        let btnClose = modalEl.find('.btn-close');
        let bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

        dz.on("sending", function (file, xhr, formData) {
            isUploadCanceled = false;
            formData.append("refid", currentProductId);
            formData.append("<?= csrf_token() ?>", "<?= csrf_hash() ?>");

            bsModal._config.backdrop = 'static';
            bsModal._config.keyboard = false;

            btnUpload.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Uploading...');
            btnClose.hide();
        });

        dz.on("canceled", function(file) {
            isUploadCanceled = true;
            $.post("<?= base_url('files/cleanup') ?>", {
                dzuuid: file.upload.uuid,
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
            });

            btnUpload.prop('disabled', false).text('Upload');
            btnClose.show();

            Swal.fire('Dibatalkan', 'Upload file telah dihentikan.', 'info');
        });

        dz.on("success", function (file, response) {
            $('#tblFiles').DataTable().ajax.reload(null, false);

            if (dz.getQueuedFiles().length > 0){
                dz.processQueue();
            }
        });

        dz.on("queuecomplete", function () {
            bsModal._config.backdrop = true;
            bsModal._config.keyboard = true;

            btnUpload.prop('disabled', false).text('Upload');
            btnClose.show();

            dz.removeAllFiles(true);
            if (!isUploadCanceled){
                Swal.fire({
                    icon: 'success',
                    title: 'Upload Selesai',
                    text: 'Semua file telah diupload.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            $('#uploadModal').modal('hide');
        });

        dz.on("error", function(file, message) {
            isUploadCanceled = true;
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + message, 'error');
            btnUpload.prop('disabled', false).text('Upload');
            btnClose.show();
        });

        btnUpload.on("click", function() {
            if (dz.getQueuedFiles().length > 0) {
                dz.processQueue();
            } else {
                Swal.fire('Info', 'Pilih File terlebih dahulu!', 'info');
            }
        });
    }
});

function deleteFile(fileId) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "File yang dihapus tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?= base_url('files/delete') ?>/" + fileId,
                type: "POST",
                data: { "<?= csrf_token() ?>": "<?= csrf_hash() ?>" },
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Terhapus!', res.message, 'success');
                        $('#tblFiles').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                }
            });
        }
    });
}

</script>
<?= $this->endSection(); ?>