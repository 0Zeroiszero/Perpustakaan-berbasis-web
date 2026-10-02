/* ============================================================
   rak-saya.js
   Kode khusus untuk halaman rak-saya.html

   Kartu buku di rak hanya tinggal di halaman (DOM).
   Tidak pakai localStorage — data akan hilang saat halaman
   dimuat ulang.
   ============================================================ */

/* ---------- Hapus buku dari rak (tombol "Hapus dari Rak") ---------- */
// tombol = tombol "Hapus dari Rak" yang diklik
function hapusRak(tombol) {
    // Cari kartu buku tempat tombol berada
    const kartu = tombol.closest(".buku-rak");
    const judul = kartu.querySelector("h5").textContent;

    // Hapus kartu dari halaman
    kartu.remove();

    // Hitung sisa buku di rak
    const sisa = document.querySelectorAll(".buku-rak").length;

    // Perbarui badge jumlah buku
    document.getElementById("jumlahRak").textContent = sisa + " Buku";

    // Tampilkan pesan kosong kalau rak sudah tidak ada isinya
    document.getElementById("rakKosong").classList.toggle("d-none", sisa !== 0);

    // Tampilkan toast pesan singkat
    document.getElementById("toastRakMsg").textContent = "Buku \"" + judul + "\" dihapus dari rak.";
    bootstrap.Toast.getOrCreateInstance(document.getElementById("toastRak"), { delay: 2500 }).show();
}
