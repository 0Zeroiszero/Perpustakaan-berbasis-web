/* Halaman kelola-pengguna.html. Data pengguna cuma ada di tabel, ilang kalo di-refresh */

// Munculin pop-up form kosong buat nambah pengguna baru
function bukaTambah() {
    hapusTandaEdit();

    document.getElementById("inputNama").value = "";
    document.getElementById("inputEmail").value = "";
    document.getElementById("inputRole").value = "User";
    document.getElementById("inputStatus").value = "Aktif";
    document.getElementById("modalTitle").textContent = "Tambah Pengguna";

    new bootstrap.Modal(document.getElementById("modalPengguna")).show();
}

// Munculin pop-up yang udah keisi data pengguna lama, biar bisa diubah
function editPengguna(tombol) {
    const baris = tombol.closest("tr");

    hapusTandaEdit();

    document.getElementById("inputNama").value = baris.querySelector(".kolom-nama b").textContent;
    document.getElementById("inputEmail").value = baris.querySelector(".kolom-email").textContent;
    document.getElementById("inputRole").value = baris.querySelector(".kolom-role").textContent;
    document.getElementById("inputStatus").value = baris.querySelector(".kolom-status").textContent;

    baris.setAttribute("data-editing", "ya");

    document.getElementById("modalTitle").textContent = "Edit Pengguna";
    new bootstrap.Modal(document.getElementById("modalPengguna")).show();
}

// Simpan isi form: kalau lagi mode edit, barisnya diubah; kalau bukan, nambah baris baru
function simpanPengguna() {
    const nama = document.getElementById("inputNama").value.trim();
    const email = document.getElementById("inputEmail").value.trim();
    const role = document.getElementById("inputRole").value;
    const status = document.getElementById("inputStatus").value;

    const tabel = document.getElementById("tabelPengguna");
    const barisEdit = tabel.querySelector("tr[data-editing]");

    if (barisEdit) {
        barisEdit.querySelector(".kolom-nama b").textContent = nama;
        barisEdit.querySelector(".kolom-email").textContent = email;
        barisEdit.querySelector(".kolom-role").innerHTML = badgeRole(role);
        barisEdit.querySelector(".kolom-status").innerHTML = badgeStatus(status);

        barisEdit.removeAttribute("data-editing");

        tampilkanToast("Pengguna \"" + nama + "\" berhasil diubah.");
    } else {
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

    bootstrap.Modal.getInstance(document.getElementById("modalPengguna")).hide();
    cariPengguna();
}

// Hapus pengguna dari tabel, terus hitung ulang jumlah dan kasih notifikasi
function hapusPengguna(tombol) {
    const baris = tombol.closest("tr");
    const nama = baris.querySelector(".kolom-nama b").textContent;
    baris.remove();

    cariPengguna();
    tampilkanToast("Pengguna \"" + nama + "\" dihapus dari tabel.");
}

// Cari pengguna di tabel, yang teksnya cocok sama kata kunci doang yang nampil
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

// Bersihin penanda baris yang lagi diklik buat diedit
function hapusTandaEdit() {
    const lama = document.querySelectorAll("#tabelPengguna tr[data-editing]");
    lama.forEach(function (b) {
        b.removeAttribute("data-editing");
    });
}

// Bikin label berwarna biru buat Admin dan ungu buat User
function badgeRole(role) {
    if (role === "Admin") {
        return '<span class="badge bg-biru-muda text-biru">Admin</span>';
    }
    return '<span class="badge bg-ungu-muda text-ungu">User</span>';
}

// Bikin label hijau buat yang Aktif dan abu-abu buat Nonaktif
function badgeStatus(status) {
    if (status === "Aktif") {
        return '<span class="badge bg-success-subtle text-success">Aktif</span>';
    }
    return '<span class="badge bg-secondary">Nonaktif</span>';
}

// Munculin notifikasi singkat di pojok bawah
function tampilkanToast(pesan) {
    document.getElementById("toastPenggunaMsg").textContent = pesan;
    bootstrap.Toast.getOrCreateInstance(document.getElementById("toastPengguna"), { delay: 2500 }).show();
}