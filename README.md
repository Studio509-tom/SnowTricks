# SnowTricks

> Cette application est une plateforme de gestion de tricks pour les amateurs de snowboard. Elle permet d'ajouter, de modifier et de visualiser différents tricks.

## Prérequis

- PHP >= 8.1
- [Composer](https://getcomposer.org/) pour la gestion des dépendances PHP
- [Symfony CLI](https://symfony.com/download) pour simplifier le développement
- MySQL (ou autre base de données compatible) pour le stockage des données

## Installation

### 1. Cloner le dépôt

Clonez le dépôt GitHub sur votre machine locale :

> gh repo clone Studio509-tom/SnowTricks

### 2. Installer les dépendances PHP
Assurez-vous d'avoir installé Composer, puis installez les dépendances :

composer install

### 3. Configurer les variables d'environnement
Copiez le fichier .env pour créer un fichier .env.local que vous allez modifier avec vos configurations locales :

cp .env .env.local

Ensuite, modifiez les informations de connexion à la base de données dans .env.local :

DATABASE_URL="mysql://user:password@127.0.0.1:3306/nom_de_la_base?serverVersion=8.0"
Remarque : Assurez-vous de remplacer user, password, 127.0.0.1, 3306 et nom_de_la_base par vos informations de connexion MySQL.

### 4. Créer la base de données et exécuter les migrations
Pour créer la base de données et appliquer les migrations :

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

### 5. Charger des données de démonstration (fixtures)
Pour charger les fixtures (données de démonstration) dans la base de données, exécutez la commande suivante :

php bin/console doctrine:fixtures:load

### 6. Lancer le serveur de développement
Vous pouvez maintenant lancer le serveur Symfony :

symfony server:start

L'application devrait être accessible à l'adresse suivante : http://localhost:8000.
