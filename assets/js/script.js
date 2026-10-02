/* JS umum — dipakai di semua halaman */

// Membersihkan teks yang diketik user biar aman&display di halaman
function esc(t) {
    const d = document.createElement("div");
    d.textContent = (t === null || t === undefined) ? "" : String(t);
    return d.innerHTML;
}

// Sama seperti di atas, tapi buat teks yang masuk ke dalam tanda kutip
function escAttr(t) {
    return esc(t).replace(/"/g, "&quot;");
}

// Nyalakan semua tombol dan menu yang ada di tiap halaman
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
            window.location.href = "route/katalog.html";
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

    document.querySelectorAll(".logout").forEach(function (button) {
        button.addEventListener("click", function () {
            window.location.href = "login.html";
        });
    });

    const searchInputEl = document.getElementById("searchInput");
    if (searchInputEl) {
        searchInputEl.addEventListener("keypress", function (e) {
            if (e.key === "Enter") cariBuku();
        });
    }

    document.querySelectorAll('a[href="#searchInput"]').forEach(function (icon) {
        icon.addEventListener("click", function () {
            const target = document.getElementById("searchInput");
            if (target) { target.focus(); }
        });
    });
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

// Update tulisan jumlah buku yang muncul di atas daftar
function updateJumlah(j) { document.getElementById("jumlahBuku").textContent = j + " Buku"; }

// Munculin notifikasi kecil di pojok bawah selama 3 detik
function showToast(judul, customMessage) {
    const msgEl = document.getElementById("toastMsg");
    msgEl.textContent = customMessage
        ? customMessage
        : `Buku "${judul}" dipilih untuk dipinjam.`;

    const el = document.getElementById("toastPinjam");
    const t = new bootstrap.Toast(el, { delay: 3000 });
    t.show();
}

// Dipanggil waktu tombol Pinjam diklik, inti cuma nampilin notifikasi
function pinjamBuku(judul) { showToast(judul); }

// Urutin buku sesuai pilihan: judul A-Z atau rating paling tinggi
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