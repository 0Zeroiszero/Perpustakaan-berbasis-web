/* ============================================================
   kelola-buku.js
   Kode khusus untuk halaman kelola-buku.html

   Semua data buku hanya tinggal di tabel HTML (DOM).
   Tidak pakai localStorage — data akan hilang saat halaman
   dimuat ulang.
   ============================================================ */

/* ---------- Buka modal untuk menambah buku baru ---------- */
function bukaModalTambah() {
    // Lepas tanda "sedang diedit" dari baris mana pun (kalau ada)
    hapusTandaEdit();

    // Kosongkan form dan ganti judul modal
    document.getElementById("formBuku").reset();
    document.getElementById("modalBukuTitle").textContent = "Tambah Buku";

    // Buka modal
    new bootstrap.Modal(document.getElementById("modalBuku")).show();
}

/* ---------- Buka modal untuk mengedit buku ---------- */
// tombol = tombol pensil yang diklik
function editBuku(tombol) {
    // Cari baris (<tr>) tempat tombol berada
    const baris = tombol.closest("tr");

    // Lepas tanda edit dari baris lain (kalau ada)
    hapusTandaEdit();

    // Isi form dengan data dari kolom-kolom baris
    document.getElementById("inputJudul").value = baris.querySelector(".kolom-judul b").textContent;
    document.getElementById("inputPenulis").value = baris.querySelector(".kolom-penulis").textContent;
    document.getElementById("inputKategori").value = baris.querySelector(".kolom-kategori").textContent;
    document.getElementById("inputPenerbit").value = baris.querySelector(".kolom-penerbit").textContent;
    document.getElementById("inputTahun").value = baris.querySelector(".kolom-tahun").textContent;
    document.getElementById("inputHalaman").value = baris.querySelector(".kolom-halaman").textContent;
    document.getElementById("inputStok").value = baris.querySelector(".kolom-stok").textContent;
    document.getElementById("inputSinopsis").value = baris.dataset.sinopsis || "";

    // Tandai baris ini sebagai baris yang sedang diedit
    baris.setAttribute("data-editing", "ya");

    // Ganti judul modal, lalu buka modal
    document.getElementById("modalBukuTitle").textContent = "Edit Buku";
    new bootstrap.Modal(document.getElementById("modalBuku")).show();
}

/* ---------- Simpan buku (tombol Simpan di modal) ---------- */
function simpanBuku(event) {
    event.preventDefault(); // hentikan reload halaman

    // Baca semua isi form
    const judul = document.getElementById("inputJudul").value.trim();
    const penulis = document.getElementById("inputPenulis").value.trim();
    const kategori = document.getElementById("inputKategori").value;
    const penerbit = document.getElementById("inputPenerbit").value.trim();
    const tahun = document.getElementById("inputTahun").value;
    const halaman = document.getElementById("inputHalaman").value;
    const stok = parseInt(document.getElementById("inputStok").value, 10) || 0;
    const sinopsis = document.getElementById("inputSinopsis").value.trim();

    // Cari baris yang sedang diedit (kalau ada)
    const tabel = document.getElementById("tabelBukuAdmin");
    const barisEdit = tabel.querySelector("tr[data-editing]");

    if (barisEdit) {
        // ----- UBAH baris yang sudah ada -----
        barisEdit.querySelector(".kolom-judul b").textContent = judul;
        barisEdit.querySelector(".kolom-judul div").textContent = sinopsis;
        barisEdit.querySelector(".kolom-judul div").setAttribute("title", sinopsis);
        barisEdit.querySelector(".kolom-penulis").textContent = penulis;
        barisEdit.querySelector(".kolom-kategori").textContent = kategori;
        barisEdit.querySelector(".kolom-penerbit").textContent = penerbit;
        barisEdit.querySelector(".kolom-tahun").textContent = tahun;
        barisEdit.querySelector(".kolom-halaman").textContent = halaman;
        barisEdit.querySelector(".kolom-stok").textContent = stok;
        barisEdit.querySelector(".kolom-status").innerHTML = statusBuku(stok);
        barisEdit.dataset.sinopsis = sinopsis;

        // Lepas tanda edit
        barisEdit.removeAttribute("data-editing");

        tampilkanToast("Buku \"" + judul + "\" berhasil diubah.");
    } else {
        // ----- TAMBAH baris baru ke tabel -----
        const tr = document.createElement("tr");
        tr.className = "baris-buku";
        tr.dataset.sinopsis = sinopsis;
        tr.innerHTML =
            '<td class="kolom-judul">' +
                '<b>' + esc(judul) + '</b>' +
                '<div class="small text-secondary text-truncate" style="max-width:260px" title="' + escAttr(sinopsis) + '">' +
                    esc(sinopsis) +
                '</div>' +
            '</td>' +
            '<td class="kolom-penulis">' + esc(penulis) + '</td>' +
            '<td class="kolom-kategori">' + esc(kategori) + '</td>' +
            '<td class="kolom-penerbit">' + esc(penerbit) + '</td>' +
            '<td class="kolom-tahun">' + esc(tahun) + '</td>' +
            '<td class="kolom-halaman">' + esc(halaman) + '</td>' +
            '<td class="kolom-stok">' + stok + '</td>' +
            '<td class="kolom-status">' + statusBuku(stok) + '</td>' +
            '<td class="text-nowrap">' +
                '<button class="btn btn-sm btn-light" title="Ubah" onclick="editBuku(this)"><i class="bi bi-pencil"></i></button> ' +
                '<button class="btn btn-sm btn-light text-danger" title="Hapus" onclick="hapusBuku(this)"><i class="bi bi-trash"></i></button>' +
            '</td>';
        tabel.appendChild(tr);

        tampilkanToast("Buku \"" + judul + "\" berhasil ditambahkan.");
    }

    // Tutup modal, kosongkan form, lalu hitung ulang jumlah buku
    bootstrap.Modal.getInstance(document.getElementById("modalBuku")).hide();
    document.getElementById("formBuku").reset();
    cariBukuAdmin();
}

/* ---------- Hapus buku (tombol sampah) ---------- */
function hapusBuku(tombol) {
    // Cari baris tempat tombol berada, lalu hapus baris itu
    const baris = tombol.closest("tr");
    const judul = baris.querySelector(".kolom-judul b").textContent;
    baris.remove();

    // Hitung ulang jumlah + tampilkan pesan
    cariBukuAdmin();
    tampilkanToast("Buku \"" + judul + "\" dihapus dari tabel.");
}

/* ---------- Pencarian buku di halaman ini ---------- */
function cariBukuAdmin() {
    const kata = document.getElementById("cariBukuAdmin").value.toLowerCase();
    const baris = document.querySelectorAll("#tabelBukuAdmin .baris-buku");

    let tampil = 0;
    baris.forEach(function (b) {
        // Tampilkan baris kalau teksnya mengandung kata kunci
        if (b.textContent.toLowerCase().includes(kata)) {
            b.classList.remove("d-none");
            tampil++;
        } else {
            b.classList.add("d-none");
        }
    });

    // Perbarui badge jumlah buku
    document.getElementById("jumlahBukuAdmin").textContent = tampil + " Buku";

    // Tampilkan pesan kosong kalau tidak ada yang cocok
    document.getElementById("tabelBukuKosong").classList.toggle("d-none", tampil !== 0);
}

/* ---------- Helper (fungsi pembantu) ---------- */

// Lepas tanda data-editing dari semua baris
function hapusTandaEdit() {
    const lama = document.querySelectorAll("#tabelBukuAdmin tr[data-editing]");
    lama.forEach(function (b) {
        b.removeAttribute("data-editing");
    });
}

// Tentukan badge status dari jumlah stok
function statusBuku(stok) {
    if (stok > 0) {
        return '<span class="badge bg-success-subtle text-success">Tersedia</span>';
    }
    return '<span class="badge bg-danger-subtle text-danger">Habis</span>';
}

// Tampilkan toast pesan singkat
function tampilkanToast(pesan) {
    document.getElementById("toastAdminMsg").textContent = pesan;
    bootstrap.Toast.getOrCreateInstance(document.getElementById("toastAdmin"), { delay: 2500 }).show();
}
