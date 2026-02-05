# 🏠 Pollux Immobilier

Une plateforme web complète de gestion immobilière développée en PHP/MySQL, permettant aux utilisateurs de rechercher, réserver et gérer des biens immobiliers en location et en vente.

## 📋 Description du projet

Pollux Immobilier est une application web full-stack qui offre une interface intuitive pour :
- La consultation de biens immobiliers (location et vente)
- La réservation de visites
- La gestion des favoris
- L'administration complète du site
- La gestion des estimations immobilières

## 🌟 Fonctionnalités principales

### Pour les utilisateurs
- 🔍 **Recherche avancée** de biens par localisation, type, prix
- ❤️ **Système de favoris** pour sauvegarder les biens intéressants
- 📅 **Réservation de visites** avec gestion automatique des créneaux
- 📧 **Formulaire de contact** et demande d'estimation
- 👤 **Espace personnel** pour suivre ses réservations et favoris

### Pour l'administration
- 📊 **Tableau de bord** avec statistiques en temps réel
- 🏢 **Gestion complète** des biens (ajout, modification, suppression)
- 👥 **Gestion des utilisateurs** et de leurs droits
- 📨 **Gestion des messages** de contact avec statuts (lu/traité)
- 📈 **Suivi des activités** et journal des actions

## 📸 Captures d'écran

### Page d'accueil
![Accueil](capture_ecran_page_accueil.png)

### Tableau de bord admin
![Admin](capture_ecran_page_tableau_de_bord_admin.png)

## 🛠️ Stack technique

### Backend
- **PHP 8.x** - Langage principal
- **MySQL** - Base de données
- **PDO** - Connexion sécurisée à la BDD
- **Sessions PHP** - Gestion de l'authentification

### Frontend
- **HTML5** - Structure sémantique
- **CSS3** - Design responsive avec variables CSSss
- **JavaScript** - Interactivité côté client
- **Font Awesome** - Icônes professionnelles

### Architecture
- **MVC-like** - Séparation des responsabilités
- **Modularité** - Code organisé en dossiers fonctionnels
- **Sécurité** - Protection contre les injections SQL et XSS

## 📁 Structure du projet
Pollux_immobilier/ ├── 📁 utilisateurs/ # Espace membres et admin │ ├── admin.php # Tableau de bord administration │ ├── login.php # Connexion │ ├── profil_utilisateur.php # Profil utilisateur │ ├── mes_reservations.php # Réservations utilisateur │ ├── mes_favoris.php # Favoris utilisateur │ └── contact.php # Messages de contact ├── 📁 includes/ # Fichiers réutilisables │ ├── header.php # En-tête du site │ ├── db.php # Connexion BDD │ └── functions.php # Fonctions utilitaires ├── 📁 uploads/ # Fichiers uploadés ├── 📁 ajax/ # Requêtes AJAX ├── 📄 style.css # Styles principaux ├── 📄 pages-common.css # Styles pages secondaires └── 📄 index.php # Page d'accueil


## 🚀 Installation

### Prérequis
- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- phpMyAdmin (optionnel)

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   git clone [https://github.com/votre-username/Pollux_immobilier.git](https://github.com/votre-username/Pollux_immobilier.git)
   cd Pollux_immobilier

Configurer la base de données
sql
CREATE DATABASE pollux_immobilier;
-- Importer le fichier SQL fourni
Configurer la connexion
Modifier includes/db.php avec vos identifiants BDD
Vérifier les permissions des dossiers uploads/
Lancer le projet
bash


# Avec WAMP/XAMPP
http://localhost/Pollux_immobilier/
💡 À propos du développeur
Mon parcours de reconversion
Après 8 ans en tant que technicienne de laboratoire en biologie, j'ai entrepris une reconversion professionnelle passionnante dans le développement web. Ce projet Pollux Immobilier représente l'aboutissement de ma formation et ma première réalisation significative en développement full-stack.

Compétences développées
🔧 Techniques

Programmation PHP/MySQL avancée
Architecture MVC et bonnes pratiques
Sécurité web (protection XSS, SQL injection)
Design responsive et CSS moderne
Gestion de projet versionné avec Git
🧠 Méthodologiques

Analyse de besoins et conception technique
Résolution de problèmes complexes
Tests et débogage systématiques
Documentation technique
Ce projet m'a permis de
✅ Mettre en pratique les concepts théoriques appris
✅ Développer une application complète de A à Z
✅ Comprendre les enjeux de la sécurité web
✅ Créer une expérience utilisateur fluide
✅ Gérer un projet de manière autonome
🎯 Objectifs futurs
Intégration d'un système de paiement en ligne
Développement d'une API REST
Application mobile companion
Système de notifications push
Intelligence artificielle pour les recommandations
🤝 Contribuer
Ce projet étant un portfolio personnel, les contributions sont principalement des suggestions d'amélioration. N'hésitez pas à :

Ouvrir une issue pour signaler un bug
Proposer des améliorations via les issues
Partager vos retours d'expérience
📄 Licence
Ce projet est sous licence MIT.

📞 Contact
Email: iris.clavier@gmail.com
LinkedIn: iris-clavier-linkedin
GitHub: votre-username
"De la précision du laboratoire à la rigueur du code, chaque ligne est une nouvelle découverte."

Projet réalisé avec ❤️ dans le cadre de ma reconversion professionnelle.





