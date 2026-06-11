# BetSport — Plateforme de Paris Sportifs Fictive

> Projet pédagogique — ESGI 4ème année IW — 2025-2026
> Symfony 7.4 · PHP 8.2+ · PostgreSQL

---

## Prérequis

| Outil | Version minimale |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| PostgreSQL | 14+ |
| Symfony CLI | 5.x |
| Node.js + npm | 18+ |

---

## Installation

### 1. Cloner le projet

```bash
git clone <url-du-repo>
cd betsport
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances JS

```bash
npm install
```

### 4. Configurer l'environnement

Copier `.env` en `.env.local` et renseigner les variables :

```dotenv
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/betsport?serverVersion=16&charset=utf8"
MESSENGER_TRANSPORT_DSN=sync://
APP_SECRET=<une-chaine-aleatoire>
```

### 5. Créer la base de données

```bash
MESSENGER_TRANSPORT_DSN=sync:// php bin/console doctrine:database:create
MESSENGER_TRANSPORT_DSN=sync:// php bin/console doctrine:migrations:migrate
```

### 6. Charger les fixtures (données de démo)

```bash
MESSENGER_TRANSPORT_DSN=sync:// php bin/console doctrine:fixtures:load
```

### 7. Compiler les assets CSS (Tailwind)

```bash
npm run build
# ou en mode watch pendant le développement :
npm run watch
```

### 8. Démarrer le serveur

```bash
symfony serve
```

L'application est accessible sur `https://127.0.0.1:8000`.

---

## Comptes de test

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@betsport.fr | Admin1234! | Administrateur |
| manager1@betsport.fr | Manager1234! | Gestionnaire |
| manager2@betsport.fr | Manager1234! | Gestionnaire |
| user1@betsport.fr … user10@betsport.fr | User1234! | Utilisateur |

> `user7` est suspendu · `user8` est auto-exclu 30 jours · `user9` a des plafonds configurés

---

## Architecture

### Stack technique

| Couche | Technologie |
|---|---|
| Framework | Symfony 7.4 |
| ORM | Doctrine ORM 3.x |
| Base de données | PostgreSQL |
| Authentification | Symfony Security (form login) |
| Formulaires | Symfony Forms |
| Templates | Twig |
| CSS | Tailwind CSS via TailwindBundle (AssetMapper) |
| Pagination | KnpPaginatorBundle 6.x |
| Fixtures | DoctrineFixturesBundle + FakerPHP |

---

### Structure des dossiers

```
src/
├── Controller/
│   ├── Admin/              ← DashboardController, UserController
│   ├── Manager/            ← DashboardController, SportEventController
│   ├── User/               ← DashboardController, WalletController, BetController, ResponsibleGamingController
│   ├── Api/                ← EventController (GET /api/events)
│   ├── HomeController.php
│   ├── SecurityController.php
│   └── RegistrationController.php
│
├── Entity/                 ← User, SportEvent, Outcome, Bet, Transaction, LimitConfig, SelfExclusion
├── Repository/             ← QueryBuilder pour toutes les requêtes complexes
├── Form/                   ← RegistrationFormType, SportEventType, OutcomeType
│
├── Service/
│   ├── BettingService.php           ← placement de paris, règles de refus
│   ├── WalletService.php            ← dépôts, débits, plafonds
│   ├── OddsCalculatorService.php    ← calcul pool-based des cotes
│   ├── PayoutService.php            ← distribution des gains, remboursements
│   └── ResponsibleGamingService.php ← plafonds, délai 48h, auto-exclusion
│
├── Security/
│   ├── UserChecker.php              ← bloque comptes suspendus / auto-exclus
│   └── Voter/
│       ├── SportEventVoter.php      ← permissions fines sur les événements
│       └── BetVoter.php             ← permissions fines sur les paris
│
└── DataFixtures/
    └── AppFixtures.php

templates/
├── base.html.twig           ← layout global (thème dark-gold)
├── admin/
├── manager/
├── user/
├── security/                ← login
├── registration/
└── components/
    └── _pagination.html.twig
```

---

### Entités et relations

```
User ──────────────── Bet            (OneToMany)
     ──────────────── Transaction    (OneToMany)
     ──────────────── SelfExclusion  (OneToMany)
     ──────────────── LimitConfig    (OneToOne)
     ──────────────── SportEvent     (OneToMany, via manager)

SportEvent ─────────── Outcome       (OneToMany)
           ─────────── Bet           (OneToMany)

Outcome ────────────── Bet           (OneToMany)

Bet ────────────────── User, SportEvent, Outcome  (ManyToOne)
Transaction ─────────── User                      (ManyToOne)
```

---

### Flux métier principal

```
1. L'utilisateur dépose de l'argent fictif      (WalletService::deposit)
2. Il consulte les événements publiés et choisit une issue
3. Il place un pari                             (BettingService::place)
   → vérification solde, plafonds, statut événement
   → débit portefeuille, création Bet EN_ATTENTE
   → recalcul des cotes                         (OddsCalculatorService)
4. Le manager ferme les paris puis saisit le résultat
5. Gains distribués aux gagnants                (PayoutService::payout)
   ou remboursement si annulation               (PayoutService::refund)
```

---

### Rôles et accès

| Rôle | Périmètre |
|---|---|
| `ROLE_USER` | `/user/*` — paris, portefeuille, jeu responsable |
| `ROLE_MANAGER` | `/manager/*` — CRUD événements, statuts, résultats |
| `ROLE_ADMIN` | `/admin/*` — gestion utilisateurs, statistiques globales |

Les Voters (`SportEventVoter`, `BetVoter`) contrôlent les permissions fines :
un manager ne peut gérer que ses propres événements, un utilisateur ne peut voir que ses propres paris.

---

### API REST publique

| Méthode | URL | Description |
|---|---|---|
| GET | `/api/events` | Liste des événements publiés |
| GET | `/api/events/{id}` | Détail d'un événement avec ses issues |

Réponses en JSON, aucune authentification requise.

---

### Jeu responsable

- **Plafonds** : dépôt quotidien/hebdo + mise quotidienne/hebdo configurables par l'utilisateur
- **Réduction** : appliquée immédiatement
- **Augmentation** : délai de 48h obligatoire (champ `pendingIncrease` JSON dans `LimitConfig`)
- **Auto-exclusion** : période définie par l'utilisateur, bloque la connexion via `UserChecker`

---

## Commandes utiles

```bash
# Lancer les migrations
MESSENGER_TRANSPORT_DSN=sync:// php bin/console doctrine:migrations:migrate

# Recharger les fixtures (purge + rechargement)
MESSENGER_TRANSPORT_DSN=sync:// php bin/console doctrine:fixtures:load

# Vider le cache
MESSENGER_TRANSPORT_DSN=sync:// php bin/console cache:clear

# Lister les routes
MESSENGER_TRANSPORT_DSN=sync:// php bin/console debug:router

# Lancer le serveur
symfony serve
```

> **Note** : le préfixe `MESSENGER_TRANSPORT_DSN=sync://` est requis sur toutes les commandes
> `bin/console` car le composant Messenger est configuré sans transport externe.
