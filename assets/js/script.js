/* JS umum — dipakai di semua halaman */

// Nyalakan semua tombol dan menu yang ada di tiap halaman
document.addEventListener("DOMContentLoaded", function () {
    const search = document.getElementById("searchForm");
    if (search) {
        search.addEventListener("submit", function (e) {
            const input = search.querySelector("input");
            if (!input) return; // form tanpa input: jangan blokir submit
            e.preventDefault();
            const kata = input.value.trim();
            window.location.href = "route/katalog.php" + (kata ? "?q=" + encodeURIComponent(kata) : "");
        });
    }

    const menu = document.getElementById("mobileMenu");
    const sidebar = document.getElementById("sidebar");

    if (menu && sidebar) {
        let backdrop = document.getElementById("sidebarBackdrop");
        if (!backdrop) {
            backdrop = document.createElement("div");
            backdrop.id = "sidebarBackdrop";
            backdrop.className = "sidebar-backdrop";
            document.body.appendChild(backdrop);
        }

        function openSidebar() {
            sidebar.classList.add("show");
            backdrop.classList.add("show");
            document.body.style.overflow = "hidden";
        }

        function closeSidebar() {
            sidebar.classList.remove("show");
            backdrop.classList.remove("show");
            document.body.style.overflow = "";
        }

        function toggleSidebar() {
            sidebar.classList.contains("show") ? closeSidebar() : openSidebar();
        }

        menu.addEventListener("click", function (e) {
            e.stopPropagation();
            toggleSidebar();
        });

        backdrop.addEventListener("click", closeSidebar);

        sidebar.querySelectorAll(".nav-link, a").forEach(function (link) {
            link.addEventListener("click", function () {
                if (window.innerWidth < 992) closeSidebar();
            });
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && sidebar.classList.contains("show")) closeSidebar();
        });

        window.addEventListener("resize", function () {
            if (window.innerWidth >= 992) closeSidebar();
        });
    }

    const searchInputEl = document.getElementById("searchInput");
    if (searchInputEl) {
        searchInputEl.addEventListener("keydown", function (e) {
            if (e.key === "Enter") cariBuku();
        });

        // Terapkan pencarian/filter yang dikirim dari beranda
        const params = new URLSearchParams(window.location.search);
        const kategori = params.get("kategori");
        const kata = params.get("q");
        if (kategori) {
            const btn = Array.prototype.find.call(document.querySelectorAll(".category-btn"),
                function (b) { return b.textContent.trim() === kategori; });
            if (btn) filterKategori(kategori, btn);
        } else if (kata) {
            searchInputEl.value = kata;
            cariBuku();
        }
    }

    // Panel cari katalog hidup di modal: fokuskan kolomnya begitu kebuka (autofocus HTML
    // tidak dipakai karena tidak berefek di modal Bootstrap)
    const modalCari = document.getElementById("modalCari");
    if (modalCari) {
        modalCari.addEventListener("shown.bs.modal", function () {
            document.getElementById("searchInput").focus();
        });
    }
});

// Cari buku di katalog, yang judul/penulisnya cocok doang yang nampil
function cariBuku() {
    const keyword = document.getElementById("searchInput").value.toLowerCase().trim();
    const books = document.querySelectorAll(".book-item");
    let jumlah = 0;
    books.forEach(b => {
        const data = b.getAttribute("data-search").toLowerCase();
        if (data.includes(keyword)) { b.classList.remove("d-none"); jumlah++; } else { b.classList.add("d-none"); }
    });
    updateJumlah(jumlah);
    document.getElementById("tidakDitemukan").classList.toggle("d-none", jumlah !== 0);
}

// Saring buku sesuai kategori yang diklik, terus hitung ulang jumlahnya
function filterKategori(kategori, btn) {
    document.querySelectorAll(".category-btn").forEach(function (x) {
        x.classList.remove("active");
        x.setAttribute("aria-pressed", "false");
    });
    btn.classList.add("active");
    btn.setAttribute("aria-pressed", "true");
    const books = document.querySelectorAll(".book-item");
    let jumlah = 0;
    books.forEach(b => {
        const kat = b.getAttribute("data-kategori");
        if (kategori === "Semua" || kat === kategori) { b.classList.remove("d-none"); jumlah++; } else { b.classList.add("d-none"); }
    });
    document.getElementById("searchInput").value = "";
    updateJumlah(jumlah);
    document.getElementById("tidakDitemukan").classList.toggle("d-none", jumlah !== 0);
}

// Update tulisan jumlah buku yang muncul di atas daftar
function updateJumlah(j) { document.getElementById("jumlahBuku").textContent = j + " Buku"; }

// KONFIRMASI HAPUS — modal tunggal dipakai bersama semua halaman.
// WAJIB dipakai sebelum menghapus apa pun yang nggak bisa dibatalkan.
// Pakai: konfirmasiHapus("Atomic Habits", "buku").then(function (ya) { if (ya) { ...hapus... } });
let modalKonfirmasi = null;
let elKonfirmasi = null;
let jawabKonfirmasi = null;

function konfirmasiHapus(nama, jenis) {
    if (!modalKonfirmasi) {
        elKonfirmasi = buatModalKonfirmasi();
        modalKonfirmasi = new bootstrap.Modal(elKonfirmasi);
    }

    elKonfirmasi.querySelector(".konfirmasi-judul").textContent = "Hapus " + jenis + "?";
    elKonfirmasi.querySelector(".konfirmasi-nama").textContent = nama;
    modalKonfirmasi.show();

    return new Promise(function (resolve) { jawabKonfirmasi = resolve; });
}

function buatModalKonfirmasi() {
    const el = document.createElement("div");
    el.className = "modal fade";
    el.id = "modalKonfirmasiHapus";
    el.tabIndex = -1;
    el.setAttribute("aria-labelledby", "konfirmasiHapusJudul");
    el.setAttribute("aria-hidden", "true");
    el.innerHTML =
        '<div class="modal-dialog modal-dialog-centered">' +
            '<div class="modal-content" style="border-radius:var(--radius-lg)">' +
                '<div class="modal-header border-0 pb-0">' +
                    '<h2 class="modal-title h5 fw-bold" id="konfirmasiHapusJudul">' +
                        '<i class="bi bi-exclamation-triangle text-danger me-2" aria-hidden="true"></i>' +
                        '<span class="konfirmasi-judul"></span>' +
                    '</h2>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>' +
                '</div>' +
                '<div class="modal-body">' +
                    '<p class="mb-1">Item berikut akan dihapus dan <b>tidak bisa dibatalkan</b>:</p>' +
                    '<p class="mb-0 fw-semibold konfirmasi-nama"></p>' +
                '</div>' +
                '<div class="modal-footer border-0 pt-0">' +
                    '<button type="button" class="btn btn-light border konfirmasi-batal" data-bs-dismiss="modal">Batal</button>' +
                    '<button type="button" class="btn btn-hapus konfirmasi-ya">Ya, Hapus</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    document.body.appendChild(el);

    // "Ya" menjawab dulu lalu modal ditutup; Esc atau klik di luar dianggap Batal
    el.querySelector(".konfirmasi-ya").addEventListener("click", function () {
        tutupKonfirmasi(true);
        bootstrap.Modal.getInstance(el).hide();
    });
    el.addEventListener("hidden.bs.modal", function () { tutupKonfirmasi(false); });
    // Fokus default ke "Batal" supayakeyboard/tekan Enter tidak jatuh ke aksi merusak
    el.addEventListener("shown.bs.modal", function () { el.querySelector(".konfirmasi-batal").focus(); });
    return el;
}

function tutupKonfirmasi(jawab) {
    const fn = jawabKonfirmasi;
    jawabKonfirmasi = null;
    if (fn) fn(jawab);
}

// Urutin buku sesuai pilihan: default (urutan terbaru dari server) atau judul A-Z
function sortBuku() {
    const grid = document.getElementById("bookGrid");
    if (!grid) return;
    const items = Array.from(document.querySelectorAll(".book-item"));
    const mode = document.getElementById("sortSelect").value;
    items.sort((a, b) => {
        if (mode === "az") return a.dataset.judul.localeCompare(b.dataset.judul);
        return 0;
    });
    items.forEach(i => grid.appendChild(i));
}