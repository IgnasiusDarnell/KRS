$(document).ready(function () {
    let currentPage = 1;
    let recordsPerPage = 5;
    let currentKlp = '';
    let currentHari = '';

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

    // Handle klp filter change
    $('#filterKlp').change(function () {
        currentKlp = $(this).val();
        currentPage = 1;
        loadData(currentPage);
    });

    // Handle hari filter change
    $('#filterHari').change(function () {
        currentHari = $(this).val();
        currentPage = 1;
        loadData(currentPage);
    });

    // Load data function with search, pagination, and filtering
    function loadData(page = 1) {
        const query = $('#search').val();
        showLoadingSpinner();

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'load',
                query: query,
                page: page,
                limit: recordsPerPage,
                klp: currentKlp,
                hari: currentHari
            },
            success: function (response) {
                hideLoadingSpinner();
                renderTable(response.data);
                renderPagination(response.pagination.totalPages, page);
                updateCurrentPageInfo(page, response.pagination.totalPages);
            },
            error: function (xhr) {
                hideLoadingSpinner();
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to load data', 'error');
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
                    <td>${escapeHtml(row.idkultawar)}</td>
                    <td>${escapeHtml(row.namamatkul)}</td>
                    <td>${escapeHtml(row.namadosen)}</td>
                    <td>${escapeHtml(row.klp)}</td>
                    <td>${escapeHtml(row.hari)}</td>
                    <td>${escapeHtml(row.jamkul)}</td>
                    <td>${escapeHtml(row.ruang)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-action edit" data-id="${row.idkultawar}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action delete" data-id="${row.idkultawar}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="8" class="text-center">No data found</td></tr>';
        }
        $('#dataKultawar').html(html);
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
    window.loadPage = function (page) {
        currentPage = page;
        loadData(page);
    };

    // Add new kultawar button handler
    $('#btnAdd').click(function () {
        $('#formKultawar')[0].reset();
        $('#idkultawar').val('');
        $('#modalTitle').text('Tambah Kultawar');
        $('#kultawarModal').modal('show');
    });

    // Save button handler
    $('#btnSave').click(function () {
        const formData = new FormData($('#formKultawar')[0]);
        formData.append('action', 'save');

        showLoadingSpinner();

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                hideLoadingSpinner();
                Swal.fire('Success', response.message, 'success');
                $('#kultawarModal').modal('hide');
                loadData(currentPage);
            },
            error: function (xhr) {
                hideLoadingSpinner();
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to save data', 'error');
            }
        });
    });

    // Edit button handler
    $(document).on('click', '.edit', function () {
        const idkultawar = $(this).data('id');
        showLoadingSpinner();

        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: {
                action: 'get_data',
                idkultawar: idkultawar
            },
            success: function (response) {
                hideLoadingSpinner();
                $('#idkultawar').val(response.idkultawar);
                $('#idmatkul').val(response.idmatkul);
                $('#npp').val(response.npp);
                $('#klp').val(response.klp);
                $('#hari').val(response.hari);
                $('#jamkul').val(response.jamkul);
                $('#ruang').val(response.ruang);
                $('#modalTitle').text('Edit Kultawar');
                $('#kultawarModal').modal('show');
            },
            error: function (xhr) {
                hideLoadingSpinner();
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to load data', 'error');
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete', function () {
        const idkultawar = $(this).data('id');
        Swal.fire({
            title: 'Confirm',
            text: 'Are you sure you want to delete this record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoadingSpinner();

                $.ajax({
                    url: 'proses.php',
                    method: 'POST',
                    data: {
                        action: 'delete',
                        idkultawar: idkultawar
                    },
                    success: function (response) {
                        hideLoadingSpinner();
                        Swal.fire('Success', response.message, 'success');
                        loadData(currentPage);
                    },
                    error: function (xhr) {
                        hideLoadingSpinner();
                        Swal.fire('Error', xhr.responseJSON?.error || 'Failed to delete data', 'error');
                    }
                });
            }
        });
    });

    // Modal reset handler
    $('#kultawarModal').on('hidden.bs.modal', function () {
        $('#formKultawar')[0].reset();
        $('#idkultawar').val('');
    });

    // Populate Mata Kuliah dropdown
    function loadMatkulDropdown() {
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: { action: 'get_matkul' },
            success: function (response) {
                let options = '<option value="">Pilih Mata Kuliah</option>';
                response.forEach(function (matkul) {
                    options += `<option value="${matkul.idmatkul}">${matkul.namamatkul}</option>`;
                });
                $('#idmatkul').html(options);
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to load Mata Kuliah', 'error');
            }
        });
    }

    // Populate Dosen dropdown
    function loadDosenDropdown() {
        $.ajax({
            url: 'proses.php',
            method: 'POST',
            data: { action: 'get_dosen' },
            success: function (response) {
                let options = '<option value="">Pilih Dosen</option>';
                response.forEach(function (dosen) {
                    options += `<option value="${dosen.npp}">${dosen.namadosen}</option>`;
                });
                $('#npp').html(options);
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.error || 'Failed to load Dosen', 'error');
            }
        });
    }

    // Load dropdowns when the modal is shown
    $('#kultawarModal').on('show.bs.modal', function () {
        loadMatkulDropdown();
        loadDosenDropdown();
    });

    // Show loading spinner
    function showLoadingSpinner() {
        $('#loadingSpinner').fadeIn();
    }

    // Hide loading spinner
    function hideLoadingSpinner() {
        $('#loadingSpinner').fadeOut();
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            text = String(text); // Convert non-string values to strings
        }
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});