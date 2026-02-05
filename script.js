  // Fonction pour formater les nombres avec des espaces comme séparateurs de milliers
function formatNumber(number) {
  return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

// Mise à jour des valeurs des curseurs
const priceRange = document.getElementById('priceRange');
const priceValue = document.getElementById('priceValue');
const surfaceRange = document.getElementById('surfaceRange');
const surfaceValue = document.getElementById('surfaceValue');

if (priceRange && priceValue) {
  // Mise à jour initiale
  priceValue.textContent = formatNumber(priceRange.value) + ' €';
  
  // Mise à jour lors du déplacement du curseur
  priceRange.addEventListener('input', function() {
    priceValue.textContent = formatNumber(this.value) + ' €';
  });
}

if (surfaceRange && surfaceValue) {
  // Mise à jour initiale
  surfaceValue.textContent = surfaceRange.value + ' m²';
  
  // Mise à jour lors du déplacement du curseur
  surfaceRange.addEventListener('input', function() {
    surfaceValue.textContent = this.value + ' m²';
  });
}

// Gestion de la soumission du formulaire
const searchForm = document.getElementById('propertySearch');
if (searchForm) {
  searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Récupération des valeurs du formulaire
    const searchTerm = document.getElementById('searchTerm').value;
    const propertyType = document.getElementById('propertyType').value;
    const transactionType = document.getElementById('transactionType').value;
    const maxPrice = priceRange.value;
    const minSurface = surfaceRange.value;
    
    // Ici, vous pouvez ajouter la logique pour filtrer les biens
    // Par exemple, faire une requête AJAX ou filtrer une liste existante
    
    console.log('Recherche effectuée avec les critères :', {
      searchTerm,
      propertyType,
      transactionType,
      maxPrice,
      minSurface
    });
    
    // Exemple d'affichage des critères de recherche
    alert(`Recherche effectuée :\n` +
          `Localisation : ${searchTerm || 'Non spécifié'}\n` +
          `Type de bien : ${propertyType || 'Tous'}\n` +
          `Transaction : ${transactionType === 'vente' ? 'Achat' : 'Location'}\n` +
          `Prix max : ${formatNumber(maxPrice)} €\n` +
          `Surface min : ${minSurface} m²`);
  });
}

// Fonction existante
function showDetails(propertyName) {
  alert("Vous avez sélectionné : " + propertyName);
}