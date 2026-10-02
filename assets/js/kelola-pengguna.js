/* ============================================================
   kelola-pengguna.js
   Kode khusus untuk halaman kelola-pengguna.html

   Semua data pengguna hanya tinggal di tabel HTML (DOM).
   Tidak pakai localStorage — data akan hilang saat halaman
   dimuat ulang.
   ============================================================ */

/* ---------- Buka modal untuk menambah pengguna ---------- */
function bukaTambah() {
    // Lepas tanda "sedang diedit" dari baris mana pun (kalau ada)
    hapusTandaEdit();

    // Kosongkan form dan ganti judul modal
    document.getElementById("inputNama").value = "";
    document.getElementById("inputEmail").value = "";
    document.getElementById("inputRole").value = "User";
    document.getElementById("inputStatus").value = "Aktif";
    document.getElementById("modalTitle").textContent = "Tambah Pengguna";

    // Buka modal
    new bootstrap.Modal(document.getElementById("modalPengguna")).show();
}

/* ---------- Buka modal untuk mengedit pengguna ---------- */
// tombol = tombol pensil yang diklik
function editPengguna(tombol) {
    // Cari baris (<tr>) tempat tombol berada
    const baris = tombol.closest("tr");

    // Lepas tanda edit dari baris lain (kalau ada)
    hapusTandaEdit();

    // Isi form dengan data dari kolom-kolom baris
    document.getElementById("inputNama").value = baris.querySelector(".kolom-nama b").textContent;
    document.getElementById("inputEmail").value = baris.querySelector(".kolom-email").textContent;
    document.getElementById("inputRole").value = baris.querySelector(".kolom-role").textContent;
    document.getElementById("inputStatus").value = baris.querySelector(".kolom-status").textContent;

    // Tandai baris ini sebagai baris yang sedang diedit
    baris.setAttribute("data-editing", "ya");

    // Ganti judul modal, lalu buka modal
    document.getElementById("modalTitle").textContent = "Edit Pengguna";
    new bootstrap.Modal(document.getElementById("modalPengguna")).show();
}

/* ---------- Simpan pengguna (tombol Simpan di modal) ---------- */
function simpanPengguna() {
    // Baca semua isi form
    const nama = document.getElementById("inputNama").value.trim();
    const email = document.getElementById("inputEmail").value.trim();
    const role = document.getElementById("inputRole").value;
    const status = document.getElementById("inputStatus").value;

    // Cari baris yang sedang diedit (kalau ada)
    const tabel = document.getElementById("tabelPengguna");
    const barisEdit = tabel.querySelector("tr[data-editing]");

    if (barisEdit) {
        // ----- UBAH baris yang sudah ada -----
        barisEdit.querySelector(".kolom-nama b").textContent = nama;
        barisEdit.querySelector(".kolom-email").textContent = email;
        barisEdit.querySelector(".kolom-role").innerHTML = badgeRole(role);
        barisEdit.querySelector(".kolom-status").innerHTML = badgeStatus(status);

        // Lepas tanda edit
        barisEdit.removeAttribute("data-editing");

        tampilkanToast("Pengguna \"" + nama + "\" berhasil diubah.");
    } else {
        // ----- TAMBAH baris baru ke tabel -----
        const tr = document.createElement("tr");
        tr.className = "baris-pengguna";
        tr.innerHTML =
            '<td class="kolom-nama">' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<div class="rounded-circle bg-ungu-muda text-ungu d-flex align-items-center justify-content-center" style="width:38px;height:38px"><i class="bi bi-person"></i></div>' +
                    '<b>' + esc(nama) + '</b>' +
                '</div>' +
            '</td>' +
            '<td class="kolom-email">' + esc(email) + '</td>' +
            '<td class="kolom-role">' + badgeRole(role) + '</td>' +
            '<td class="kolom-status">' + badgeStatus(status) + '</td>' +
            '<td class="kolom-dipinjam">0 Buku</td>' +
            '<td>' +
                '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-light" title="Ubah" onclick="editPengguna(this)"><i class="bi bi-pencil"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-light text-danger" title="Hapus" onclick="hapusPengguna(this)"><i class="bi bi-trash"></i></button>' +
                '</div>' +
            '</td>';
        tabel.appendChild(tr);

        tampilkanToast("Pengguna \"" + nama + "\" berhasil ditambahkan.");
    }

    // Tutup modal, lalu hitung ulang jumlah pengguna
    bootstrap.Modal.getInstance(document.getElementById("modalPengguna")).hide();
    cariPengguna();
}

/* ---------- Hapus pengguna (tombol sampah) ---------- */
function hapusPengguna(tombol) {
    // Cari baris tempat tombol berada, lalu hapus baris itu
    const baris = tombol.closest("tr");
    const nama = baris.querySelector(".kolom-nama b").textContent;
    baris.remove();

    // Hitung ulang jumlah + tampilkan pesan
    cariPengguna();
    tampilkanToast("Pengguna \"" + nama + "\" dihapus dari tabel.");
}

/* ---------- Pencarian pengguna di halaman ini ---------- */
function cariPengguna() {
    const kata = document.getElementById("cariPengguna").value.toLowerCase();
    const baris = document.querySelectorAll("#tabelPengguna .baris-pengguna");

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

    // Perbarui badge jumlah pengguna
    document.getElementById("jumlahPengguna").textContent = tampil + " Pengguna";

    // Tampilkan pesan kosong kalau tidak ada yang cocok
    document.getElementById("penggunaKosong").classList.toggle("d-none", tampil !== 0);
}

/* ---------- Helper (fungsi pembantu) ---------- */

// Lepas tanda data-editing dari semua baris
function hapusTandaEdit() {
    const lama = document.querySelectorAll("#tabelPengguna tr[data-editing]");
    lama.forEach(function (b) {
        b.removeAttribute("data-editing");
    });
}

// Tentukan badge role
function badgeRole(role) {
    if (role === "Admin") {
        return '<span class="badge bg-biru-muda text-biru">Admin</span>';
    }
    return '<span class="badge bg-ungu-muda text-ungu">User</span>';
}

// Tentukan badge status
function badgeStatus(status) {
    if (status === "Aktif") {
        return '<span class="badge bg-success-subtle text-success">Aktif</span>';
    }
    return '<span class="badge bg-secondary">Nonaktif</span>';
}

// Tampilkan toast pesan singkat
function tampilkanToast(pesan) {
    document.getElementById("toastPenggunaMsg").textContent = pesan;
    bootstrap.Toast.getOrCreateInstance(document.getElementById("toastPengguna"), { delay: 2500 }).show();
}
