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
HALAMAN = [
    "index.php",
    "catalog.php",
    "book-detail.php?slug=laskar-pelangi",
    "author.php?slug=pramoedya-ananta-toer",
    "login.php",
    "register.php",
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
    shutil.which("chrome"),
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

    ws = connect(target["webSocketDebuggerUrl"], max_size=8 * 1024 * 1024)
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

    send("Page.enable")
    send("Network.enable")

    for lebar in LEBAR:
        send("Emulation.setDeviceMetricsOverride", {
            "width": lebar, "height": 900, "deviceScaleFactor": 1, "mobile": lebar < 768,
        })
        for halaman in HALAMAN:
            events.clear()
            send("Page.navigate", {"url": f"{BASE}/{halaman}"})
            pump(10, sampai="Page.loadEventFired")
            time.sleep(0.4)
            r = send("Runtime.evaluate", {
                "expression": 'JSON.stringify({sw:document.documentElement.scrollWidth,'
                              'iw:window.innerWidth,h1:document.querySelectorAll("h1").length})',
                "returnByValue": True,
            })
            d = json.loads(r["result"]["value"])
            cek(f"{lebar}px {halaman} tanpa overflow", d["sw"] <= d["iw"], f"scrollWidth {d['sw']} > {d['iw']}")
            cek(f"{lebar}px {halaman} satu h1", d["h1"] == 1, f"h1={d['h1']}")

    # Network reader: login dulu lewat HTTP biasa, lalu cookie dipasang di CDP.
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    html = opener.open(f"{BASE}/login.php", timeout=15).read().decode()
    token = re.search(r'name="csrf" value="([^"]+)"', html).group(1)
    opener.open(
        f"{BASE}/login.php",
        urllib.parse.urlencode({"csrf": token, "email": "andi@email.com", "password": "andi123"}).encode(),
        timeout=15,
    )
    sid = next(c.value for c in jar if c.name == "PHPSESSID")
    send("Network.setCookie", {"name": "PHPSESSID", "value": sid, "domain": "localhost", "path": "/"})

    send("Emulation.setDeviceMetricsOverride", {"width": 1200, "height": 900, "deviceScaleFactor": 1, "mobile": False})
    events.clear()
    send("Page.navigate", {"url": f"{BASE}/read.php?slug=laskar-pelangi"})
    pump(8, sampai="Page.loadEventFired")
    pump(4)  # PDF.js CDN + render + auto-save awal
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
