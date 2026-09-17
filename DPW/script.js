// ===== Hamburger menu =====
const menuBtn = document.getElementById('menu-btn');
const menu = document.getElementById('menu');

function closeMenu() {
  menu.classList.add('hidden');
  menuBtn.setAttribute('aria-expanded', 'false');
}

function toggleMenu() {
  const isOpen = !menu.classList.contains('hidden');
  if (isOpen) {
    closeMenu();
  } else {
    menu.classList.remove('hidden');
    menuBtn.setAttribute('aria-expanded', 'true');
  }
}

menuBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  toggleMenu();
});

// Tutup jika klik di luar menu
document.addEventListener('click', (e) => {
  if (!menu.contains(e.target) && !menuBtn.contains(e.target)) {
    closeMenu();
  }
});

// Tutup setelah memilih link (agar tidak menggantung)
menu.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', closeMenu);
});

// Tutup jika layar diperbesar ke ukuran desktop
window.addEventListener('resize', () => {
  if (window.innerWidth >= 768) closeMenu();
});

// ===== Filter buku =====
const searchInput = document.getElementById('search-input');
const bookCards = document.querySelectorAll('.book-card');
const noResult = document.getElementById('no-result');

searchInput.addEventListener('input', () => {
  const keyword = searchInput.value.toLowerCase();
  let visibleCount = 0;

  bookCards.forEach(card => {
    const title = card.querySelector('h3').textContent.toLowerCase();
    if (title.includes(keyword)) {
      card.style.display = 'block';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });

  noResult.classList.toggle('hidden', visibleCount > 0);
});