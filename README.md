# ✦ Kwaret — Digital Products Marketplace

<div align="center">

![Kwaret](https://img.shields.io/badge/Kwaret-Digital%20Marketplace-6366f1?style=for-the-badge&logo=shopify&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=for-the-badge&logo=symfony&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

**Plateforme e-commerce de produits numériques — gaming, streaming, IA, musique**

[🌐 Demo live](https://kwaret.vercel.app) · [🔧 API](https://kwaret-production.up.railway.app/api/products)

</div>

---

## ✨ Fonctionnalités

### 🛍️ Boutique
- Catalogue de produits numériques par catégories (Gaming, Streaming, IA, Musique)
- Barre de recherche avec dropdown suggestions, miniatures, highlighting et navigation clavier
- Filtres par catégorie, tri par prix
- Fiches produits détaillées

### 👤 Authentification
- Inscription / Connexion par email + mot de passe (JWT)
- OAuth social : **Discord** & **GitHub**
- Guards de routes (pages protégées)

### 🛒 Panier & Commandes
- Panier persistant (localStorage)
- Paiement par wallet intégré
- Historique des commandes
- Tickets de support auto-créés à chaque achat

### 💳 Wallet
- Rechargement de solde
- Historique des transactions
- Cashback sur certains produits

### 🎫 Support
- Système de tickets avec messagerie
- Notifications en temps réel

### 🔧 Admin Dashboard
- Gestion des produits (CRUD + upload d'images drag & drop)
- Gestion des commandes et statuts
- Gestion des utilisateurs
- Gestion des catégories
- Statistiques et métriques
- Gestion des rechargements wallet
- Gestion des tickets support

---

## 🏗️ Stack technique

| Couche | Technologie |
|--------|-------------|
| **Frontend** | Vue.js 3, Vite, Pinia, Vue Router 4 |
| **Backend** | Symfony 7.4, PHP 8.3 |
| **Auth** | JWT (lexik/jwt-authentication-bundle) |
| **OAuth** | Discord, GitHub (knpuniversity/oauth2-client-bundle) |
| **BDD** | MySQL 8.0, Doctrine ORM |
| **API** | REST JSON, Symfony Serializer |
| **CORS** | NelmioCorsBundle |
| **Hosting** | Vercel (frontend) + Railway (backend + MySQL) |
| **Container** | Docker (PHP-FPM + Nginx Alpine) |

---

## 🚀 Lancer en local

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0

### Backend

```bash
cd backend
composer install
cp .env .env.local
# Modifier DATABASE_URL dans .env.local
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console lexik:jwt:generate-keypair
symfony serve
```

### Frontend

```bash
cd frontend
npm install
# Créer .env.local avec :
# VITE_API_URL=http://localhost:8000/api
npm run dev
```

---

## 🌐 Déploiement

### Backend → Railway
- Build automatique via `Dockerfile`
- Variables d'environnement configurées dans Railway
- MySQL plugin intégré

### Frontend → Vercel
- Build automatique (`vite build`)
- Root directory : `frontend`
- Variable `VITE_API_URL` pointant vers l'URL Railway

---

## 📁 Structure du projet

```
kwaret/
├── backend/                  # API Symfony 7.4
│   ├── src/
│   │   ├── Controller/       # AuthController, ProductController, OrderController...
│   │   ├── Entity/           # User, Product, Order, Ticket, Wallet...
│   │   └── Repository/
│   ├── config/
│   │   └── packages/         # JWT, CORS, OAuth, Doctrine...
│   ├── docker/
│   │   ├── nginx.conf        # Config Nginx production
│   │   └── start.sh          # Script de démarrage
│   └── Dockerfile
│
└── frontend/                 # App Vue.js 3
    └── src/
        ├── components/       # NavBar, SearchBar, ProductCard...
        ├── pages/            # HomePage, ShopPage, LoginPage...
        │   └── admin/        # AdminProducts, AdminOrders...
        ├── stores/           # Pinia (auth, cart)
        └── router/
```

---

## 🔑 Variables d'environnement

### Backend (`.env`)

```env
APP_ENV=prod
APP_SECRET=your_secret
DATABASE_URL=mysql://user:pass@host:3306/db?serverVersion=8.0
JWT_PASSPHRASE=your_passphrase
FRONTEND_URL=https://your-frontend.vercel.app
CORS_ALLOW_ORIGIN=^https://your-frontend\.vercel\.app$
DISCORD_CLIENT_ID=...
DISCORD_CLIENT_SECRET=...
GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
```

### Frontend (`.env.local`)

```env
VITE_API_URL=https://your-backend.up.railway.app/api
```

---

<div align="center">
  Made with ❤️ — <a href="https://kwaret.vercel.app">kwaret.vercel.app</a>
</div>
