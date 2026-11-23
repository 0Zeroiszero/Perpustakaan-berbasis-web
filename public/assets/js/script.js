/* JS umum — dipakai di semua halaman. Pencarian/filter sepenuhnya PHP. */

// Nyalakan menu sidebar mobile dan modal cari.
document.addEventListener("DOMContentLoaded", function () {
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

        menu.addEventListener("click", function (e) {
            e.stopPropagation();
            sidebar.classList.contains("show") ? closeSidebar() : openSidebar();
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

    // Modal cari Bootstrap tidak menghormati autofocus HTML; fokuskan manual.
    const modalCari = document.getElementById("modalCari");
    if (modalCari) {
        modalCari.addEventListener("shown.bs.modal", function () {
            document.getElementById("searchInput").focus();
        });
    }
});

// KONFIRMASI HAPUS — modal tunggal dipakai bersama semua halaman.
let modalKonfirmasi = null;
let elKonfirmasi = null;
let jawabKonfirmasi = null;

function konfirmasiHapus(nama, jenis, opsi) {
    opsi = opsi || {};
    if (!modalKonfirmasi) {
        elKonfirmasi = buatModalKonfirmasi();
        modalKonfirmasi = new bootstrap.Modal(elKonfirmasi);
    }

    elKonfirmasi.querySelector(".konfirmasi-judul").textContent = opsi.judul || ("Hapus " + jenis + "?");
    elKonfirmasi.querySelector(".konfirmasi-nama").textContent = nama;
    elKonfirmasi.querySelector(".konfirmasi-pesan").innerHTML = opsi.pesan ||
        "Item berikut akan dihapus dan <b>tidak bisa dibatalkan</b>:";
    const tombolYa = elKonfirmasi.querySelector(".konfirmasi-ya");
    tombolYa.className = "konfirmasi-ya " + (opsi.kelas || "btn btn-hapus");
    tombolYa.textContent = opsi.tombol || "Ya, Hapus";
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
                    '<p class="mb-1 konfirmasi-pesan">Item berikut akan dihapus dan <b>tidak bisa dibatalkan</b>:</p>' +
                    '<p class="mb-0 fw-semibold konfirmasi-nama"></p>' +
                '</div>' +
                '<div class="modal-footer border-0 pt-0">' +
                    '<button type="button" class="btn btn-light border konfirmasi-batal" data-bs-dismiss="modal">Batal</button>' +
                    '<button type="button" class="btn btn-hapus konfirmasi-ya">Ya, Hapus</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    document.body.appendChild(el);

    el.querySelector(".konfirmasi-ya").addEventListener("click", function () {
        tutupKonfirmasi(true);
        bootstrap.Modal.getInstance(el).hide();
    });
    el.addEventListener("hidden.bs.modal", function () { tutupKonfirmasi(false); });
    el.addEventListener("shown.bs.modal", function () { el.querySelector(".konfirmasi-batal").focus(); });
    return el;
}

function tutupKonfirmasi(jawab) {
    const fn = jawabKonfirmasi;
    jawabKonfirmasi = null;
    if (fn) fn(jawab);
}

// Baca Ulang: konfirmasi dulu karena progres akan direset ke halaman 1.
function bacaUlang(tombol) {
    const form = tombol.closest("form");

    konfirmasiHapus(tombol.dataset.judul, "bacaan", {
        judul: "Baca ulang buku?",
        pesan: "Progres bacaan akan direset ke halaman 1 dan status menjadi <b>Sedang dibaca</b>:",
        tombol: "Ya, Baca Ulang",
        kelas: "btn-ungu"
    }).then(function (ya) {
        if (ya) form.submit();
    });
}
