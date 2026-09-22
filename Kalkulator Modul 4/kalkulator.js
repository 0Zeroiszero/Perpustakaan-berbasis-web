const layar = document.querySelector(".hasil")
const tombol = document.querySelectorAll("button")

let angka = ""

tombol.forEach((button) => {

    button.addEventListener("click", () => {

        const nilai = button.innerText

        if (nilai >= "0" && nilai <= "9") {
            angka += nilai
            layar.innerText = angka
        }

        if (nilai == "+" || nilai == "-" || nilai == "x" || nilai == "÷") {
            angka += nilai
            layar.innerText = angka
        }

        if (nilai == ".") {
            angka += "."
            layar.innerText = angka
        }

        if (nilai == "AC") {
            angka = ""
            layar.innerText = "0"
        }

        if (nilai == "DEL") {
            angka = angka.slice(0, -1)

            if (angka == "") {
                layar.innerText = "0"
            } else {
                layar.innerText = angka
            }
        }

        if (nilai == "=") {

            let hitung = angka
                .replaceAll("x", "*")
                .replaceAll("÷", "/")
                .replaceAll("-", "-")

            let hasil = Function("return " + hitung)()

            layar.innerText = hasil
            angka = hasil.toString()
        }

    })

})