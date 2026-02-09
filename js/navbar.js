const hamburger = document.getElementById('hamburger');
const navbar = document.getElementById('navbar');
const overlay = document.getElementById('sidebarOverlay');

function toggleSidebar() {
  hamburger.classList.toggle('active');
  navbar.classList.toggle('active');
  overlay.classList.toggle('active');
}

hamburger.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', toggleSidebar);

document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    if (navbar.classList.contains('active')) {
      toggleSidebar();
    }
  });
});

