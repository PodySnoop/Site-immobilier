document.addEventListener("DOMContentLoaded", () => {
  const sections = document.querySelectorAll("section");

  sections.forEach(section => {
    section.addEventListener("click", () => {
      section.style.backgroundColor = "#eaf6ff";
      setTimeout(() => {
        section.style.backgroundColor = "#ffffff";
      }, 800);
    });
  });
});