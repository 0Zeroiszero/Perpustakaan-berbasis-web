/* Halaman kelola-buku.php. Data disimpan ke database lewat form POST. */

// Buka form kosong untuk buku baru
function bukaModalTambah() {
    const form = document.getElementById("formBuku");
    form.reset();
    // Halaman edit menyimpan nilai lama sebagai default form; kosongkan lagi untuk mode tambah.
    form.querySelectorAll("input:not([type=hidden]), textarea").forEach(function (el) { el.value = ""; });
    form.querySelectorAll("select").forEach(function (el) { el.selectedIndex = 0; });
    document.getElementById("inputId").value = "";
    document.getElementById("modalBukuTitle").textContent = "Tambah Buku";

    new bootstrap.Modal(document.getElementById("modalBuku")).show();
}

// Hapus buku lewat modal konfirmasi, lalu kirim form ke server
function hapusBuku(tombol) {
    const form = tombol.closest("form");

    konfirmasiHapus(tombol.dataset.judul, "buku").then(function (ya) {
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
