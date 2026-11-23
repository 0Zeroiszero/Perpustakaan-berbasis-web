/* Halaman admin/books.php. Data disimpan ke database lewat form POST. */

// Nonaktifkan/aktifkan buku lewat modal konfirmasi, lalu kirim form ke server.
// Soft-delete: buku tidak dihapus keras, hanya is_active (koleksi & progres tetap utuh).
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

// Cari buku di tabel yang sudah dirender PHP (tanpa reload)
function cariBukuAdmin() {
    const kata = document.getElementById("cariBukuAdmin").value.toLowerCase();
    const baris = document.querySelectorAll("#tabelBukuAdmin .baris-buku");

    let tampil = 0;
    baris.forEach(function (b) {
        if (b.textContent.toLowerCase().includes(kata)) {
            b.classList.remove("d-none");
            tampil++;
        } else {
            b.classList.add("d-none");
        }
    });

    document.getElementById("jumlahBukuAdmin").textContent = tampil + " Buku";
    document.getElementById("tabelBukuKosong").classList.toggle("d-none", tampil !== 0);
}
