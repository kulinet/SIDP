<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Berakhir</title>
    <!-- Include SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: sans-serif; display: none; background-color: #f5f5f9; }
    </style>
</head>
<body>
    <script>
        document.body.style.display = 'block';
        Swal.fire({
            icon: 'warning',
            title: 'Sesi Berakhir',
            text: 'Anda terlalu lama berada di halaman ini. Silakan muat ulang halaman untuk melanjutkan.',
            confirmButtonText: 'Muat Ulang',
            allowOutsideClick: false,
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // If possible, we go back so the user doesn't lose the URL they were on
                // Note: The form data will be lost, but the page will reload properly instead of showing a 403 on the action URL
                var previousUrl = document.referrer;
                if (previousUrl) {
                    window.location.href = previousUrl;
                } else {
                    window.history.back();
                }
            }
        });
    </script>
</body>
</html>
