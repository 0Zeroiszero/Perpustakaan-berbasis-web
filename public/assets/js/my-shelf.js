/* Halaman rak-saya.php. Penghapusan dikirim ke server lewat form POST. */

function hapusRak(tombol) {
    const form = tombol.closest("form");

    konfirmasiHapus(tombol.dataset.judul, "buku dari rak").then(function (ya) {
        if (ya) form.submit();
    });
}
