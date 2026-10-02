/* ============================================================
   PustakaDigital — assets/js/script.js
   JavaScript UMUM yang dipakai semua halaman.
      (Proyek ini front-end only — tanpa localStorage)
   
      Fitur per halaman ada di file terpisah, misalnya:
      - dashboard-admin.html  → assets/js/dashboard-admin.js
      - kelola-buku.html      → assets/js/kelola-buku.js
      - kelola-pengguna.html  → assets/js/kelola-pengguna.js
      - rak-saya.html         → assets/js/rak-saya.js
   ============================================================ */

/* ============================ UTIL ============================ */

// Escape teks agar aman dimasukkan lewat innerHTML
function esc(t) {
    const d = document.createElement("div");
    d.textContent = (t === null || t === undefined) ? "" : String(t);
    return d.innerHTML;
}

// Escape teks untuk dipakai di dalam atribut HTML (tanda kutip ikut di-escape)
function escAttr(t) {
    return esc(t).replace(/"/g, "&quot;");
}

/* ======================= UMUM (semua halaman) ======================= */

document.addEventListener("DOMContentLoaded", function () {
    // --- Form login (route/login.html) ---
    const login = document.getElementById("loginForm");
    if (login) {
        login.addEventListener("submit", function (e) {
            e.preventDefault();
            const role = document.getElementById("role").value;
            window.location.href = (role === "admin") ? "dashboard-admin.html" : "dashboard-user.html";
        });
    }

    // --- Form pencarian di beranda (index.html) ---
    const search = document.getElementById("searchForm");
    if (search) {
        search.addEventListener("submit", function (e) {
            e.preventDefault();
            window.location.href = "route/katalog.html";
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

    // --- Tombol keluar ---
    document.querySelectorAll(".logout").forEach(function (button) {
        button.addEventListener("click", function () {
            window.location.href = "login.html";
        });
    });

    // --- Enter pada pencarian katalog ---
    const searchInputEl = document.getElementById("searchInput");
    if (searchInputEl) {
        searchInputEl.addEventListener("keypress", function (e) {
            if (e.key === "Enter") cariBuku();
        });
    }

    // --- Ikon search navbar (katalog.html): fokuskan kolom agar keyboard langsung muncul ---
    document.querySelectorAll('a[href="#searchInput"]').forEach(function (icon) {
        icon.addEventListener("click", function () {
            const target = document.getElementById("searchInput");
            if (target) { target.focus(); }
        });
    });
});

/* ============================ KATALOG ============================ */

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
