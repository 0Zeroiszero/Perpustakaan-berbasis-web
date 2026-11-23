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
$aktif = '';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<main class="container-fluid px-3 px-md-4 py-4">
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
            <label class="visually-hidden" for="zoomSelect">Perbesaran</label>
            <select id="zoomSelect" class="form-select w-auto">
                <option value="0.75">75%</option>
                <option value="1" selected>100%</option>
                <option value="1.25">125%</option>
                <option value="1.5">150%</option>
            </select>
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
            let skala = 1;

            const kanvas = document.getElementById("pdfCanvas");
            const konteks = kanvas.getContext("2d");

            function renderPage(nomor) {
                pdfDoc.getPage(nomor).then(function (page) {
                    const viewport = page.getViewport({ scale: skala });
                    kanvas.width = viewport.width;
                    kanvas.height = viewport.height;
                    return page.render({ canvasContext: konteks, viewport: viewport }).promise;
                }).then(function () {
                    halamanIni = nomor;
                    document.getElementById("pageNum").value = nomor;
                    simpanProgres(nomor);
                });
            }

            // Kirim form ke iframe tersembunyi: tersimpan tanpa reload, tanpa AJAX.
            function simpanProgres(nomor) {
                document.getElementById("lastPage").value = nomor;
                document.getElementById("totalPagesInput").value = totalHalaman;
                document.getElementById("progressForm").submit();
            }

            function keHalaman(nomor) {
                const target = Math.max(1, Math.min(totalHalaman, nomor));
                if (pdfDoc && target !== halamanIni) renderPage(target);
            }

            document.getElementById("prevPage").addEventListener("click", function () { keHalaman(halamanIni - 1); });
            document.getElementById("nextPage").addEventListener("click", function () { keHalaman(halamanIni + 1); });
            document.getElementById("pageNum").addEventListener("change", function () { keHalaman(parseInt(this.value, 10) || 1); });
            document.getElementById("zoomSelect").addEventListener("change", function () {
                skala = parseFloat(this.value);
                if (pdfDoc) renderPage(halamanIni);
            });
            document.addEventListener("keydown", function (e) {
                if (e.target.tagName === "INPUT" || e.target.tagName === "SELECT") return;
                if (e.key === "ArrowLeft") keHalaman(halamanIni - 1);
                if (e.key === "ArrowRight") keHalaman(halamanIni + 1);
            });

            pdfjsLib.getDocument(streamUrl).promise.then(function (doc) {
                pdfDoc = doc;
                // PDF ber-object-stream: page_count DB 0, pakai jumlah halaman nyata dari PDF.js.
                if (totalHalaman < 1) {
                    totalHalaman = doc.numPages;
                    document.getElementById("pageNum").max = totalHalaman;
                    document.getElementById("totalPages").textContent = totalHalaman;
                }
                renderPage(halamanIni);
            }).catch(function () {
                document.querySelector(".reader-stage").innerHTML =
                    '<p class="text-secondary py-5">Gagal memuat berkas buku. Muat ulang halaman atau hubungi admin.</p>';
            });
        </script>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
