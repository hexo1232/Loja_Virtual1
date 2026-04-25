// dashboard.js

document.addEventListener("DOMContentLoaded", function () {
  const toggleSidebarBtn = document.querySelector("#toggleSidebar");
  const sidebar = document.querySelector(".sidebar");
  const header = document.querySelector(".header");
  const main = document.querySelector(".main");
   

  if (toggleSidebarBtn && sidebar && header && main) {
    toggleSidebarBtn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
      header.classList.toggle("collapsed");
      main.classList.toggle("collapsed");
    });
  }

  // Animação de fade-in ao carregar elementos
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, i) => {
    card.style.opacity = 0;
    setTimeout(() => {
      card.style.transition = "opacity 0.5s ease";
      card.style.opacity = 1;
    }, i * 100);
  });
});
