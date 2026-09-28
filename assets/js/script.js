document.addEventListener("DOMContentLoaded", function () {
    const login = document.getElementById("loginForm");
    if (login) {
        login.addEventListener("submit", function (e) {
            e.preventDefault();
            const role = document.getElementById("role").value;
            if (role === "admin") {
                window.location.href = "dashboard-admin.html";
            } else {
                window.location.href = "dashboard-user.html";
            }
        });
    }

    const search = document.getElementById("searchForm");
    if (search) {
        search.addEventListener("submit", function (e) {
            e.preventDefault();

            window.location.href = "katalog.html";
        });
    }

    const menu = document.getElementById("mobileMenu");
    const sidebar = document.getElementById("sidebar");
    if (menu && sidebar) {
        menu.addEventListener("click", function () {
            sidebar.classList.toggle("show");
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
function showToast(judul){
  document.getElementById("toastMsg").textContent = `Buku "${judul}" dipilih untuk dipinjam.`;
  const el = document.getElementById("toastPinjam");
  const t = new bootstrap.Toast(el, {delay:3000});
  t.show();
}

function pinjamBuku(judul){ 
  showToast(judul); 
}

function pinjamDariModal(){
  const modalEl = document.getElementById("modalDetail");
  const m = bootstrap.Modal.getInstance(modalEl);
  if(m) m.hide();
  showToast(bukuTerpilih);
}
document.getElementById("searchInput").addEventListener("keypress", e => { if (e.key === "Enter") cariBuku(); });

function sortBuku() {
    const grid = document.getElementById("bookGrid");
    const items = Array.from(document.querySelectorAll(".book-item"));
    const mode = document.getElementById("sortSelect").value;
    items.sort((a, b) => {
        if (mode === "az") return a.dataset.judul.localeCompare(b.dataset.judul);
        if (mode === "rating") return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        return 0;
    });
    items.forEach(i => grid.appendChild(i));
}