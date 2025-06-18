# EcoRide – Plateforme de Covoiturage

**EcoRide** est une application de covoiturage mobile-first, développée avec Slim PHP, MySQL et MongoDB. Elle permet aux conducteurs de proposer des trajets et aux passagers de réserver en quelques clics.

---

## 🚀 Aperçu & Démonstration

* [![Live Demo](https://img.shields.io/badge/Live-Demo-brightgreen)](https://ecoride-mirror-1-fcd84d9225d6.herokuapp.com/)

---

## 📌 Fonctionnalités Clés

1. **Inscription & Connexion**
2. **Création / Réservation de trajets** (CRUD)
3. **Tableau de bord conducteur / passager / admin**
4. **Gestion des préférences (MongoDB)**
5. **Recherche avancée** (filtres écologique, prix, durée…)
6. **Sécurité & Sessions PHP**

> **Note de sécurité (CSRF)**
> Par contrainte de temps pour l’examen, la protection CSRF a été temporairement désactivée afin d’assurer un déploiement pleinement fonctionnel. Une réintégration via Slim-CSRF et FormData est planifiée pour la version post-révision.

---

## 👥 Comptes de Test

### Admin Principal

* **Nom** : Admin One
* **Email** : [admin1@ecoride.com](mailto:admin1@ecoride.com)
* **Mot de passe** : adminsecure

### Autres Comptes

| Rôle       | Nom          | Email                                             | Mot de passe |
| ---------- | ------------ | ------------------------------------------------- | ------------ |
| Conducteur | Driver One   | [driver1@ecoride.com](mailto:driver1@ecoride.com) | driverpass   |
| Conducteur | Driver Two   | [driver2@ecoride.com](mailto:driver2@ecoride.com) | driverpass   |
| Conducteur | Driver Three | [driver3@ecoride.com](mailto:driver3@ecoride.com) | driverpass   |
| Conducteur | Driver Four  | [driver4@ecoride.com](mailto:driver4@ecoride.com) | driverpass   |
| Passager   | User One     | [user1@ecoride.com](mailto:user1@ecoride.com)     | password123  |
| Passager   | User Two     | [user2@ecoride.com](mailto:user2@ecoride.com)     | password123  |
| Passager   | User Three   | [user3@ecoride.com](mailto:user3@ecoride.com)     | password123  |
| Passager   | User Four    | [user4@ecoride.com](mailto:user4@ecoride.com)     | password123  |
| Passager   | User Five    | [user5@ecoride.com](mailto:user5@ecoride.com)     | password123  |
| Passager   | User Six     | [user6@ecoride.com](mailto:user6@ecoride.com)     | password123  |

---

## 🛠️ Installation & Déploiement

### Prérequis

* PHP 8+
* Composer
* MySQL 5.7+
* MongoDB 4.4+

### Installation locale

```bash
git clone https://github.com/theWQLker/Ecoridepool-Ride-web-app.git
cd ecoride-slim
composer install
# Importez le dump MySQL et le backup MongoDB :
# mysql -u user -p ecoride < ecoride_dump.sql
# mongorestore --db ecoride_mongo_backup ecoride_mongo_backup/
php -S localhost:8000 -t public
```
Accédez à [http://localhost:8000](http://localhost:8000)
---

## 📂 Structure du Projet

```text
ecoride-slim/
├── app/
│   ├── Controllers/    # Logique métier
│   ├── templates/      # Vues Twig
│   ├── routes.php      # Définitions des routes
│   └── mongodb.php     # Connexion MongoDB
├── public/
│   ├── index.php       # Point d’entrée Slim
│   └── js/             # Scripts front-end
├── vendor/             # Bibliothèques Composer
├── ecoride_dump.sql    # Dump MySQL initial
└── ecoride_mongo_backup/ # Backup MongoDB
```

---

## 🔄 Git & Branches

* **main** : version stable & déployée
* **dev** : développement en cours
* **feature-**\* : nouvelles fonctionnalités

```bash
# Créer une branche de fonctionnalité
git checkout -b feature-login-enhancements
```

---

## 🎯 Usage & Tests

1. Inscrire un utilisateur (passager ou conducteur)
2. Créer un trajet en tant que conducteur
3. Rechercher et rejoindre un trajet en tant que passager
4. Explorer les dashboards Admin / Employé

---

## 🔮 Prochaines évolutions

* Mise en place de tests automatisés (PHPUnit)
* Notifications e-mail (SwiftMailer / SMTP)
* Intégration d’un système de paiement (Stripe)

---

© 2025 EcoRide – Tous droits réservés.

---
```
