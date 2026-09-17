function validasi() {
    let nama = document.getElementById(
        "nama").value;
    let kode = document.getElementById(
        "kode").value;
    const pesan = document.getElementById(
        "pesan");
    if (nama === "" || kode === "") {
        pesan.textContent =
            "Nama dan kode buku wajib diisi.";
        pesan.style.color = "red";
        return false;
    }
    pesan.textContent =
        "Peminjaman atas nama " + nama +
        " tercatat.";
    pesan.style.color = "green";
    return true;
}
