# Verifikasi viewport & network reader lewat CDP (Chrome headless + paket websockets).
import http.cookiejar
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
import urllib.parse
import urllib.request

from websockets.sync.client import connect

BASE = "http://localhost/Perpustakaan-berbasis-web/public"
LEBAR = [320, 375, 390, 768, 1200, 1366]
TAMU = [
    "index.php",
    "catalog.php",
    "book-detail.php?slug=laskar-pelangi",
    "author.php?slug=pramoedya-ananta-toer",
    "publisher.php?slug=bentang-pustaka",
    "login.php",
    "register.php",
]
LOGIN = [
    "catalog.php",
    "book-detail.php?slug=laskar-pelangi",
    "author.php?slug=pramoedya-ananta-toer",
    "publisher.php?slug=bentang-pustaka",
    "my-shelf.php",
    "read.php?slug=laskar-pelangi",
    "profile.php",
    "dashboard-user.php",
    "dashboard-admin.php",
    "admin/books.php",
    "admin/users.php?edit=2",
    "admin/publishers.php",
    "admin/authors.php",
]

gagal = 0
lulus = 0


def cek(nama, ok, detail=""):
    global gagal, lulus
    if ok:
        lulus += 1
        print(f"PASS  {nama}")
    else:
        gagal += 1
        print(f"FAIL  {nama}" + (f" — {detail}" if detail else ""))


chrome = next(
    (p for p in [
        r"C:\Program Files\Google\Chrome\Application\chrome.exe",
        r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    ] if os.path.exists(p)),
    None,
)
if not chrome:
    print("FAIL  Chrome tidak ditemukan")
    sys.exit(1)

profil = tempfile.mkdtemp(prefix="optl-cdp-")
proses = subprocess.Popen([
    chrome, "--headless=new", "--remote-debugging-port=9222",
    f"--user-data-dir={profil}", "--no-first-run", "--no-default-browser-check", "about:blank",
])

ws = None
try:
    target = None
    for _ in range(40):
        try:
            with urllib.request.urlopen("http://127.0.0.1:9222/json/list", timeout=2) as r:
                daftar = json.load(r)
            target = next((t for t in daftar if t["type"] == "page"), None)
            if target:
                break
        except OSError:
            pass
        time.sleep(0.5)
    if not target:
        print("FAIL  CDP tidak tersedia")
        sys.exit(1)

    ws = connect(target["webSocketDebuggerUrl"], max_size=8 * 1024 * 1024, legacy=True)
    events = []
    _id = 0

    def send(method, params=None):
        global _id
        _id += 1
        mid = _id
        ws.send(json.dumps({"id": mid, "method": method, "params": params or {}}))
        while True:
            msg = json.loads(ws.recv(timeout=20))
            if msg.get("id") == mid:
                return msg.get("result", {})
            events.append(msg)

    def pump(detik, sampai=None):
        akhir = time.time() + detik
        while time.time() < akhir:
            try:
                msg = json.loads(ws.recv(timeout=max(0.05, akhir - time.time())))
            except TimeoutError:
                break
            events.append(msg)
            if sampai and msg.get("method") == sampai:
                return

    def evaluasi(ekspresi):
        return send("Runtime.evaluate", {
            "expression": ekspresi, "returnByValue": True, "awaitPromise": True,
        })["result"].get("value")

    def kunjungi(halaman, detik=10):
        events.clear()
        send("Page.navigate", {"url": f"{BASE}/{halaman}"})
        pump(detik, sampai="Page.loadEventFired")
        time.sleep(0.35)

    def cek_ukuran(lebar, halaman, label):
        kunjungi(halaman)
        d = json.loads(evaluasi(
            'JSON.stringify({sw:document.documentElement.scrollWidth,'
            'iw:window.innerWidth,h1:document.querySelectorAll("h1").length,'
            'h:JSON.stringify(Array.from(document.querySelectorAll("h1,h2,h3")).map(function(x){return +x.tagName[1]}))})'
        ))
        cek(f"{lebar}px {label} tanpa overflow", d["sw"] <= d["iw"], f"scrollWidth {d['sw']} > {d['iw']}")
        cek(f"{lebar}px {label} satu h1", d["h1"] == 1, f"h1={d['h1']}")
        jenjang = json.loads(d["h"])
        urut = all(jenjang[i] - jenjang[i - 1] <= 1 for i in range(1, len(jenjang)))
        cek(f"{lebar}px {label} heading berurutan", urut, jenjang)

    send("Page.enable")
    send("Network.enable")

    # Tamu: landing + halaman publik + navbar publik.
    for lebar in LEBAR:
        send("Emulation.setDeviceMetricsOverride", {
            "width": lebar, "height": 900, "deviceScaleFactor": 1, "mobile": lebar < 768,
        })
        for halaman in TAMU:
            cek_ukuran(lebar, halaman, halaman)

    # Landing 1366x768: informasi utama muat, footer baru muncul bila scroll.
    send("Emulation.setDeviceMetricsOverride", {"width": 1366, "height": 768, "deviceScaleFactor": 1, "mobile": False})
    kunjungi("index.php")
    d = json.loads(evaluasi(
        'JSON.stringify({lantai:document.querySelector(".landing").getBoundingClientRect().bottom,'
        'kaki:document.querySelector(".site-footer").getBoundingClientRect().top,'
        'tinggi:window.innerHeight,'
        'grid:getComputedStyle(document.querySelector(".landing-books")).gridTemplateColumns.split(" ").length,'
        'navbar:!!document.querySelector("#navMenu"),sidebar:!!document.querySelector("#sidebar")})'
    ))
    cek("landing 1366x768 muat tanpa scroll konten", d["lantai"] <= d["tinggi"] + 2, d)
    cek("landing footer hanya muncul setelah scroll", d["kaki"] >= d["tinggi"] - 2, d)
    cek("landing grid 3 kolom", d["grid"] == 3, d["grid"])
    cek("landing tamu memakai navbar publik", d["navbar"] and not d["sidebar"])

    # Landing mobile: judul -> registrasi -> buku.
    send("Emulation.setDeviceMetricsOverride", {"width": 390, "height": 844, "deviceScaleFactor": 1, "mobile": True})
    kunjungi("index.php")
    d = json.loads(evaluasi(
        'JSON.stringify({copy:document.querySelector(".landing-copy").getBoundingClientRect().top,'
        'reg:document.querySelector(".landing-register").getBoundingClientRect().top,'
        'books:document.querySelector(".landing-books-wrap").getBoundingClientRect().top,'
        'n:document.querySelectorAll(".landing-book").length})'
    ))
    cek("landing mobile: judul di atas", d["copy"] < d["reg"], d)
    cek("landing mobile: registrasi sebelum buku", d["reg"] < d["books"], d)
    cek("landing mobile: 3 buku", d["n"] == 3, d)

    # Login admin via HTTP, lalu pasang cookie di CDP.
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    html = opener.open(f"{BASE}/login.php", timeout=15).read().decode()
    token = re.search(r'name="csrf" value="([^"]+)"', html).group(1)
    opener.open(
        f"{BASE}/login.php",
        urllib.parse.urlencode({"csrf": token, "email": "admin@pustaka.com", "password": "admin123"}).encode(),
        timeout=15,
    )
    sid = next(c.value for c in jar if c.name == "PHPSESSID")
    send("Network.setCookie", {"name": "PHPSESSID", "value": sid, "domain": "localhost", "path": "/"})

    # Shell login: sidebar tetap ada di katalog/detail/reader/admin, tanpa navbar publik.
    for lebar in LEBAR:
        send("Emulation.setDeviceMetricsOverride", {
            "width": lebar, "height": 900, "deviceScaleFactor": 1, "mobile": lebar < 768,
        })
        for halaman in LOGIN:
            cek_ukuran(lebar, halaman, halaman)

    kunjungi("catalog.php")
    d = json.loads(evaluasi(
        'JSON.stringify({sidebar:!!document.querySelector("#sidebar"),nav:!!document.querySelector("#navMenu"),'
        'beranda:Array.from(document.querySelectorAll("#sidebar a")).some(function(a){return a.textContent.trim()==="Beranda"}),'
        'logout:getComputedStyle(document.querySelector(".dropdown-item.logout")).color,'
        'dropRak:Array.from(document.querySelectorAll(".dropdown-menu a")).some(function(a){return a.textContent.trim()==="Rak Saya"}),'
        'sideRak:Array.from(document.querySelectorAll("#sidebar a")).some(function(a){return a.textContent.trim()==="Rak Saya"})})'
    ))
    cek("catalog login memakai shell sidebar", d["sidebar"] and not d["nav"])
    cek("sidebar tanpa menu Beranda", not d["beranda"])
    cek("dropdown profil keluar merah", d["logout"] == "rgb(220, 38, 38)", d["logout"])
    cek("Rak Saya ada di sidebar, bukan dropdown", d["sideRak"] and not d["dropRak"], d)

    # Mobile topbar: subjudul hilang, avatar bulat saja, dropdown tetap dalam viewport.
    send("Emulation.setDeviceMetricsOverride", {"width": 375, "height": 812, "deviceScaleFactor": 1, "mobile": True})
    kunjungi("catalog.php")
    d = json.loads(evaluasi(
        'JSON.stringify({sub:getComputedStyle(document.querySelector(".topbar-sub")).display,'
        'nama:getComputedStyle(document.querySelector(".topbar .nama-akun")).display,'
        'judul:getComputedStyle(document.querySelector(".topbar-title")).whiteSpace,'
        'clamp:getComputedStyle(document.querySelector(".grid-buku .book-card h3")).webkitLineClamp})'
    ))
    cek("topbar mobile tanpa subjudul", d["sub"] == "none", d)
    cek("topbar mobile avatar-only", d["nama"] == "none", d)
    cek("topbar judul satu baris", d["judul"] == "nowrap", d)
    cek("judul kartu maksimal 2 baris", d["clamp"] == "2", d)

    send("Runtime.evaluate", {"expression": 'document.querySelector(".topbar .btn-akun").click()'})
    time.sleep(0.3)
    d = json.loads(evaluasi(
        'JSON.stringify({show:!!document.querySelector(".topbar .dropdown-menu.show"),'
        'left:document.querySelector(".topbar .dropdown-menu.show").getBoundingClientRect().left,'
        'right:document.querySelector(".topbar .dropdown-menu.show").getBoundingClientRect().right,'
        'iw:window.innerWidth})'
    ))
    cek("dropdown profil terbuka", d["show"], d)
    cek("dropdown profil tidak overflow", d["left"] >= 0 and d["right"] <= d["iw"], d)

    # Sticky: topbar harus tetap di atas setelah scroll.
    send("Runtime.evaluate", {"expression": 'window.scrollTo(0, 600)'})
    time.sleep(0.3)
    d = json.loads(evaluasi(
        'JSON.stringify({y:window.scrollY,top:document.querySelector(".topbar").getBoundingClientRect().top})'
    ))
    cek("topbar katalog sticky saat scroll", d["y"] > 100 and abs(d["top"]) <= 1, d)

    # Modal filter Rak Saya harus bisa discroll di 320x687 (viewport dari laporan).
    send("Emulation.setDeviceMetricsOverride", {"width": 320, "height": 687, "deviceScaleFactor": 2, "mobile": True})
    kunjungi("my-shelf.php")
    send("Runtime.evaluate", {"expression": 'document.querySelector(\'[data-bs-target="#modalFilterRak"]\').click()'})
    time.sleep(0.4)
    d = json.loads(evaluasi(
        'JSON.stringify({show:!!document.querySelector("#modalFilterRak.show"),'
        'scroll:document.querySelector("#modalFilterRak .modal-body").scrollHeight,'
        'client:document.querySelector("#modalFilterRak .modal-body").clientHeight,'
        'footer:document.querySelector("#modalFilterRak .modal-footer").getBoundingClientRect().bottom,'
        'tinggi:window.innerWidth,tinggiViewport:window.innerHeight})'
    ))
    cek("modal filter Rak Saya terbuka", d["show"], d)
    cek("modal filter body bisa discroll", d["scroll"] > d["client"], d)
    cek("footer modal filter terlihat", d["footer"] <= d["tinggiViewport"] + 1, d)
    send("Runtime.evaluate", {"expression": 'document.querySelector("#modalFilterRak .modal-body").scrollTop = 9999'})
    time.sleep(0.2)
    d = evaluasi('document.querySelector("#modalFilterRak .modal-body").scrollTop')
    cek("scrollTop modal filter bergerak", d > 0, d)

    # Modal admin auto-terbuka dan tidak overflow di 320px.
    send("Emulation.setDeviceMetricsOverride", {"width": 320, "height": 800, "deviceScaleFactor": 1, "mobile": True})
    kunjungi("admin/users.php?edit=2")
    d = json.loads(evaluasi(
        'JSON.stringify({modal:!!document.querySelector("#modalUser.show"),sw:document.documentElement.scrollWidth,iw:window.innerWidth})'
    ))
    cek("modal pengguna terbuka di 320px", d["modal"], d)
    cek("modal pengguna tidak overflow 320px", d["sw"] <= d["iw"], d)

    # Modal Buku: footer harus terlihat; body form yang discroll, bukan halaman.
    send("Emulation.setDeviceMetricsOverride", {"width": 1366, "height": 900, "deviceScaleFactor": 1, "mobile": False})
    kunjungi("admin/books.php?edit=10")
    d = json.loads(evaluasi(
        'JSON.stringify({show:!!document.querySelector("#modalBuku.show"),'
        'footer:document.querySelector("#modalBuku .modal-footer").getBoundingClientRect().bottom,'
        'tinggi:window.innerHeight,sw:document.documentElement.scrollWidth,iw:window.innerWidth,'
        'akun:!!document.querySelector(".topbar .btn-akun")})'
    ))
    cek("modal buku terbuka di desktop", d["show"], d)
    cek("footer modal buku terlihat", d["footer"] <= d["tinggi"] + 1, d)
    cek("modal buku tidak overflow horizontal", d["sw"] <= d["iw"], d)
    cek("topbar admin punya dropdown profil", d["akun"], d)

    # Reader di HP: resolusi fisik mengikuti DPR agar tidak buram (fit halaman/lebar).
    send("Emulation.setDeviceMetricsOverride", {"width": 390, "height": 844, "deviceScaleFactor": 2, "mobile": True})
    kunjungi("read.php?slug=laskar-pelangi", 8)
    pump(4)
    d = json.loads(evaluasi(
        'JSON.stringify({dpr:window.devicePixelRatio,'
        'w:document.getElementById("pdfCanvas").width,'
        'css:document.getElementById("pdfCanvas").getBoundingClientRect().width,'
        'h:document.getElementById("pdfCanvas").height})'
    ))
    cek("reader HP memakai resolusi DPR", d["dpr"] >= 2 and d["w"] >= d["css"] * 1.8, d)
    cek("reader HP kanvas punya tinggi", d["h"] > 100, d)

    # Reader: kanvas benar-benar berisi, fit halaman/lebar, iframe auto-save.
    send("Emulation.setDeviceMetricsOverride", {"width": 1200, "height": 900, "deviceScaleFactor": 1, "mobile": False})
    kunjungi("read.php?slug=laskar-pelangi", 8)
    pump(4)
    d = json.loads(evaluasi(
        'JSON.stringify({w:document.getElementById("pdfCanvas").width,'
        'img:(document.getElementById("pdfCanvas").toDataURL("image/png")||"").length,'
        'doc:typeof pdfDoc!=="undefined"&&pdfDoc!==null,'
        'fitH:!!document.getElementById("fitPage"),fitW:!!document.getElementById("fitWidth"),'
        'zoom:!!document.getElementById("zoomSelect")})'
    ))
    cek("kanvas reader benar-benar dirender", d["doc"] and d["w"] > 0 and d["img"] > 5000, d)
    cek("fit halaman/lebar menggantikan persentase", d["fitH"] and d["fitW"] and not d["zoom"])
    send("Runtime.evaluate", {"expression": 'document.getElementById("fitWidth").click()'})
    time.sleep(0.3)
    d = json.loads(evaluasi(
        'JSON.stringify({w:document.getElementById("fitWidth").getAttribute("aria-pressed"),'
        'p:document.getElementById("fitPage").getAttribute("aria-pressed")})'
    ))
    cek("tombol fit lebar aktif", d["w"] == "true" and d["p"] == "false", d)
    send("Runtime.evaluate", {"expression": 'document.getElementById("nextPage").click()'})
    pump(2)
    reqs = [e["params"] for e in events if e.get("method") == "Network.requestWillBeSent"]
    progres = [r for r in reqs if "progress-save.php" in r["request"]["url"]]
    ajax = [r for r in reqs if r.get("type") in ("XHR", "Fetch") and "stream.php" not in r["request"]["url"]]
    cek("reader auto-save lewat iframe", len(progres) >= 1 and all(r.get("type") == "Document" for r in progres),
        f"{len(progres)} request progress-save")
    cek("reader tanpa XHR/Fetch aplikasi", not ajax, f"{[r['request']['url'] for r in ajax]}")
finally:
    if ws:
        ws.close()
    proses.terminate()
    shutil.rmtree(profil, ignore_errors=True)

print(f"\n{lulus} lulus, {gagal} gagal")
sys.exit(1 if gagal else 0)
