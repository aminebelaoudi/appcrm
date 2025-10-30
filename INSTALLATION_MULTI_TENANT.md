# Guide d'Installation Multi-Tenant

Ce guide vous accompagne dans la mise en place de l'architecture multi-tenant avec `id_location`.

## 📋 Prérequis

- Laravel installé et configuré
- Base de données MySQL/PostgreSQL configurée dans `.env`
- Composer installé
- Fichier `.env` avec `GHL_LOCATION_ID` défini

## 🚀 Installation en 5 Étapes

### Étape 1: Vérifier la Configuration .env

Assurez-vous que votre fichier `.env` contient:

```env
# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votre_base_de_donnees
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# GHL Configuration
GHL_LOCATION_ID=WuRVjwO5z3hMRuasHjG5
GHL_API_TOKEN=votre_token_ghl

# Centris API
CENTRIS_API_KEY=votre_token_centris
```

> ⚠️ **Important**: Le `GHL_LOCATION_ID` sera utilisé comme identifiant unique pour votre client principal.

### Étape 2: Exécuter les Migrations

Ouvrez PowerShell dans le dossier du projet et exécutez:

```powershell
php artisan migrate
```

**Ce qui sera créé:**
- ✅ Table `users` (avec id_location unique)
- ✅ Table `property_persons` (avec FK vers users)
- ✅ Table `property_opportunities` (avec FK vers users)

**Sortie attendue:**
```
Migrating: 2024_01_01_000000_create_users_table
Migrated:  2024_01_01_000000_create_users_table (XX ms)
Migrating: 2024_01_01_000001_create_property_persons_table
Migrated:  2024_01_01_000001_create_property_persons_table (XX ms)
Migrating: 2024_01_01_000002_create_property_opportunities_table
Migrated:  2024_01_01_000002_create_property_opportunities_table (XX ms)
```

### Étape 3: Créer l'Utilisateur Principal

Exécutez le seeder pour créer votre premier utilisateur:

```powershell
php artisan db:seed --class=UserSeeder
```

**Sortie attendue:**
```
✅ Utilisateur créé avec id_location: WuRVjwO5z3hMRuasHjG5
```

### Étape 4: Vérifier la Création

**Option A: Via Tinker**
```powershell
php artisan tinker
```
```php
\App\Models\User::all();
// Devrait afficher l'utilisateur créé avec votre GHL_LOCATION_ID
exit
```

**Option B: Via MySQL**
```sql
SELECT * FROM users;
```

### Étape 5: Tester l'API

**Test 1: Récupérer les personnes (devrait retourner un tableau vide)**
```
GET http://localhost:8000/api/properties/12345/persons?id_location=WuRVjwO5z3hMRuasHjG5
```

**Réponse attendue:**
```json
{
  "success": true,
  "persons": []
}
```

**Test 2: Ajouter une personne**
```
POST http://localhost:8000/api/properties/persons
Content-Type: application/json

{
  "id_location": "WuRVjwO5z3hMRuasHjG5",
  "property_listing_id": "12345",
  "contact_id": "test_contact_1",
  "name": "Test Person",
  "email": "test@example.com",
  "phone": "514-555-0123",
  "implication": "Acheteur"
}
```

**Réponse attendue:**
```json
{
  "success": true,
  "message": "Personne ajoutée avec succès",
  "person": {...}
}
```

## ✅ Checklist de Vérification

Après l'installation, vérifiez:

- [ ] Les 3 tables existent dans votre base de données
- [ ] Un utilisateur avec votre `GHL_LOCATION_ID` existe dans la table `users`
- [ ] Les foreign keys sont correctement créées
- [ ] L'API `/api/properties/{id}/persons` répond avec `id_location`
- [ ] L'API `/api/properties/{id}/opportunities` répond avec `id_location`

## 🔧 Vérification des Tables

```sql
-- Vérifier la structure de la table users
DESCRIBE users;

-- Vérifier la structure de la table property_persons
DESCRIBE property_persons;

-- Vérifier la structure de la table property_opportunities
DESCRIBE property_opportunities;

-- Vérifier les foreign keys
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = 'votre_base_de_donnees'
    AND TABLE_NAME IN ('property_persons', 'property_opportunities');
```

## 🐛 Dépannage

### Problème 1: Migration échoue avec "Table already exists"

**Solution:**
```powershell
# Rollback des migrations
php artisan migrate:rollback

# Ou réinitialiser complètement
php artisan migrate:fresh
php artisan db:seed --class=UserSeeder
```

### Problème 2: "id_location est requis" sur les API calls

**Cause:** Le paramètre `id_location` n'est pas envoyé dans la requête.

**Solution:** Vérifiez que votre frontend envoie bien `id_location`:
```javascript
// GET request
fetch(`/api/properties/${listingId}/persons?id_location=${idLocation}`)

// POST request
body: JSON.stringify({
  id_location: idLocation,
  // ... autres champs
})
```

### Problème 3: Seeder retourne une erreur "Class not found"

**Solution:**
```powershell
# Regénérer l'autoload
composer dump-autoload

# Réexécuter le seeder
php artisan db:seed --class=UserSeeder
```

### Problème 4: Foreign key constraint fails

**Cause:** Vous essayez d'insérer des données avec un `id_location` qui n'existe pas dans la table `users`.

**Solution:** 
1. Vérifiez que l'utilisateur existe:
```php
\App\Models\User::where('id_location', 'votre_id_location')->first();
```
2. Si non, créez-le:
```php
\App\Models\User::create([
    'name' => 'Nom du client',
    'id_location' => 'votre_id_location'
]);
```

## 📝 Commandes Utiles

### Créer un nouvel utilisateur manuellement
```powershell
php artisan tinker
```
```php
\App\Models\User::create([
    'name' => 'Nom du Client',
    'id_location' => 'nouveau_location_id'
]);
exit
```

### Voir tous les utilisateurs
```powershell
php artisan tinker
```
```php
\App\Models\User::all();
exit
```

### Voir toutes les personnes d'un client
```powershell
php artisan tinker
```
```php
\App\Models\PropertyPerson::where('id_location', 'WuRVjwO5z3hMRuasHjG5')->get();
exit
```

### Supprimer toutes les données d'un client
```powershell
php artisan tinker
```
```php
$user = \App\Models\User::where('id_location', 'WuRVjwO5z3hMRuasHjG5')->first();
$user->delete(); // Cascade delete supprimera aussi persons et opportunities
exit
```

### Réinitialiser la base de données
```powershell
# ⚠️ ATTENTION: Supprime TOUTES les données!
php artisan migrate:fresh --seed
```

## 🎯 Prochaines Étapes

Maintenant que la base multi-tenant est en place:

1. **Intégrer l'AJAX dans le Frontend**
   - Suivre le guide `AJAX_INTEGRATION_GUIDE.js`
   - Ajouter le token CSRF dans le HTML
   - Remplacer les TODO par les vrais appels API

2. **Implémenter l'Authentification** (Optionnel)
   - Installer Laravel UI ou Breeze
   - Créer un système de login
   - Lier l'utilisateur authentifié à son id_location

3. **Créer un Dashboard Admin** (Optionnel)
   - Interface pour gérer les utilisateurs
   - Voir les statistiques par client
   - Gérer les relations des propriétés

## 📚 Documentation Complémentaire

- `MULTI_TENANT_GUIDE.md` - Guide complet de l'architecture multi-tenant
- `AJAX_INTEGRATION_GUIDE.js` - Exemples de code JavaScript pour l'intégration
- `INSTALLATION_GUIDE.md` - Guide d'intégration AJAX original

## 💡 Notes Importantes

1. **id_location est unique par client**: Ne créez jamais deux utilisateurs avec le même `id_location`

2. **Cascade Delete**: Si vous supprimez un utilisateur, TOUTES ses données seront supprimées automatiquement

3. **Contraintes Uniques**: Un client ne peut pas avoir deux fois la même personne/opportunité sur la même propriété

4. **Sécurité**: L'id_location doit TOUJOURS être envoyé avec chaque requête API

## ✨ Résumé

Vous avez maintenant:
- ✅ Une architecture multi-tenant complète
- ✅ Une base de données avec isolation des données par client
- ✅ Des API sécurisées avec vérification id_location
- ✅ Un utilisateur principal créé automatiquement
- ✅ Une documentation complète pour la maintenance

**Félicitations! Votre système multi-tenant est prêt! 🎉**
