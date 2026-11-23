/* Halaman admin/users.php. Status akun dikirim ke server lewat form POST. */

// Nonaktifkan/aktifkan akun lewat modal konfirmasi, lalu kirim form ke server.
function toggleUser(tombol) {
    const form = tombol.closest("form");
    const nonaktif = tombol.dataset.ke === "0";

    konfirmasiHapus(tombol.dataset.nama, "akun", nonaktif ? {
        judul: "Nonaktifkan akun?",
        pesan: "Akun akan ditolak saat login. Koleksi & progres bacanya tetap utuh:",
        tombol: "Ya, Nonaktifkan"
    } : {
        judul: "Aktifkan akun?",
        pesan: "Akun akan bisa login kembali:",
        tombol: "Ya, Aktifkan"
    }).then(function (ya) {
        if (ya) form.submit();
    });
}

// Cari pengguna di tabel yang sudah dirender PHP (tanpa reload)
function cariUserAdmin() {
    const kata = document.getElementById("cariUserAdmin").value.toLowerCase();
    const baris = document.querySelectorAll("#tabelUserAdmin .baris-user");

    let tampil = 0;
    baris.forEach(function (b) {
        if (b.textContent.toLowerCase().includes(kata)) {
            b.classList.remove("d-none");
            tampil++;
        } else {
            b.classList.add("d-none");
        }
    });

    document.getElementById("jumlahUserAdmin").textContent = tampil + " Pengguna";
}
