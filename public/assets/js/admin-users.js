/* Halaman admin/users.php: konfirmasi status dan salin password sementara. */

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

function salinPassword(tombol) {
    const input = document.getElementById("passwordBaru");
    if (!input) return;

    const selesai = function () {
        const teks = tombol.textContent;
        tombol.textContent = "Tersalin";
        setTimeout(function () { tombol.textContent = teks; }, 1500);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(selesai);
    } else {
        input.select();
        document.execCommand("copy");
        selesai();
    }
}
