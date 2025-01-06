$(document).ready(function () {
    let currentPage = 1;
    let recordsPerPage = 5;

    // Initialize data loading
    loadData(currentPage);

    // Handle records per page change
    $('#recordsPerPage').change(function () {
        recordsPerPage = parseInt($(this).val());
        currentPage = 1;
        loadData(currentPage);
    });

    // Live search handler with debounce
    let searchTimeout;
    $('#search').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadData(currentPage);
        }, 300);
    });

    // Load data function with search and pagination
    function loadData(page = 1) {
        const query = $('#search').val();

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'load',
                query: query,
                page: page,
                limit: recordsPerPage
            },
            success: function (response) {
                renderTable(response.data);
                renderPagination(response.pagination.totalPages, page);
                updateCurrentPageInfo(page, response.pagination.totalPages, response.pagination.totalRecords);
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Gagal memuat data', 'error');
            }
        });
    }

    // Render table data
    function renderTable(data) {
        let html = '';
        if (data.length > 0) {
            data.forEach(function (row) {
                html += `
                <tr>
                    <td>${escapeHtml(row.idmatkul || '')}</td>
                    <td>${escapeHtml(row.namamatkul || '')}</td>
                    <td>${row.sks || ''}</td>  <!-- Removed escapeHtml for SKS since it's a number -->
                    <td>${escapeHtml(row.jns || '')}</td>
                    <td>${escapeHtml(row.smt || '')}</td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-action edit" data-idmatkul="${escapeHtml(row.idmatkul)}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action delete" data-idmatkul="${escapeHtml(row.idmatkul)}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="6" class="text-center">Tidak ada data ditemukan</td></tr>';
        }
        $('#dataMatkul').html(html);
    }

    // Render pagination controls
    function renderPagination(totalPages, currentPage) {
        let html = '';

        if (totalPages > 1) {
            // Previous button
            if (currentPage > 1) {
                html += `<button class="btn btn-secondary" onclick="loadPage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>`;
            }

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (
                    i === 1 ||
                    i === totalPages ||
                    (i >= currentPage - 2 && i <= currentPage + 2)
                ) {
                    html += `<button class="btn ${i === currentPage ? 'btn-primary' : 'btn-light'}" 
                            onclick="loadPage(${i})">${i}</button>`;
                } else if (
                    i === currentPage - 3 ||
                    i === currentPage + 3
                ) {
                    html += '<span class="btn btn-light disabled">...</span>';
                }
            }

            // Next button
            if (currentPage < totalPages) {
                html += `<button class="btn btn-secondary" onclick="loadPage(${currentPage + 1})">
                    Next <i class="fas fa-chevron-right"></i>
                </button>`;
            }
        }

        $('#pagination').html(html);
    }

    // Update current page info
    function updateCurrentPageInfo(currentPage, totalPages, totalRecords) {
        $('#currentPageInfo').text(
            `Showing page ${currentPage} of ${totalPages} (Total records: ${totalRecords})`
        );
    }

    // Global function for pagination
    window.loadPage = function (page) {
        currentPage = page;
        loadData(page);
    };

    // Add new matkul button handler
    $('#btnAdd').click(function () {
        $('#formMatkul')[0].reset();
        $('#id').val('');
        $('#idmatkul').prop('readonly', false);
        $('#idmatkul, #namamatkul').removeClass('is-valid is-invalid');
        $('.invalid-feedback, .valid-feedback').remove();
        $('#matkulModal').modal('show');
    });

    // Form validation
    function validateForm() {
        let isValid = true;
        const id = $('#id').val();
        const idmatkul = $('#idmatkul').val();
        const namamatkul = $('#namamatkul').val();
        const sks = $('#sks').val();
        const jns = $('#jns').val();
        const smt = $('#smt').val();

        // Clear previous validation
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Required fields validation
        if (!idmatkul || !namamatkul || !sks || !jns || !smt) {
            isValid = false;
            Swal.fire('Error', 'Semua field harus diisi!', 'error');
        }

        return isValid;
    }

    // idmatkul validation on blur
    $('#idmatkul').blur(function () {
        const idmatkul = $(this).val();
        const currentId = $('#id').val();
        const idmatkulInput = $(this);

        if (currentId) return; // Skip validation for edit mode

        idmatkulInput.removeClass('is-valid is-invalid');
        idmatkulInput.siblings('.invalid-feedback, .valid-feedback').remove();

        if (!idmatkul) return;

        if (!/^A(11|12)\.\d{5}$/.test(idmatkul)) {
            idmatkulInput.addClass('is-invalid');
            idmatkulInput.after('<div class="invalid-feedback">Format ID Matkul tidak valid! Contoh: A12.56101</div>');
            return;
        }

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'check_idmatkul',
                idmatkul: idmatkul,
                current_idmatkul: currentId
            },
            success: function (response) {
                idmatkulInput.removeClass('is-valid is-invalid');
                idmatkulInput.siblings('.invalid-feedback, .valid-feedback').remove();

                if (response.exists) {
                    idmatkulInput.addClass('is-invalid');
                    idmatkulInput.after('<div class="invalid-feedback">ID Matkul sudah terdaftar!</div>');
                } else {
                    idmatkulInput.addClass('is-valid');
                    idmatkulInput.after('<div class="valid-feedback">ID Matkul tersedia</div>');
                }
            },
            error: function () {
                idmatkulInput.addClass('is-invalid');
                idmatkulInput.after('<div class="invalid-feedback">Gagal memeriksa ID Matkul</div>');
            }
        });
    });

    // Save button handler
    $('#btnSave').click(function () {
        if (!validateForm()) return;

        const idmatkulInput = $('#idmatkul');
        const currentId = $('#id').val();

        // Only validate idmatkul for new entries
        if (!currentId && idmatkulInput.hasClass('is-invalid')) {
            Swal.fire('Error', 'Silakan perbaiki ID Matkul terlebih dahulu!', 'error');
            idmatkulInput.focus();
            return;
        }

        const formData = new FormData($('#formMatkul')[0]);
        formData.append('action', 'save');

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                Swal.fire('Success', response.message, 'success');
                $('#matkulModal').modal('hide');
                loadData(currentPage);
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Gagal menyimpan data', 'error');
            }
        });
    });

    // Edit button handler
    $(document).on('click', '.edit', function () {
        const idmatkul = $(this).data('idmatkul');
        
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'get_data',
                idmatkul: idmatkul
            },
            success: function (response) {
                $('#id').val(response.idmatkul);
                $('#idmatkul').val(response.idmatkul).prop('readonly', true);
                $('#namamatkul').val(response.namamatkul);
                $('#sks').val(response.sks);
                $('#jns').val(response.jns);
                $('#smt').val(response.smt);
                $('#matkulModal').modal('show');
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Gagal memuat data', 'error');
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete', function () {
        const idmatkul = $(this).data('idmatkul');
        
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin menghapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'proses.php',
                    method: 'POST',
                    data: {
                        action: 'delete',
                        idmatkul: idmatkul
                    },
                    success: function (response) {
                        Swal.fire('Success', response.message, 'success');
                        loadData(currentPage);
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error || 'Gagal menghapus data', 'error');
                    }
                });
            }
        });
    });

    // Modal reset handler
    $('#matkulModal').on('hidden.bs.modal', function () {
        $('#formMatkul')[0].reset();
        $('#id').val('');
        $('#idmatkul').prop('readonly', false);
        $('#idmatkul, #namamatkul').removeClass('is-valid is-invalid');
        $('.invalid-feedback, .valid-feedback').remove();
    });

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return '';
        }
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});