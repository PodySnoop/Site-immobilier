function highlight(card) {
  card.style.backgroundColor = "#a2d5f2";
  card.style.transition = "background-color 0.5s ease";
  setTimeout(() => {
    card.style.backgroundColor = "#ecf0f1";
  }, 1000);
}