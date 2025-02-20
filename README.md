
# Ecoridepool-Ride-web-app
=======

# EcoRide - Plateforme de Covoiturage

## Présentation du Projet
**EcoRide** est une plateforme de covoiturage développée avec **Slim PHP**, **MySQL** et **MongoDB**, permettant aux utilisateurs de proposer et réserver des trajets.  
L'interface est **mobile-first**, utilisant **Twig** (Blade-like) et **Tailwind CSS**.

## 📌 Fonctionnalités Principales
- **Inscription et connexion sécurisées** (Sessions persistantes).
- **Réservation et acceptation des trajets en temps réel**.
- **Historique des trajets** pour conducteurs et passagers.
- **Gestion des préférences utilisateurs** avec MongoDB.
- **Administration des utilisateurs** (modification des rôles et licences).
- **Interface mobile-friendly** avec navigation fluide.

## 🛠️ Technologies Utilisées
- **Backend** : Slim PHP 4
- **Base de données** : MySQL (relationnel) + MongoDB (préférences utilisateur)
- **Frontend** : Twig (Blade-like) + Tailwind CSS
- **Sessions & Authentification** : Sessions PHP (Pas de JWT)
- **Déploiement** : Compatible avec Apache/Nginx (Heroku, Fly.io)

## 📂 Installation et Configuration

### 1️⃣ Prérequis
- PHP 8+
- MySQL 5.7+
- MongoDB 4.4+
- Composer (gestionnaire de dépendances PHP)

### 2️⃣ Installation de Slim PHP
- Cloner le projet avec la commande :  
  `git clone https://github.com/theWQLker/Ecoridepool-Ride-web-app.git`
- Aller dans le dossier du projet :  
  `cd ecoride-slim`
- Installer Slim et les dépendances PHP avec :  
  `composer install`

### 3️⃣ Configuration de MySQL
Importer la base de données avec les utilisateurs et trajets préexistants dans le dossier `/ecoride-slim/data`.

### 4️⃣ Configuration de MongoDB
Importer les préférences utilisateurs MongoDB depuis le dossier `C:\ecoride-slim\data`.

### 5️⃣ Démarrer le serveur PHP
Lancer le serveur PHP localement avec la commande :  
  `php -S localhost:8000 -t public`

## 🔧 Déploiement

### 👥 Comptes de Test
| Rôle      | Email                    | Mot de passe    |
|-----------|--------------------------|-----------------|
| Admin     | admin1@ecoride.com        | adminsecure     |
| Admin     | admin2@ecoride.com        | adminsecure     |
| Conducteur| driver1@ecoride.com       | driverpass      |
| Conducteur| driver2@ecoride.com       | driverpass      |
| Conducteur| driver3@ecoride.com       | driverpass      |
| Passager  | user1@ecoride.com         | userpassword    |
| Passager  | user2@ecoride.com         | userpassword    |
| Passager  | user3@ecoride.com         | userpassword    |

## 📂 Structure des Fichiers
```
ecoride-slim/
│── app/
│   ├── Controllers/
│   │   ├── AdminController.php
│   │   ├── DriverController.php
│   │   ├── HomeController.php
│   │   ├── RideController.php
│   │   ├── UserController.php
│   ├── templates/  (Vue - Twig)
│   │   ├── layout.twig
│   │   ├── home.twig
│   │   ├── login.twig
│   │   ├── register.twig
│   │   ├── profile.twig
│   │   ├── menu.twig
│   │   ├── request-ride.twig
│   │   ├── ride-history.twig
│   │   ├── driver-ride-history.twig
│   │   ├── admin.twig  (Gestion admin)
│   ├── routes.php  (Toutes les routes API & web)
│   ├── dependencies.php  (Dépendances du projet)
│   ├── mongodb.php  (Connexion MongoDB)
│── public/
│   ├── index.php
│   ├── assets/
│── vendor/ (Dépendances Composer)
│── ecoride_dump.sql (Export MySQL)
│── ecoride_mongo_backup/ (Backup MongoDB)
```

## 🔍 Gestion des Branches Git

### Organisation des Branches
- **main** → Version stable
- **dev** → Développement en cours
- **feature-*** → Nouvelle fonctionnalité

### Commandes Git
- Créer une nouvelle branche de développement :  
  `git checkout -b dev`

### Fonctionnalités et État
- **Inscription et connexion sécurisée** - ✅ Terminé
- **Gestion des sessions persistantes** - ✅ Terminé
- **Interface mobile-first** - ✅ Terminé
- **Demande et acceptation de trajets** - ✅ Terminé
- **Historique des trajets pour conducteurs et passagers** - ✅ Terminé
- **Préférences utilisateur (MongoDB)** - ✅ Terminé
- **Interface administrateur** - ✅ Terminé
- **Modification des rôles et licences** - ✅ Terminé
- **Affichage des trajets actifs** - ✅ Terminé
