document.addEventListener("DOMContentLoaded", function () {
    const login = document.getElementById("loginForm");
    if (login) {
        login.addEventListener("submit", function (e) {
            e.preventDefault();
            const role = document.getElementById("role").value;
            if (role === "admin") {
                window.location.href = "dashboard-admin.html";
            } else {
                window.location.href = "dashboard-user.html";
            }
        });
    }

    const search = document.getElementById("searchForm");
    if (search) {
        search.addEventListener("submit", function (e) {
            e.preventDefault();

            window.location.href = "katalog.html";
        });
    }

    const menu = document.getElementById("mobileMenu");
    const sidebar = document.getElementById("sidebar");
    if (menu && sidebar) {
        menu.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });
    }

    const logout = document.querySelectorAll(".logout");
    logout.forEach(function (button) {
        button.addEventListener("click", function () {
            window.location.href = "login.html";
        });
    });
});