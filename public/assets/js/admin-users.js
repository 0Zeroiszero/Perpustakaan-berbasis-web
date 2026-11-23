/* Halaman kelola-pengguna.php. Data disimpan ke database lewat form POST. */

// Buka form kosong untuk pengguna baru
function bukaTambah() {
    const form = document.getElementById("formPengguna");
    form.reset();
    // Halaman edit menyimpan nilai lama sebagai default form; kosongkan lagi untuk mode tambah.
    form.querySelectorAll("input:not([type=hidden]), textarea").forEach(function (el) { el.value = ""; });
    form.querySelectorAll("select").forEach(function (el) { el.selectedIndex = 0; });
    document.getElementById("inputId").value = "";
    document.getElementById("inputPassword").required = true;
    document.getElementById("modalTitle").textContent = "Tambah Pengguna";

    new bootstrap.Modal(document.getElementById("modalPengguna")).show();
}

// Hapus pengguna lewat modal konfirmasi, lalu kirim form ke server
function hapusPengguna(tombol) {
    const form = tombol.closest("form");

    konfirmasiHapus(tombol.dataset.nama, "pengguna").then(function (ya) {
        if (ya) form.submit();
    });
}

// Cari pengguna di tabel yang sudah dirender PHP (tanpa reload)
function cariPengguna() {
    const kata = document.getElementById("cariPengguna").value.toLowerCase();
    const baris = document.querySelectorAll("#tabelPengguna .baris-pengguna");

    let tampil = 0;
    baris.forEach(function (b) {
        if (b.textContent.toLowerCase().includes(kata)) {
            b.classList.remove("d-none");
            tampil++;
        } else {
            b.classList.add("d-none");
        }
    });

    document.getElementById("jumlahPengguna").textContent = tampil + " Pengguna";
    document.getElementById("penggunaKosong").classList.toggle("d-none", tampil !== 0);
}
