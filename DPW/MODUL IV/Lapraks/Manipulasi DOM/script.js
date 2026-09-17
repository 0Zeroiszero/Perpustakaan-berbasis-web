function cariBuku() {
    let kata = document.getElementById(
        "kataKunci").value;
    let hasil =
        document.querySelector("#hasil");
    hasil.textContent =
        "Menampilkan buku dengan kata kunci "
        + kata + ".";
    hasil.style.fontWeight = "bold";
}
