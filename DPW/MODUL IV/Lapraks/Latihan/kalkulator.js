function hitung(op) {
    let a = parseFloat(
        document.getElementById(
            "angka1").value);
    let b = parseFloat(
        document.getElementById(
            "angka2").value);
    let h;
    if (op === "+") { h = a + b; }
    else if (op === "-") { h = a - b; }
    else if (op === "*") { h = a * b; }
    else if (b === 0) {
        h = "Tidak bisa dibagi nol";
    }
    else { h = a / b; }
    document.getElementById("hasil")
        .textContent =
        "Hasil: " + a + " " + op + " " + b +
        " = " + h;
}