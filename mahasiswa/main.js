$(document).ready(function() {
    let currentPage = 1;
    let recordsPerPage = 5;
    
    // Initialize data loading
    loadData(currentPage);

    // Handle records per page change
    $('#recordsPerPage').change(function() {
        recordsPerPage = parseInt($(this).val());
        currentPage = 1; // Reset to first page
        loadData(currentPage);
    });

    // Live search handler with debounce
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1; // Reset to first page on search
            loadData(currentPage);
        }, 300); // Debounce delay of 300ms
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
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to load data', 'error');
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
                    <td>${escapeHtml(row.nim)}</td>
                    <td>${escapeHtml(row.nama)}</td>
                    <td>${escapeHtml(row.email)}</td>
                    <td><img src="../photo/${escapeHtml(row.foto)}" class="mahasiswa-img" height="90px"></td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-action edit" data-id="${row.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action delete" data-id="${row.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="5" class="text-center">Tidak ada data ditemukan</td></tr>';
        }
        $('#dataMahasiswa').html(html);
    }

    // Render pagination controls
    function renderPagination(totalPages, currentPage) {
        let html = '';
        
        // Previous button
        if (currentPage > 1) {
            html += `<button class="btn btn-secondary" onclick="loadPage(${currentPage - 1})">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>`;
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (
                i === 1 || // First page
                i === totalPages || // Last page
                (i >= currentPage - 1 && i <= currentPage + 1) // Pages around current
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

        // Next button
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

    // Add new student button handler
    $('#btnAdd').click(function() {
        $('#formMahasiswa')[0].reset();
        $('#id').val('');
        $('#nim').prop('readonly', false);
        $('#previewContainer').hide();
        $('#mahasiswaModal').modal('show');
    });

    // Save button handler
    $('#nim').blur(function() {
        const nim = $(this).val();
        const nimInput = $(this);
        
        // Reset validation state
        nimInput.removeClass('is-valid is-invalid');
        nimInput.siblings('.invalid-feedback, .valid-feedback').remove();
        
        // Skip validation if empty
        if (!nim) return;
        
        // First check format
        if (!validateNim(nim)) {
            nimInput.addClass('is-invalid');
            nimInput.after('<div class="invalid-feedback">NIM harus dalam format A12.2022.06905</div>');
            return;
        }

        // Then check for duplicates in database
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'check_nim',
                nim: nim,
                id: $('#id').val() // Include current ID for edit case
            },
            success: function(response) {
                nimInput.removeClass('is-valid is-invalid');
                nimInput.siblings('.invalid-feedback, .valid-feedback').remove();
                
                if (response.exists) {
                    nimInput.addClass('is-invalid');
                    nimInput.after('<div class="invalid-feedback">NIM sudah terdaftar!</div>');
                } else {
                    nimInput.addClass('is-valid');
                    nimInput.after('<div class="valid-feedback">NIM tersedia</div>');
                }
            },
            error: function() {
                nimInput.addClass('is-invalid');
                nimInput.after('<div class="invalid-feedback">Gagal memeriksa NIM. Silakan coba lagi.</div>');
            }
        });

                 $(document).ready(function() {
            // Show loading spinner
            function showLoadingSpinner() {
                $('#loadingSpinner').show();
            }

            // Hide loading spinner
            function hideLoadingSpinner() {
                $('#loadingSpinner').hide();
            }

            // Handle Generate PDF button click
            $('#btnGeneratePDF').click(function() {
                Swal.fire({
                    title: 'Pilih Opsi PDF',
                    text: 'Apakah Anda ingin melihat atau mengunduh PDF?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-eye mr-2"></i>Lihat PDF',
                    cancelButtonText: '<i class="fas fa-download mr-2"></i>Unduh PDF',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // View PDF in the browser
                        showLoadingSpinner();
                        window.open('generate_pdf.php?action=view', '_blank');
                        hideLoadingSpinner();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Download PDF
                        showLoadingSpinner();
                        window.location.href = 'generate_pdf.php?action=download';
                        hideLoadingSpinner();
                    }
                });
            });
        });

    });

    // Modify save button handler to check validation state
    $('#btnSave').click(function() {
        const nimInput = $('#nim');
        
        // If adding new record, ensure NIM is valid
        if (!$('#id').val() && nimInput.hasClass('is-invalid')) {
            Swal.fire('Error', 'Silakan perbaiki NIM terlebih dahulu!', 'error');
            nimInput.focus();
            return;
        }

        const formData = new FormData($('#formMahasiswa')[0]);
        
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire('Success', response.message, 'success');
                $('#mahasiswaModal').modal('hide');
                loadData(currentPage);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to save data', 'error');
            }
        });
    }); 
    function validateNim(nim) {
        return /^[A-Za-z0-9]{3}\.[0-9]{4}\.[0-9]{5}$/.test(nim);
    }

    // Edit button handler
    $(document).on('click', '.edit', function() {
        const id = $(this).data('id');
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'get_data',
                id: id
            },
            success: function(response) {
                $('#id').val(response.id);
                $('#nim').val(response.nim).prop('readonly', true); // Make NIM readonly for editing
                $('#nama').val(response.nama);
                $('#email').val(response.email);
                if (response.foto_path) {
                    $('#imagePreview').attr('src', response.foto_path);
                    $('#previewContainer').show();
                }
                $('#mahasiswaModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to load data', 'error');
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete', function() {
        const id = $(this).data('id');
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
                        id: id
                    },
                    success: function(response) {
                        Swal.fire('Success', response.message, 'success');
                        loadData(currentPage);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error || 'Failed to delete data', 'error');
                    }
                });
            }
        });
    });

    // Image preview handler
    $('#foto').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#previewContainer').show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Modal reset handler
    $('#mahasiswaModal').on('hidden.bs.modal', function() {
        $('#formMahasiswa')[0].reset();
        $('#id').val('');
        $('#previewContainer').hide();
        $('#nim').prop('readonly', false);
    });
    
     // Show loading spinner
            function showLoadingSpinner() {
                $('#loadingSpinner').show();
            }

            // Hide loading spinner
            function hideLoadingSpinner() {
                $('#loadingSpinner').hide();
            }

            // Handle Generate PDF button click
            $('#btnGeneratePDF').click(function() {
                Swal.fire({
                    title: 'Pilih Opsi PDF',
                    text: 'Apakah Anda ingin melihat atau mengunduh PDF?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-eye mr-2"></i>Lihat PDF',
                    cancelButtonText: '<i class="fas fa-download mr-2"></i>Unduh PDF',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // View PDF in the browser
                        showLoadingSpinner();
                        window.open('generate_pdf.php?action=view', '_blank');
                        hideLoadingSpinner();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Download PDF
                        showLoadingSpinner();
                        window.location.href = 'generate_pdf.php?action=download';
                        hideLoadingSpinner();
                    }
                });
            });

});

// Helper function to escape HTML
function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}