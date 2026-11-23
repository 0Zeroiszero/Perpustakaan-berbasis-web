<?php
// Pembaca PDF inline (PDF.js). Auto-save last_page lewat form tersembunyi ke iframe,
// tanpa AJAX dan tanpa reload (kontrak PDF: satu-satunya pengiriman tanpa pindah halaman).
require __DIR__ . '/../includes/book_helper.php';
wajib_login();
header('Cache-Control: no-store');

$slug = trim((string) ($_GET['slug'] ?? ''));
$buku = preg_match('/^[a-z0-9-]{1,220}$/', $slug)
    ? row('SELECT * FROM v_books_catalog WHERE slug = ? AND is_active = 1', [$slug])
    : null;

if (!$buku) {
    http_response_code(404);
}

$awal = 1;
if ($buku) {
    $progres = row(
        'SELECT last_page FROM reading_progress WHERE user_id = ? AND book_id = ?',
        [akun()['id'], $buku['book_id']]
    );
    $awal = max(1, min((int) ($progres['last_page'] ?? 1), (int) $buku['page_count']));
}

$judul = ($buku ? 'Baca: ' . $buku['title'] : 'Buku tidak ditemukan') . ' - PustakaDigital';
$css = ['read.css'];
$aktif = 'katalog';
$admin = akun()['role'] === 'admin';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/sidebar.php';
echo '<div class="main-with-sidebar">';
$judulTopbar = 'Baca Buku';
$subTopbar = 'Pembaca PDF dan progres otomatis.';
require __DIR__ . '/../includes/partials/app_topbar.php';
?>

<main class="container-fluid p-3 p-lg-4">
    <?php if (!$buku): ?>
        <div class="card-clean p-5 text-center">
            <i class="bi bi-search" style="font-size:3rem;color:var(--abu)" aria-hidden="true"></i>
            <h1 class="page-title mt-3">Buku tidak ditemukan</h1>
            <p class="text-secondary">Buku yang kamu cari mungkin sudah dinonaktifkan dari katalog.</p>
            <a href="<?= url('catalog.php') ?>" class="btn btn-ungu mt-2">Kembali ke Katalog</a>
        </div>
    <?php else: ?>
        <div class="reader-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
            <a href="<?= url('book-detail.php?slug=' . urlencode($buku['slug'])) ?>" class="btn btn-ungu-outline"
                aria-label="Kembali ke detail buku" title="Kembali ke detail buku">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
            <h1 class="h6 fw-bold mb-0 me-auto text-truncate"><?= e($buku['title']) ?></h1>
            <button type="button" id="prevPage" class="btn btn-rak-netral" aria-label="Halaman sebelumnya">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <label class="visually-hidden" for="pageNum">Halaman</label>
            <input id="pageNum" type="number" min="1" max="<?= (int) $buku['page_count'] ?>" value="<?= $awal ?>"
                class="form-control reader-halaman" aria-label="Nomor halaman">
            <span class="text-secondary small">/ <span id="totalPages"><?= (int) $buku['page_count'] ?></span></span>
            <button type="button" id="nextPage" class="btn btn-rak-netral" aria-label="Halaman berikutnya">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>

            <!-- Mode tampilan menggantikan persentase: fit satu halaman / fit lebar -->
            <div class="btn-group" role="group" aria-label="Mode tampilan halaman">
                <button type="button" id="fitPage" class="btn btn-rak-netral fit-btn"
                    aria-pressed="true">Fit Halaman</button>
                <button type="button" id="fitWidth" class="btn btn-rak-netral fit-btn"
                    aria-pressed="false">Fit Lebar</button>
            </div>
        </div>

        <div class="reader-stage">
            <canvas id="pdfCanvas" aria-label="Halaman buku <?= e($buku['title']) ?>"></canvas>
        </div>

        <!-- Auto-save: form tersembunyi bertarget iframe; bukan AJAX -->
        <form id="progressForm" method="post" action="<?= url('actions/progress-save.php') ?>" target="progressFrame">
            <?= csrf_field() ?>
            <input type="hidden" name="book_id" value="<?= (int) $buku['book_id'] ?>">
            <input type="hidden" name="last_page" id="lastPage" value="<?= $awal ?>">
            <input type="hidden" name="total_pages" id="totalPagesInput" value="<?= (int) $buku['page_count'] ?>">
        </form>
        <iframe name="progressFrame" title="Penyimpan progres baca" style="display:none"></iframe>

        <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
        <script>
            // Logika khusus halaman reader (kontrak: JS halaman ditulis di halamannya).
            pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js";

            const streamUrl = <?= json_encode(url('stream.php?slug=' . urlencode($buku['slug']))) ?>;
            let totalHalaman = <?= (int) $buku['page_count'] ?>;
            let pdfDoc = null;
            let halamanIni = <?= $awal ?>;
            let modeTampilan = window.matchMedia("(max-width: 767.98px)").matches ? "lebar" : "halaman";
            let renderTerakhir = 0;

            const kanvas = document.getElementById("pdfCanvas");
            const konteks = kanvas.getContext("2d");
            const panggung = document.querySelector(".reader-stage");
            const tombolFitHalaman = document.getElementById("fitPage");
            const tombolFitLebar = document.getElementById("fitWidth");

            function tandaiMode() {
                tombolFitHalaman.setAttribute("aria-pressed", modeTampilan === "halaman" ? "true" : "false");
                tombolFitLebar.setAttribute("aria-pressed", modeTampilan === "lebar" ? "true" : "false");
            }

            function skalaUntuk(page) {
                const dasar = page.getViewport({ scale: 1 });
                const lebarTersedia = Math.max(240, panggung.clientWidth - 24);
                const atas = panggung.getBoundingClientRect().top;
                const tinggiTersedia = Math.max(280, window.innerHeight - atas - 24);
                if (modeTampilan === "lebar") return lebarTersedia / dasar.width;
                return Math.min(lebarTersedia / dasar.width, tinggiTersedia / dasar.height);
            }

            // Render pertama HARUS jalan walau nomornya sama dengan halamanIni.
            // Nomor urut mencegah render lama menimpa render baru saat klik cepat.
            function gambarHalaman(nomor, simpan) {
                if (!pdfDoc) return;
                const target = Math.max(1, Math.min(totalHalaman, nomor));
                const urut = ++renderTerakhir;
                halamanIni = target;
                document.getElementById("pageNum").value = target;

                pdfDoc.getPage(target).then(function (page) {
                    if (urut !== renderTerakhir) return;
                    const viewport = page.getViewport({ scale: skalaUntuk(page) });
                    kanvas.width = viewport.width;
                    kanvas.height = viewport.height;
                    return page.render({ canvasContext: konteks, viewport: viewport }).promise;
                }).then(function () {
                    if (urut !== renderTerakhir) return;
                    if (simpan !== false) simpanProgres(target);
                });
            }

            // Kirim form ke iframe tersembunyi: tersimpan tanpa reload, tanpa AJAX.
            function simpanProgres(nomor) {
                document.getElementById("lastPage").value = nomor;
                document.getElementById("totalPagesInput").value = totalHalaman;
                document.getElementById("progressForm").submit();
            }

            function keHalaman(nomor) {
                gambarHalaman(nomor, true);
            }

            document.getElementById("prevPage").addEventListener("click", function () { keHalaman(halamanIni - 1); });
            document.getElementById("nextPage").addEventListener("click", function () { keHalaman(halamanIni + 1); });
            document.getElementById("pageNum").addEventListener("change", function () { keHalaman(parseInt(this.value, 10) || 1); });
            tombolFitHalaman.addEventListener("click", function () {
                modeTampilan = "halaman";
                tandaiMode();
                gambarHalaman(halamanIni, false);
            });
            tombolFitLebar.addEventListener("click", function () {
                modeTampilan = "lebar";
                tandaiMode();
                gambarHalaman(halamanIni, false);
            });
            document.addEventListener("keydown", function (e) {
                if (e.target.tagName === "INPUT" || e.target.tagName === "SELECT") return;
                if (e.key === "ArrowLeft") keHalaman(halamanIni - 1);
                if (e.key === "ArrowRight") keHalaman(halamanIni + 1);
            });

            let timerResize = null;
            window.addEventListener("resize", function () {
                clearTimeout(timerResize);
                timerResize = setTimeout(function () {
                    if (pdfDoc) gambarHalaman(halamanIni, false);
                }, 150);
            });

            tandaiMode();
            pdfjsLib.getDocument(streamUrl).promise.then(function (doc) {
                pdfDoc = doc;
                // PDF ber-object-stream: page_count DB 0, pakai jumlah halaman nyata dari PDF.js.
                if (totalHalaman < 1) {
                    totalHalaman = doc.numPages;
                    document.getElementById("pageNum").max = totalHalaman;
                    document.getElementById("totalPages").textContent = totalHalaman;
                }
                gambarHalaman(halamanIni, true);
            }).catch(function () {
                document.querySelector(".reader-stage").innerHTML =
                    '<p class="text-secondary py-5">Gagal memuat berkas buku. Muat ulang halaman atau hubungi admin.</p>';
            });
        </script>
    <?php endif; ?>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
