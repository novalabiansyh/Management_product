<form id="formImport" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label">File Excel</label>
        <input type="file" name="file" class="form-control" accept=".xls,.xlsx" required>
        <small class="text-muted">Format: .xls atau .xlsx</small>
    </div>

    <div id="importProgress" style="display:none;">
        <div class="mb-1">
            Importing: <span id="importProgressText">0%</span>
        </div>
        <div style="width:100%; background:#ddd; height:20px;">
            <div id="importProgressBar" style="width:0%; height:100%; background:#4CAF50;"></div>
        </div>
    </div>
    <div id="importResult" class="mt-3"></div>
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="<?= site_url('products/downloadTemplate') ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i>Template
        </a>

        <div>
        <button type="button" class="btn btn-outline-danger me-2 d-none" id="btnCancelImport">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </button>

        <button type="button" class="btn btn-primary" id="btnImport" onclick="submitImport()">
            <i class="bi bi-upload me-1"></i> Import
        </button>
    </div>
    </div>
</form>

<script>
let importFile = '';
let importTotal = 0;
let importOffset = 0;
let importLimit = 100;
let totalSuccess = 0;
let totalFailed = 0;
let isCanceled = false;

    function submitImport() {
    //reset variable
    isCanceled = false;
    importFile = '';
    importTotal = 0;
    importOffset = 0;
    totalSuccess = 0;
    totalFailed = 0;

    $('#importResult').html('');
    $('#importProgressBar').css('width', '0%');
    $('#importProgressText').text('0%');

    let formData = new FormData($('#formImport')[0]);

    $.ajax({
        url: "<?= site_url('products/import') ?>",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                importFile = res.file;
                importTotal = res.total;
                importOffset = 0;

                $('#btnImport').prop('disabled', true).text('sedang memproses...');
                $('#btnCancelImport').removeClass('d-none');

                $('#importProgress').show();
                startImportChunk();
            } else {
                alert(res.message);
            }
        }
    });
}

function startImportChunk() {   

    if (isCanceled) return;

    $.getJSON("<?= site_url('products/importChunk') ?>",{
            file: importFile,
            offset: importOffset,
            limit: importLimit
        },
        function(res) {

            if (isCanceled) return;

            totalSuccess += res.success;
            totalFailed += res.failed;
            importOffset += importLimit;
            let persen = Math.min(Math.round(importOffset / importTotal * 100), 100);
            $('#importProgressBar').css('width', persen + '%');
            $('#importProgressText').text(persen + '%');

            $('#importResult').html(`
                <div class="alert alert-info">
                    Import Success: <b>${totalSuccess}</b><br>
                    Import Failed: <b>${totalFailed}</b>
                </div>
            `);

        if (importOffset < importTotal) {
            startImportChunk();
        } else {
            // selesai
            $('#btnImport').prop('disabled', false).text('Import');
            $('#btnCancelImport').addClass('d-none');
            $('#importProgressBar').css('width', '100%');
            $('#importProgressText').text('100%');

            $('#importResult').html(`
                <div class="alert alert-warning">
                    <b>Import selesai!</b><br>
                    Success: ${totalSuccess}<br>
                    Failed: ${totalFailed}
                </div>
            `);

            $('#tblProduct').DataTable().ajax.reload();
        }
        }
    );
}

$('#btnCancelImport').on('click', function () {
    if (!confirm('Batalkan proses import?')) return;

    isCanceled = true;

    $('#btnImport').prop('disabled', false).text('Import');
    $('#btnCancelImport').addClass('d-none');

    $('#importProgress').hide();

    $('#importResult').html(`
        <div class="alert alert-secondary">
            Proses import dibatalkan.<br>
            Success: ${totalSuccess}<br>
            Failed: ${totalFailed}
        </div>
    `);
});

</script>