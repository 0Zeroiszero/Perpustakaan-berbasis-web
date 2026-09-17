const buku = ["Dasar Pemrograman Web",
  "Basis Data", "Jaringan Komputer"];
let daftar = document.getElementById("daftar");
for (let judul of buku) {
  daftar.innerHTML += "<li>" + judul + "</li>";
}
document.getElementById("status")
  .textContent =
  "Daftar di atas dirender oleh berkas script-eksternal.js.";
