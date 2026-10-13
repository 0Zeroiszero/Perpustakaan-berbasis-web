/* Halaman rak-saya.html. Data cuma disimpan di halaman, ilang kalo di-refresh */

// Hapus buku dari rak, harus lewat modal konfirmasi dulu
function hapusRak(tombol) {
    const kartu = tombol.closest(".buku-rak");
    const judul = kartu.querySelector("h5").textContent;

    konfirmasiHapus(judul, "buku dari rak").then(function (ya) {
        if (!ya) return;

        kartu.remove();

        const sisa = document.querySelectorAll(".buku-rak").length;

        document.getElementById("jumlahRak").textContent = sisa + " Buku";

        document.getElementById("rakKosong").classList.toggle("d-none", sisa !== 0);

        document.getElementById("toastRakMsg").textContent = "Buku \"" + judul + "\" dihapus dari rak.";
        bootstrap.Toast.getOrCreateInstance(document.getElementById("toastRak"), { delay: 2500 }).show();
    });
}