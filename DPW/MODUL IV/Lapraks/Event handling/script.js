setInterval(function () {
    let waktu =
        new Date().toLocaleTimeString("id-ID");
    document.getElementById("jam")
        .textContent = waktu;
}, 1000);

function cekAnggota() {
    let no = document.getElementById(
        "noAnggota").value;
    document.getElementById("status")
        .textContent =
        no === "" ? "Nomor anggota masih kosong."
            : "Anggota " + no +
            " tercatat aktif.";
    return false;
}