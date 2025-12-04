# 🏦 Banking Documents API

API Laravel 12 pour la gestion sécurisée de documents bancaires confidentiels (KYC, contrats) avec chiffrement, scan antivirus et partage temporaire.

## 🎯 Contexte académique

Projet complet démontrant :

-   ✅ Architecture Laravel professionnelle
-   ✅ Sécurité bancaire (chiffrement AES-256)
-   ✅ Conformité RGPD (audit, traçabilité)
-   ✅ Scan antivirus asynchrone (ClamAV)
-   ✅ Partage sécurisé via URL signée
-   ✅ Tests automatisés (PHPUnit)
-   ✅ API RESTful documentée

## 🚀 Fonctionnalités

### 📤 Gestion de documents

-   Upload avec chiffrement automatique (AES-256-CBC)
-   Stockage dans `storage/app/private/documents` (jamais public)
-   Vérification d'intégrité (checksum SHA-256)
-   Scan antivirus obligatoire (ClamAV via queue)
-   Types supportés : PDF, JPG, PNG, DOC, DOCX, XLS, XLSX
-   Taille max : 10 MB

### 🔗 Partage temporaire

-   Génération d'URLs signées à usage unique
-   Expiration configurable (1-168 heures)
-   Limite de téléchargements (1-100)
-   Révocation possible
-   Accès sans authentification

### 📊 Audit RGPD

-   Journalisation de toutes les actions
-   Export CSV des logs
-   Aucune donnée sensible dans les logs
-   Traçabilité IP + User-Agent

### 🛡️ Sécurité

-   Chiffrement de bout en bout
-   Scan antivirus asynchrone
-   Policies Laravel (ownership)
-   Authentification Sanctum
-   Soft delete (récupération possible)

## 🏗️ Architecture

```
┌─────────────────────────────────────────────┐
│           API Laravel 12                    │
├─────────────────────────────────────────────┤
│  Controllers → Services → Models             │
│  ├─ DocumentService (CRUD chiffré)           │
│  ├─ EncryptionService (AES-256)              │
│  ├─ AntivirusService (ClamAV => coming soon) │
│  └─ SharingService (URL signée)              │
├─────────────────────────────────────────────┤
│  Middleware: EnsureDocumentAccess            │
│  Policies: DocumentPolicy (ownership)        │
│  Jobs: ScanAntivirusJob (queue)              │
└─────────────────────────────────────────────┘
         ↓                    ↓
    MySQL 8+            Redis (Queue)
         ↓
  storage/app/private/documents
     (fichiers chiffrés)
```

## 📦 Technologies

-   **Framework** : Laravel 12
-   **Base de données** : MySQL 8+
-   **Cache/Queue** : Redis
-   **Chiffrement** : AES-256-CBC (Laravel Crypt)
-   **Antivirus** : ClamAV
-   **Auth** : Laravel Sanctum
-   **Tests** : PHPUnit


## 🚀 Quick Start

```bash
# 1. Installation
composer install
cp .env.example .env
php artisan key:generate

# 2. Base de données
php artisan migrate

# 3. Stockage
mkdir -p storage/app/private/documents
chmod -R 775 storage

# 4. Lancer l'API
php artisan serve

# 5. Lancer le worker (scan antivirus)
php artisan queue:work
```

## 🧪 Tests

```bash
# Tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage

# Test spécifique
php artisan test --filter DocumentServiceTest
```

## 📍 Endpoints principaux

| Méthode | Endpoint                       | Description                |
| ------- | ------------------------------ | -------------------------- |
| POST    | `/api/auth/register`           | Sign Up                    |
| POST    | `/api/auth/login`              | Log in                     |
| POST    | `/api/auth/logout`             | Log out                    |

| POST    | `/api/documents`               | Upload document            |
| GET     | `/api/documents`               | Liste documents            |
| GET     | `/api/documents/{id}`          | Détails document           |
| GET     | `/api/documents/{id}/download` | Télécharge document        |
| DELETE  | `/api/documents/{id}`          | Supprime document          |
| POST    | `/api/documents/{id}/share`    | Crée partage               |
| GET     | `/api/documents/share/{token}` | Accède au partage (public) |

| GET     | `/api/audit`                   | Logs d'audit               |

## 🔑 Authentification

L'API utilise **Laravel Sanctum** :

```bash
# Créer un token
php artisan tinker
$token = App\Models\User::first()->createToken('api')->plainTextToken;
```

Utiliser le token :

```bash
curl -H "Authorization: Bearer {token}" http://localhost:8000/api/documents
```

## 📊 Statuts de document

| Statut         | Description                     |
| -------------- | ------------------------------- |
| `pending_scan` | En attente d'analyse antivirus  |
| `scanning`     | Scan en cours                   |
| `clean`        | Validé, téléchargeable          |
| `infected`     | Virus détecté, fichier supprimé |
| `failed`       | Échec du scan                   |

## 🛡️ Sécurité

### Chiffrement

-   Algorithme : AES-256-CBC
-   Clé : `APP_KEY` dans `.env`
-   Fichiers jamais stockés en clair

### Antivirus

-   ClamAV via daemon `clamd`
-   Scan asynchrone (queue Redis)
-   Suppression automatique si virus détecté

### Partage

-   Token unique 64 caractères
-   Expiration temporelle
-   Limite de téléchargements
-   Révocation possible

### RGPD

-   Audit de toutes les actions
-   Pas de logs de contenu
-   Export CSV des données personnelles
-   Soft delete (droit à l'oubli)

## 🏭 Environnement de production

### Prérequis

-   PHP 8.2+ avec extensions : PDO, OpenSSL, Redis
-   Mysql 8+
-   Redis 7+
-   ClamAV avec daemon actif
-   Supervisor (pour queues)

### Optimisations

```bash
# Cache de configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# OPcache activé
# Redis pour sessions et cache
```

### Monitoring recommandé

-   **Logs** : Sentry, Bugsnag
-   **Performances** : New Relic, Laravel Telescope
-   **Uptime** : Pingdom, UptimeRobot

## 🧑‍💻 Développement

### Structure des modules

```
app/
├── Enums/              # DocumentStatus, AuditAction
├── Http/
│   ├── Controllers/    # DocumentController, SharingController
│   ├── Middleware/     # EnsureDocumentAccess
│   └── Requests/       # StoreDocumentRequest, ShareDocumentRequest
├── Jobs/               # ScanAntivirusJob
├── Models/             # Document, DocumentShare, Audit
├── Policies/           # DocumentPolicy
├── Services/           # DocumentService, EncryptionService, etc.
└── Exceptions/         # VirusDetectedException
```

### Conventions de code

-   PSR-12 : Standard de code PHP
-   Services pour la logique métier
-   Policies pour l'autorisation
-   Jobs pour les tâches asynchrones
-   Form Requests pour la validation

## 👨‍🎓 Auteur

Brice GOUDALO x)
Camélia SOGLO :D

## 🔗 Ressources

-   [Laravel Documentation](https://laravel.com/docs/12.x)
-   [ClamAV Documentation](https://docs.clamav.net/)
-   [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)
-   [RGPD Compliance](https://www.cnil.fr/fr/rgpd-par-ou-commencer)

---

**⚠️ Important** : Ce projet est conçu pour un environnement d'apprentissage. Pour un usage en production bancaire réelle, des audits de sécurité professionnels sont indispensables.
