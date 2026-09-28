document.addEventListener("DOMContentLoaded", function () {
    const login = document.getElementById("loginForm");
    if (login) {
        login.addEventListener("submit", function (e) {
            e.preventDefault();
            const role = document.getElementById("role").value;
            window.location.href = (role === "admin") ? "dashboard-admin.html" : "dashboard-user.html";
        });
    }

    const search = document.getElementById("searchForm");
    if (search) {
        search.addEventListener("submit", function (e) {
            e.preventDefault();

            window.location.href = "katalog.html";
        });
    }

    // ============================
    // SIDEBAR TOGGLE + BACKDROP
    // (Tutup hanya saat klik di luar sidebar)
    // ============================
    const menu = document.getElementById("mobileMenu");
    const sidebar = document.getElementById("sidebar");

    if (menu && sidebar) {
        // Buat backdrop otomatis kalau belum ada
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

        // Tombol hamburger → buka/tutup
        menu.addEventListener("click", function (e) {
            e.stopPropagation();
            toggleSidebar();
        });

        // Klik backdrop (di luar sidebar) → tutup
        backdrop.addEventListener("click", closeSidebar);

        // Klik link di dalam sidebar → auto close (mobile)
        sidebar.querySelectorAll(".nav-link, a").forEach(function (link) {
            link.addEventListener("click", function () {
                if (window.innerWidth < 992) closeSidebar();
            });
        });

        // ESC → tutup
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && sidebar.classList.contains("show")) closeSidebar();
        });

        // Resize ke desktop → reset
        window.addEventListener("resize", function () {
            if (window.innerWidth >= 992) closeSidebar();
        });
    }

    const logout = document.querySelectorAll(".logout");
    logout.forEach(function (button) {
        button.addEventListener("click", function () {
            window.location.href = "login.html";
        });
    });
});

let bukuTerpilih = "";
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

function filterKategori(kategori, btn) {
    document.querySelectorAll(".category-btn").forEach(x => x.classList.remove("active"));
    btn.classList.add("active");
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

function updateJumlah(j) { document.getElementById("jumlahBuku").textContent = j + " Buku"; }

function showToast(judul, customMessage) {
    const msgEl = document.getElementById("toastMsg");
    msgEl.textContent = customMessage
        ? customMessage
        : `Buku "${judul}" dipilih untuk dipinjam.`;

  const el = document.getElementById("toastPinjam");
    const t = new bootstrap.Toast(el, { delay: 3000 });
  t.show();
}


function pinjamBuku(judul) { showToast(judul); }

function pinjamDariModal() {
  const modalEl = document.getElementById("modalDetail");
  const m = bootstrap.Modal.getInstance(modalEl);
    if (m) m.hide();
  showToast(bukuTerpilih);
}

function tambahKeRak(judul) {
    showToast(judul, `Buku "${judul}" berhasil ditambahkan ke rak buku!`);
}

const searchInputEl = document.getElementById("searchInput");
if (searchInputEl) {
    searchInputEl.addEventListener("keypress", e => { if (e.key === "Enter") cariBuku(); });
}

function sortBuku() {
    const grid = document.getElementById("bookGrid");
    if (!grid) return;
    const items = Array.from(document.querySelectorAll(".book-item"));
    const mode = document.getElementById("sortSelect").value;
    items.sort((a, b) => {
        if (mode === "az") return a.dataset.judul.localeCompare(b.dataset.judul);
        if (mode === "rating") return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        return 0;
    });
    items.forEach(i => grid.appendChild(i));
}

/* ================================
   DASHBOARD ADMIN - TAMBAH & EDIT BUKU
   ================================ */
let barisBukuDiedit = null;

function bukaFormTambah() {
    barisBukuDiedit = null;
    const form = document.getElementById("bookForm");
    if (!form) return;

    form.reset();
    document.getElementById("stokBuku").value = 1;
    document.getElementById("modalBukuLabel").textContent = "Tambah Buku";
}

function editBuku(button) {
    const row = button.closest("tr");
    if (!row) return;

    barisBukuDiedit = row;
    document.getElementById("judulBuku").value = row.querySelector(".book-title").textContent.trim();
    document.getElementById("penulisBuku").value = row.querySelector(".book-author").textContent.trim();
    document.getElementById("kategoriBuku").value = row.querySelector(".book-category").textContent.trim();
    document.getElementById("stokBuku").value = row.querySelector(".book-stock").textContent.trim();
    document.getElementById("modalBukuLabel").textContent = "Edit Buku";

    const modalEl = document.getElementById("modalBuku");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function buatStatus(stok) {
    if (stok > 0) {
        return `<span class="badge bg-success-subtle text-success status-badge">Tersedia</span>`;
    }
    return `<span class="badge bg-danger-subtle text-danger status-badge">Habis</span>`;
}

function buatIdBuku(judul) {
    return judul.toLowerCase().trim().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
}

function buatBarisBuku(judul, penulis, kategori, stok) {
    const row = document.createElement("tr");
    row.dataset.id = buatIdBuku(judul);
    row.innerHTML = `
        <td class="book-title"><b>${escapeHtml(judul)}</b></td>
        <td class="book-author">${escapeHtml(penulis)}</td>
        <td class="book-category">${escapeHtml(kategori)}</td>
        <td class="book-stock">${stok}</td>
        <td>${buatStatus(stok)}</td>
        <td>
            <button class="btn btn-sm btn-light btn-edit-buku" type="button" title="Edit buku" onclick="editBuku(this)">
                <i class="bi bi-pencil"></i>
            </button>
        </td>
    `;
    return row;
}

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value;
    return div.innerHTML;
}

function updateTotalBuku(tambah = 0) {
    const stat = document.querySelector(".stat-number");
    if (!stat || !tambah) return;

    const angka = parseInt(stat.textContent.replace(/\D/g, ""), 10) || 0;
    stat.textContent = (angka + tambah).toLocaleString("id-ID");
}

const bookForm = document.getElementById("bookForm");
if (bookForm) {
    bookForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const judul = document.getElementById("judulBuku").value.trim();
        const penulis = document.getElementById("penulisBuku").value.trim();
        const kategori = document.getElementById("kategoriBuku").value;
        const stok = Math.max(0, parseInt(document.getElementById("stokBuku").value, 10) || 0);

        if (!judul || !penulis || !kategori) return;

        if (barisBukuDiedit) {
            barisBukuDiedit.querySelector(".book-title").innerHTML = `<b>${escapeHtml(judul)}</b>`;
            barisBukuDiedit.querySelector(".book-author").textContent = penulis;
            barisBukuDiedit.querySelector(".book-category").textContent = kategori;
            barisBukuDiedit.querySelector(".book-stock").textContent = stok;
            barisBukuDiedit.querySelector(".status-badge").outerHTML = buatStatus(stok);
            barisBukuDiedit.dataset.id = buatIdBuku(judul);
        } else {
            const tbody = document.querySelector(".table-card tbody");
            if (!tbody) return;
            tbody.appendChild(buatBarisBuku(judul, penulis, kategori, stok));
            updateTotalBuku(1);
        }

        const modalEl = document.getElementById("modalBuku");
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        bookForm.reset();
        barisBukuDiedit = null;
    });
}
