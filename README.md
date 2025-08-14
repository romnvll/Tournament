Description
-----------

Ce projet a été réalisé dans le but d'aider le club de handball local à gérer efficacement ses rencontres lors des tournois. En tant que passionné de handball et de développement informatique, j'ai conçu cette application en **PHP** pour simplifier et optimiser le processus de gestion des tournois, offrant ainsi une solution pratique et centralisée pour le club.


Fonctionnalités du logiciel Tournament
--------------------------------------

Le logiciel **Tournament** est une application de gestion de tournois qui offre une gamme complète de fonctionnalités pour organiser et gérer efficacement des compétitions. Voici une liste détaillée des fonctionnalités disponibles :

Gestion des Tournois
--------------------

-   **Création de tournois** : Permet de créer de nouveaux tournois en spécifiant les détails tels que le nom, la date, le lieu et le type de compétition.
-   **Modification des tournois** : Possibilité de modifier les informations des tournois existants.
-   **Suppression des tournois** : Option pour supprimer des tournois de la base de données.
-   **Types de tournois supportés** : Toute ronde simple ou aller/retour.

Gestion des Participants
------------------------

-   **Inscription des participants** : Ajout de nouveaux clubs participants .
-   **Gestion des équipes** : Création et gestion d'équipes pour les tournois .


Gestion des rencontres
------------------

-   **Génération automatique des rencontres** : Création des rencontres en fonction du format du tournoi.
-   **Planification des rencontres** : Programmation des dates, heures et lieux des rencontres.
-   **Saisie des résultats** : Enregistrement des scores et des résultats de chaque match. La saisie peut se faire soit par la table centrale, soit par le **gestionnaire du score** à la table du terrain. 
La personne reçoit un e-mail📧 et se connecte pour saisir uniquement les scores de son terrain.
-   **Mise à jour en temps réel** : Actualisation automatique du tableau du tournoi .

Système de Classement
---------------------

-   **Calcul des classements** : Génération des classements individuels ou par équipe en fonction des résultats.
-   **Critères de départage** : Utilisation de critères tels que le *goal average*, le nombre de victoires, etc.
-   **Historique des performances** : Suivi des performances des participants au fil des tournois.

Interface Utilisateur
---------------------

-   **Tableau de bord intuitif** : Vue d'ensemble des informations clés du tournoi sur un seul écran.
-   **Visualisation graphique** : Affichage des tableaux, arbres et rondes du tournoi de manière graphique.
-   **Personnalisation de l'interface** : Options pour personnaliser les thèmes et les dispositions.



Notifications et Communications
-------------------------------

-   **Generation de QR code coach/publique** : Génération automtique d'un qr code pour notifier les coachs et le public des horaires des matchs et résultats.
-   **Generation de QR code arbitre** : Génération automtique d'un qr code pour informer les arbitres sur leurs prochaines rencontres.



Support et Documentation
------------------------

-   **Aide intégrée** : Documentation pour guider les utilisateurs à travers les fonctionnalités.
-   **Support technique** : Assistance pour résoudre les problèmes ou répondre aux questions des utilisateurs.


* * * * *



Comment utiliser
----------------

### Installation

Clonez le projet localement sur votre machine :

```
git clone https://github.com/romnvll/Tournament.git

```

Dans la base de données, il faut se créer un compte dans la table **Clubs** avec un email et un mot de passe codé avec **SHA-256**.

-   **Version PHP** : 8.1 minimum

Pour commencer la configuration des tournois, entrez dans la partie admin :

```
http://ip/Tournament/Auth/

```
