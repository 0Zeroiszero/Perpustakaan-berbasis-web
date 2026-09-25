const seedBooks = [
  {
    id: 1,
    title: "Pemrograman Java",
    author: "Andi",
    cat: "Pemrograman",
    stock: 5,
  },
  { id: 2, title: "Basis Data", author: "Budi", cat: "Database", stock: 3 },
  {
    id: 3,
    title: "Jaringan Komputer",
    author: "Citra",
    cat: "Jaringan",
    stock: 4,
  },
  {
    id: 4,
    title: "Algoritma dan Struktur Data",
    author: "Dewi",
    cat: "Pemrograman",
    stock: 2,
  },
];
const seedMembers = [
  {
    id: 1,
    name: "Irfan Kurniawan",
    email: "irfan@email.com",
    phone: "081234567890",
  },
  {
    id: 2,
    name: "Budi Santoso",
    email: "budi@email.com",
    phone: "082345678901",
  },
  {
    id: 3,
    name: "Citra Lestari",
    email: "citra@email.com",
    phone: "083456789012",
  },
];
const seedLoans = [
  {
    id: 1,
    memberId: 1,
    bookId: 1,
    date: "2026-09-20",
    due: "2026-09-27",
    status: "Dipinjam",
  },
  {
    id: 2,
    memberId: 2,
    bookId: 2,
    date: "2026-09-15",
    due: "2026-09-20",
    status: "Terlambat",
  },
];
let books = JSON.parse(localStorage.getItem("books")) || seedBooks;
let members = JSON.parse(localStorage.getItem("members")) || seedMembers;
let loans = JSON.parse(localStorage.getItem("loans")) || seedLoans;
const save = () => {
  localStorage.setItem("books", JSON.stringify(books));
  localStorage.setItem("members", JSON.stringify(members));
  localStorage.setItem("loans", JSON.stringify(loans));
};
const money = (n) => "Rp" + Number(n).toLocaleString("id-ID");
const getBook = (id) => books.find((b) => b.id == id);
const getMember = (id) => members.find((m) => m.id == id);
const badge = (status) =>
  `<span class="badge ${status === "Terlambat" ? "late" : status === "Dipinjam" ? "borrowed" : "available"}">${status}</span>`;
function renderDashboard() {
  document.getElementById("totalBuku").textContent = books.reduce(
    (s, b) => s + b.stock,
    0,
  );
  document.getElementById("totalAnggota").textContent = members.length;
  document.getElementById("totalPinjam").textContent = loans.filter(
    (l) => l.status === "Dipinjam" || l.status === "Terlambat",
  ).length;
  document.getElementById("totalTerlambat").textContent = loans.filter(
    (l) => l.status === "Terlambat",
  ).length;
  document.getElementById("recentLoans").innerHTML =
    loans
      .slice(-5)
      .reverse()
      .map(
        (l) =>
          `<tr><td>${getMember(l.memberId)?.name || "-"}</td><td>${getBook(l.bookId)?.title || "-"}</td><td>${badge(l.status)}</td></tr>`,
      )
      .join("") || '<tr><td colspan="3">Belum ada data.</td></tr>';
}
function renderBooks() {
  const q = (document.getElementById("bookSearch")?.value || "").toLowerCase();
  const cat = document.getElementById("bookCategory")?.value || "";
  const list = books.filter(
    (b) =>
      (b.title + " " + b.author).toLowerCase().includes(q) &&
      (!cat || b.cat === cat),
  );
  document.getElementById("bookTable").innerHTML =
    list
      .map(
        (b) =>
          `<tr><td><b>${b.title}</b></td><td>${b.author}</td><td>${b.cat}</td><td>${b.stock}</td><td>${badge(b.stock > 0 ? "Tersedia" : "Habis")}</td><td><button class="action-btn" onclick="editBook(${b.id})">Edit</button><button class="action-btn danger" onclick="deleteBook(${b.id})">Hapus</button></td></tr>`,
      )
      .join("") || '<tr><td colspan="6">Data tidak ditemukan.</td></tr>';
}
function openBookModal(id = null) {
  document.getElementById("bookForm").reset();
  document.getElementById("bookId").value = id || "";
  document.getElementById("bookModalTitle").textContent = id
    ? "Edit Buku"
    : "Tambah Buku";
  if (id) {
    const b = getBook(id);
    bookTitle.value = b.title;
    bookAuthor.value = b.author;
    bookCat.value = b.cat;
    bookStock.value = b.stock;
  }
  openModal("bookModal");
}
function saveBook(e) {
  e.preventDefault();
  const id = Number(bookId.value);
  const data = {
    id: id || Date.now(),
    title: bookTitle.value,
    author: bookAuthor.value,
    cat: bookCat.value,
    stock: Number(bookStock.value),
  };
  if (id) books = books.map((b) => (b.id === id ? data : b));
  else books.push(data);
  save();
  closeModal("bookModal");
  renderBooks();
  toast("Data buku berhasil disimpan.");
}
function editBook(id) {
  openBookModal(id);
}
function deleteBook(id) {
  if (confirm("Hapus buku ini?")) {
    books = books.filter((b) => b.id !== id);
    save();
    renderBooks();
    toast("Buku dihapus.");
  }
}
function renderMembers() {
  const q = (
    document.getElementById("memberSearch")?.value || ""
  ).toLowerCase();
  const list = members.filter((m) =>
    (m.name + " " + m.email).toLowerCase().includes(q),
  );
  document.getElementById("memberTable").innerHTML =
    list
      .map(
        (m) =>
          `<tr><td><b>${m.name}</b></td><td>${m.email}</td><td>${m.phone}</td><td><button class="action-btn" onclick="editMember(${m.id})">Edit</button><button class="action-btn danger" onclick="deleteMember(${m.id})">Hapus</button></td></tr>`,
      )
      .join("") || '<tr><td colspan="4">Data tidak ditemukan.</td></tr>';
}
function openMemberModal(id = null) {
  memberForm.reset();
  memberId.value = id || "";
  memberModalTitle.textContent = id ? "Edit Anggota" : "Tambah Anggota";
  if (id) {
    const m = getMember(id);
    memberName.value = m.name;
    memberEmail.value = m.email;
    memberPhone.value = m.phone;
  }
  openModal("memberModal");
}
function saveMember(e) {
  e.preventDefault();
  const id = Number(memberId.value);
  const data = {
    id: id || Date.now(),
    name: memberName.value,
    email: memberEmail.value,
    phone: memberPhone.value,
  };
  if (id) members = members.map((m) => (m.id === id ? data : m));
  else members.push(data);
  save();
  closeModal("memberModal");
  renderMembers();
  toast("Data anggota berhasil disimpan.");
}
function editMember(id) {
  openMemberModal(id);
}
function deleteMember(id) {
  if (confirm("Hapus anggota ini?")) {
    members = members.filter((m) => m.id !== id);
    save();
    renderMembers();
    toast("Anggota dihapus.");
  }
}
function renderLoans() {
  document.getElementById("loanTable").innerHTML =
    loans
      .map(
        (l) =>
          `<tr><td>${getMember(l.memberId)?.name || "-"}</td><td>${getBook(l.bookId)?.title || "-"}</td><td>${l.date}</td><td>${l.due}</td><td>${badge(l.status)}</td></tr>`,
      )
      .join("") || '<tr><td colspan="5">Belum ada transaksi.</td></tr>';
}
function openLoanModal() {
  loanMember.innerHTML = members
    .map((m) => `<option value="${m.id}">${m.name}</option>`)
    .join("");
  loanBook.innerHTML = books
    .filter((b) => b.stock > 0)
    .map(
      (b) => `<option value="${b.id}">${b.title} (stok: ${b.stock})</option>`,
    )
    .join("");
  loanDate.value = new Date().toISOString().slice(0, 10);
  loanDue.value = "";
  openModal("loanModal");
}
function saveLoan(e) {
  e.preventDefault();
  const book = getBook(Number(loanBook.value));
  if (!book || book.stock < 1) {
    alert("Stok buku tidak tersedia.");
    return;
  }
  loans.push({
    id: Date.now(),
    memberId: Number(loanMember.value),
    bookId: book.id,
    date: loanDate.value,
    due: loanDue.value,
    status: "Dipinjam",
  });
  book.stock--;
  save();
  closeModal("loanModal");
  renderLoans();
  toast("Peminjaman berhasil dibuat.");
}
function renderReturns() {
  const active = loans.filter(
    (l) => l.status === "Dipinjam" || l.status === "Terlambat",
  );
  document.getElementById("returnTable").innerHTML =
    active
      .map((l) => {
        const due = new Date(l.due);
        const now = new Date();
        const late = Math.max(0, Math.ceil((now - due) / 86400000));
        return `<tr><td>${getMember(l.memberId)?.name || "-"}</td><td>${getBook(l.bookId)?.title || "-"}</td><td>${l.due}</td><td>${money(late * 1000)}</td><td><button class="action-btn" onclick="returnBook(${l.id})">Kembalikan</button></td></tr>`;
      })
      .join("") || '<tr><td colspan="5">Tidak ada peminjaman aktif.</td></tr>';
}
function returnBook(id) {
  const loan = loans.find((l) => l.id === id);
  if (!loan) return;
  if (confirm("Proses pengembalian buku?")) {
    loan.status = "Dikembalikan";
    const book = getBook(loan.bookId);
    if (book) book.stock++;
    save();
    renderReturns();
    toast("Buku berhasil dikembalikan.");
  }
}
function openModal(id) {
  document.getElementById(id).classList.add("show");
}
function closeModal(id) {
  document.getElementById(id).classList.remove("show");
}
function toast(message) {
  alert(message);
}
function demoLogin(e) {
  e.preventDefault();
  window.location.href = "index.html";
}
document
  .getElementById("menuBtn")
  ?.addEventListener("click", () =>
    document.getElementById("sidebar").classList.toggle("open"),
  );
