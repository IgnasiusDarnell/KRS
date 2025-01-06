$(document).ready(function() {
    let currentPage = 1;
    let recordsPerPage = 5;
    
    // Initialize data loading
    loadData(currentPage);

    // Handle records per page change
    $('#recordsPerPage').change(function() {
        recordsPerPage = parseInt($(this).val());
        currentPage = 1;
        loadData(currentPage);
    });

    // Live search handler with debounce
    let searchTimeout;
    $('#search').on('input', function() {
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
            success: function(response) {
                renderTable(response.data);
                renderPagination(response.pagination.totalPages, page);
                updateCurrentPageInfo(page, response.pagination.totalPages);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Gagal memuat data', 'error');
            }
        });
    }

    // Render table data
    function renderTable(data) {
        let html = '';
        if (data.length > 0) {
            data.forEach(function(row) {
                html += `
                <tr>
                    <td>${escapeHtml(row.npp)}</td>
                    <td>${escapeHtml(row.namadosen)}</td>
                    <td>${escapeHtml(row.homebase)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-action edit" data-npp="${row.npp}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action delete" data-npp="${row.npp}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="4" class="text-center">Tidak ada data ditemukan</td></tr>';
        }
        $('#dataDosen').html(html);
    }

    // Render pagination controls
    function renderPagination(totalPages, currentPage) {
        let html = '';
        
        if (currentPage > 1) {
            html += `<button class="btn btn-secondary" onclick="loadPage(${currentPage - 1})">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (
                i === 1 ||
                i === totalPages ||
                (i >= currentPage - 1 && i <= currentPage + 1)
            ) {
                html += `<button class="btn ${i === currentPage ? 'btn-primary' : 'btn-light'}" 
                        onclick="loadPage(${i})">${i}</button>`;
            } else if (
                i === currentPage - 2 ||
                i === currentPage + 2
            ) {
                html += `<span class="btn btn-light disabled">...</span>`;
            }
        }

        if (currentPage < totalPages) {
            html += `<button class="btn btn-secondary" onclick="loadPage(${currentPage + 1})">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>`;
        }

        $('#pagination').html(html);
    }

    // Update current page info
    function updateCurrentPageInfo(currentPage, totalPages) {
        $('#currentPageInfo').text(`Page ${currentPage} of ${totalPages}`);
    }

    // Global function for pagination
    window.loadPage = function(page) {
        currentPage = page;
        loadData(page);
    };

    // Add new dosen button handler
    $('#btnAdd').click(function() {
        $('#formDosen')[0].reset();
        $('#id').val(''); // Clear hidden ID field
        $('#npp').prop('readonly', false); // Enable NPP field for new entry
        $('#dosenModal').modal('show');
    });

    // NPP validation on blur
    $('#npp').blur(function() {
        const npp = $(this).val();
        const nppInput = $(this);
        const currentNpp = $('#id').val(); // Use hidden ID field for current NPP
        
        // Skip validation if we're in edit mode
        if (currentNpp) return;
        
        nppInput.removeClass('is-valid is-invalid');
        nppInput.siblings('.invalid-feedback, .valid-feedback').remove();
        
        if (!npp) return;
        
        if (!validateNPP(npp)) {
            nppInput.addClass('is-invalid');
            nppInput.after('<div class="invalid-feedback">NPP harus dalam format 0686.11.1993.003</div>');
            return;
        }

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'check_npp',
                npp: npp,
                current_npp: currentNpp
            },
            success: function(response) {
                nppInput.removeClass('is-valid is-invalid');
                nppInput.siblings('.invalid-feedback, .valid-feedback').remove();
                
                if (response.exists) {
                    nppInput.addClass('is-invalid');
                    nppInput.after('<div class="invalid-feedback">NPP sudah terdaftar!</div>');
                } else {
                    nppInput.addClass('is-valid');
                    nppInput.after('<div class="valid-feedback">NPP tersedia</div>');
                }
            },
            error: function() {
                nppInput.addClass('is-invalid');
                nppInput.after('<div class="invalid-feedback">Gagal memeriksa NPP. Silakan coba lagi.</div>');
            }
        });
    });

    // Save button handler
    $('#btnSave').click(function() {
        const nppInput = $('#npp');
        
        if (!$('#id').val() && nppInput.hasClass('is-invalid')) {
            Swal.fire('Error', 'Silakan perbaiki NPP terlebih dahulu!', 'error');
            nppInput.focus();
            return;
        }

        const formData = new FormData($('#formDosen')[0]);
        formData.append('action', 'save');
        
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire('Success', response.message, 'success');
                $('#dosenModal').modal('hide');
                loadData(currentPage);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Gagal menyimpan data', 'error');
            }
        });
    });

    function validateNPP(npp) {
        return /^\d{4}\.\d{2}\.\d{4}\.\d{3}$/.test(npp);
    }

    // Edit button handler
    $(document).on('click', '.edit', function() {
        const npp = $(this).data('npp');
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'get_data',
                npp: npp
            },
            success: function(response) {
                $('#id').val(response.npp); // Set hidden ID field
                $('#npp').val(response.npp).prop('readonly', true); // Disable NPP field for editing
                $('#namadosen').val(response.namadosen);
                $('#homebase').val(response.homebase);
                $('#dosenModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Gagal memuat data', 'error');
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete', function() {
        const npp = $(this).data('npp');
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
                        npp: npp
                    },
                    success: function(response) {
                        Swal.fire('Success', response.message, 'success');
                        loadData(currentPage);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error || 'Gagal menghapus data', 'error');
                    }
                });
            }
        });
    });

    // Modal reset handler
    $('#dosenModal').on('hidden.bs.modal', function() {
        $('#formDosen')[0].reset();
        $('#id').val(''); // Clear hidden ID field
        $('#npp').prop('readonly', false); // Re-enable NPP field for new entries
        $('#npp, #namadosen').removeClass('is-valid is-invalid');
        $('.invalid-feedback, .valid-feedback').remove();
    });
    
});

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}