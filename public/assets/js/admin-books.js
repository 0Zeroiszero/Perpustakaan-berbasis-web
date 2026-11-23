/* Halaman admin/books.php: konfirmasi nonaktifkan/aktifkan buku. Pencarian dilakukan PHP. */

function toggleBuku(tombol) {
    const form = tombol.closest("form");
    const nonaktif = tombol.dataset.ke === "0";

    konfirmasiHapus(tombol.dataset.judul, "buku", nonaktif ? {
        judul: "Nonaktifkan buku?",
        pesan: "Buku akan disembunyikan dari katalog dan streaming. Koleksi & progres pembaca tetap utuh:",
        tombol: "Ya, Nonaktifkan"
    } : {
        judul: "Aktifkan buku?",
        pesan: "Buku akan tampil kembali di katalog dan bisa dibaca:",
        tombol: "Ya, Aktifkan"
    }).then(function (ya) {
        if (ya) form.submit();
    });
}
