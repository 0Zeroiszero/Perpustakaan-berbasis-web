/* Halaman rak-saya.html. Data cuma disimpan di halaman, ilang kalo di-refresh */

// Hapus buku dari rak, terus update jumlah buku dan kasih notifikasi
function hapusRak(tombol) {
    const kartu = tombol.closest(".buku-rak");
    const judul = kartu.querySelector("h5").textContent;

    kartu.remove();

    const sisa = document.querySelectorAll(".buku-rak").length;

    document.getElementById("jumlahRak").textContent = sisa + " Buku";

    document.getElementById("rakKosong").classList.toggle("d-none", sisa !== 0);

    document.getElementById("toastRakMsg").textContent = "Buku \"" + judul + "\" dihapus dari rak.";
    bootstrap.Toast.getOrCreateInstance(document.getElementById("toastRak"), { delay: 2500 }).show();
}