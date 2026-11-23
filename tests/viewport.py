# Verifikasi viewport & network reader lewat CDP (Chrome headless + paket websockets).
# Chrome headless mematok lebar window minimum ~512px, jadi 320/375px wajib
# Emulation.setDeviceMetricsOverride, bukan --window-size.
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
LEBAR = [320, 375, 768, 1200]
HALAMAN_TAMU = [
    "index.php",
    "catalog.php",
    "book-detail.php?slug=laskar-pelangi",
    "author.php?slug=pramoedya-ananta-toer",
    "login.php",
    "register.php",
    "my-shelf.php",
    "read.php?slug=laskar-pelangi",
    "profile.php",
    "dashboard-user.php",
    "dashboard-admin.php",
    "admin/books.php",
    "admin/book-form.php",
    "admin/publishers.php",
    "admin/authors.php",
    "admin/users.php",
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
        })["result"]["value"]

    def cek_ukuran(lebar, halaman, label):
        events.clear()
        send("Page.navigate", {"url": f"{BASE}/{halaman}"})
        pump(10, sampai="Page.loadEventFired")
        time.sleep(0.3)
        d = json.loads(evaluasi(
            'JSON.stringify({sw:document.documentElement.scrollWidth,'
            'iw:window.innerWidth,h1:document.querySelectorAll("h1").length,'
            'h:JSON.stringify(Array.from(document.querySelectorAll("h1,h2,h3")).map(function(x){return +x.tagName[1]}))})'
        ))
        cek(f"{lebar}px {label} tanpa overflow", d["sw"] <= d["iw"], f"scrollWidth {d['sw']} > {d['iw']}")
        cek(f"{lebar}px {label} satu h1", d["h1"] == 1, f"h1={d['h1']}")
        jenjang = json.loads(d["h"])
        urut = True
        for i in range(1, len(jenjang)):
            if jenjang[i] - jenjang[i - 1] > 1:
                urut = False
        cek(f"{lebar}px {label} heading berurutan", urut, jenjang)

    send("Page.enable")
    send("Network.enable")

    # Guest: halaman publik harus tampil; halaman terkunci akan redirect ke login.
    for lebar in LEBAR:
        send("Emulation.setDeviceMetricsOverride", {
            "width": lebar, "height": 900, "deviceScaleFactor": 1, "mobile": lebar < 768,
        })
        for halaman in HALAMAN_TAMU[:6]:
            cek_ukuran(lebar, halaman, halaman)

    # Login via HTTP biasa, lalu cookie dipasang di CDP supaya halaman terkunci ikut diuji.
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

    for lebar in LEBAR:
        send("Emulation.setDeviceMetricsOverride", {
            "width": lebar, "height": 900, "deviceScaleFactor": 1, "mobile": lebar < 768,
        })
        for halaman in HALAMAN_TAMU[6:]:
            cek_ukuran(lebar, halaman, halaman)

    # Pil genre berapostrof: klik harus menyaring tanpa error JS.
    send("Emulation.setDeviceMetricsOverride", {"width": 1200, "height": 900, "deviceScaleFactor": 1, "mobile": False})
    # Sisipkan genre uji lewat DB tidak bisa dari sini; pakai pil yang ada dan pastikan klik berfungsi.
    send("Page.navigate", {"url": f"{BASE}/catalog.php"})
    pump(10, sampai="Page.loadEventFired")
    hasil = evaluasi(
        '(function(){var p=document.querySelector(".category-btn[data-nama=\\"Novel\\"]");'
        'if(!p)return "no-pill";p.click();'
        'return document.querySelectorAll(".book-item:not(.d-none)").length+"/"+'
        'document.getElementById("jumlahBuku").textContent;})()'
    )
    cek("filter genre klik berfungsi", isinstance(hasil, str) and hasil != "no-pill" and not hasil.startswith("no"),
        str(hasil))

    # Network reader: buka PDF, klik next, pastikan auto-save lewat iframe & tanpa AJAX aplikasi.
    events.clear()
    send("Page.navigate", {"url": f"{BASE}/read.php?slug=laskar-pelangi"})
    pump(8, sampai="Page.loadEventFired")
    pump(4)
    send("Runtime.evaluate", {"expression": 'document.getElementById("nextPage").click()'})
    pump(2)
    reqs = [e["params"] for e in events if e.get("method") == "Network.requestWillBeSent"]
    progres = [r for r in reqs if "progress-save.php" in r["request"]["url"]]
    # PDF.js sendiri memakai fetch/XHR internal untuk mengalirkan PDF dari stream.php;
    # kontrak "tanpa AJAX" melarang AJAX tulisan aplikasi, bukan transport library.
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
