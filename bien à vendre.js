const form = document.getElementById('property-form');
const list = document.getElementById('property-list');

form.addEventListener('submit', function (e) {
  e.preventDefault();

  const title = document.getElementById('title').value;
  const location = document.getElementById('location').value;
  const price = document.getElementById('price').value;
  const description = document.getElementById('description').value;

  const property = document.createElement('div');
  property.className = 'property';
  property.innerHTML = `
    <h3>${title}</h3>
    <p><strong>Localisation :</strong> ${location}</p>
    <p><strong>Prix :</strong> ${price} €</p>
    <p>${description}</p>
  `;

  list.appendChild(property);
  form.reset();
});