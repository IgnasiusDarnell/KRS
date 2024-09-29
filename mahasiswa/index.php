<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahasiswa</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <style>
        .mahasiswa-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
    </style>
</head>

<body class="container mt-5">

    <h2 class="mb-4">Data Mahasiswa</h2>
    <button class="btn btn-primary mb-3" id="btnAdd">Tambah Mahasiswa</button>
    <div class="form-group">
        <input type="text" class="form-control" id="search" placeholder="Cari mahasiswa...">
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIM</th>
                <th>Email</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="dataMahasiswa"></tbody>
    </table>

    <!-- Modal -->
    <div class="modal fade" id="mahasiswaModal" tabindex="-1" role="dialog" aria-labelledby="mahasiswaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mahasiswaModalLabel">Tambah/Edit Mahasiswa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formMahasiswa" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="id">
                        <div class="form-group">
                            <label for="nama">Nama:</label>
                            <input type="text" class="form-control" name="nama" id="nama" required>
                        </div>
                        <div class="form-group">
                            <label for="nim">NIM:</label>
                            <input type="text" class="form-control" name="nim" id="nim" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="text" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="form-group">
                            <label for="foto">Foto:</label>
                            <input type="file" class="form-control-file" name="foto" id="foto" accept="image/*">
                        </div>
                        <div id="previewContainer" class="mb-3" style="display: none;">
                            <img id="imagePreview" src="#" alt="Preview" class="mahasiswa-img">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSave">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            loadData();

            function loadData(query = '') {
                $.ajax({
                    url: 'proses.php',
                    method: 'POST',
                    data: {
                        action: 'load',
                        query: query
                    },
                    dataType: 'json',
                    success: function(response) {
                        let html = '';
                        if (response.length > 0) {
                            response.forEach(function(row) {
                                html += `<tr>
                                    <td>${escapeHtml(row.nama)}</td>
                                    <td>${escapeHtml(row.nim)}</td>
                                    <td>${escapeHtml(row.email)}</td>
                                    <td><img src="../photo/${escapeHtml(row.foto)}" class="mahasiswa-img"></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit" data-id="${row.id}">Edit</button>
                                        <button class="btn btn-sm btn-danger delete" data-id="${row.id}">Hapus</button>
                                    </td>
                                </tr>`;
                            });
                        } else {
                            html = '<tr><td colspan="5" class="text-center">Tidak ada data ditemukan</td></tr>';
                        }
                        $('#dataMahasiswa').html(html);
                    }
                });
            }

            $('#btnAdd').click(function() {
                resetForm();
                $('#mahasiswaModal').modal('show');
            });

            $('#btnSave').click(function() {
                $('#formMahasiswa').submit();
            });

            $('#formMahasiswa').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);

                $.ajax({
                    url: 'proses.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.error,
                            });
                        } else {
                            $('#mahasiswaModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: response.message,
                            });
                            loadData();
                        }
                    }
                });
            });

            $('#search').on('keyup', function() {
                var query = $(this).val();
                loadData(query);
            });

            $(document).on('click', '.edit', function() {
                var id = $(this).data('id');
                $.ajax({
                    url: 'proses.php',
                    method: 'POST',
                    data: {
                        action: 'get_data',
                        id: id
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#id').val(data.id);
                        $('#nama').val(data.nama);
                        $('#nim').val(data.nim);
                        $('#email').val(data.email);
                        if (data.foto) {
                            $('#previewContainer').show();
                            $('#imagePreview').attr('src', `../photo/${data.foto}`);
                        } else {
                            $('#previewContainer').hide();
                        }
                        $('#mahasiswaModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.delete', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda tidak dapat mengembalikan data ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
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
                            dataType: 'json',
                            success: function(response) {
                                if (response.error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.error,
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Sukses',
                                        text: response.message,
                                    });
                                    loadData();
                                }
                            }
                        });
                    }
                });
            });

            $('#foto').change(function() {
                previewImage(this);
            });

            function resetForm() {
                $('#formMahasiswa')[0].reset();
                $('#id').val('');
                $('#previewContainer').hide();
                $('#imagePreview').attr('src', '#');
            }

            function previewImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewContainer').show();
                        $('#imagePreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>

</body>

</html>