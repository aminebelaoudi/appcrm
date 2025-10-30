# ⚡ Commandes PowerShell - Installation Rapide

## 📍 Navigation vers le Projet
```powershell
cd "c:\Users\PC\Desktop\work\canada\ghl dah\app-ghl\ghp-app"
```

---

## 🗄️ Étape 1: Exécuter les Migrations

```powershell
php artisan migrate
```

**Sortie attendue:**
```
Migrating: 2024_01_01_000000_create_users_table
Migrated:  2024_01_01_000000_create_users_table (XX ms)
Migrating: 2024_01_01_000001_create_property_persons_table
Migrated:  2024_01_01_000001_create_property_persons_table (XX ms)
Migrating: 2024_01_01_000002_create_property_opportunities_table
Migrated:  2024_01_01_000002_create_property_opportunities_table (XX ms)
```

---

## 👤 Étape 2: Créer l'Utilisateur Initial

```powershell
php artisan db:seed --class=UserSeeder
```

**Sortie attendue:**
```
✅ Utilisateur créé avec id_location: WuRVjwO5z3hMRuasHjG5
```

---

## ✅ Étape 3: Vérification via Tinker

```powershell
php artisan tinker
```

Puis dans Tinker:
```php
# Voir tous les utilisateurs
\App\Models\User::all();

# Devrait afficher quelque chose comme:
# => Illuminate\Database\Eloquent\Collection {#4567
#      all: [
#        App\Models\User {#4568
#          id: 1,
#          name: "Client Principal",
#          id_location: "WuRVjwO5z3hMRuasHjG5",
#          created_at: "2024-01-01 12:00:00",
#          updated_at: "2024-01-01 12:00:00",
#        },
#      ],
#    }

# Quitter Tinker
exit
```

---

## 🚀 Étape 4: Démarrer le Serveur

```powershell
php artisan serve
```

**Sortie attendue:**
```
Starting Laravel development server: http://127.0.0.1:8000
```

---

## 🧪 Étape 5: Tester les API Endpoints

### Option A: Via PowerShell (avec curl)

**Test 1: Récupérer les personnes (devrait être vide)**
```powershell
curl -X GET "http://localhost:8000/api/properties/12345/persons?id_location=WuRVjwO5z3hMRuasHjG5"
```

**Sortie attendue:**
```json
{"success":true,"persons":[]}
```

**Test 2: Ajouter une personne**
```powershell
$body = @{
    id_location = "WuRVjwO5z3hMRuasHjG5"
    property_listing_id = "12345"
    contact_id = "test_contact_1"
    name = "Test Person"
    email = "test@example.com"
    phone = "514-555-0123"
    implication = "Acheteur"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/properties/persons" -Method POST -Body $body -ContentType "application/json"
```

### Option B: Via le Navigateur

Ouvrez: `http://localhost:8000/properties`

Puis cliquez sur une propriété et testez:
1. Onglet "Personnes Impliquées"
2. Bouton "Ajouter Personne"
3. Sélectionner un contact
4. Vérifier qu'il apparaît dans le tableau

---

## 🔄 Commandes de Maintenance

### Si vous voulez recommencer depuis zéro
```powershell
# ⚠️ ATTENTION: Supprime TOUTES les données!
php artisan migrate:fresh

# Puis recréer l'utilisateur
php artisan db:seed --class=UserSeeder
```

### Voir le statut des migrations
```powershell
php artisan migrate:status
```

### Rollback de la dernière migration
```powershell
php artisan migrate:rollback
```

### Rollback de toutes les migrations
```powershell
php artisan migrate:reset
```

---

## 🐛 Dépannage

### Problème: "SQLSTATE[42S01]: Base table or view already exists"

**Solution:**
```powershell
# Rollback et réexécuter
php artisan migrate:rollback
php artisan migrate
```

### Problème: "Class 'UserSeeder' not found"

**Solution:**
```powershell
# Regénérer l'autoload
composer dump-autoload

# Réessayer
php artisan db:seed --class=UserSeeder
```

### Problème: "Foreign key constraint fails"

**Solution:**
```powershell
# Vérifier que l'utilisateur existe
php artisan tinker
```
```php
\App\Models\User::where('id_location', 'WuRVjwO5z3hMRuasHjG5')->first();
exit
```

### Problème: Port 8000 déjà utilisé

**Solution:**
```powershell
# Utiliser un autre port
php artisan serve --port=8001
```

---

## 📊 Commandes de Vérification

### Vérifier la structure des tables
```powershell
php artisan tinker
```
```php
# Voir les colonnes de la table users
\Schema::getColumnListing('users');

# Voir les colonnes de property_persons
\Schema::getColumnListing('property_persons');

# Voir les colonnes de property_opportunities
\Schema::getColumnListing('property_opportunities');

exit
```

### Compter les enregistrements
```powershell
php artisan tinker
```
```php
# Nombre d'utilisateurs
\App\Models\User::count();

# Nombre de personnes
\App\Models\PropertyPerson::count();

# Nombre d'opportunités
\App\Models\PropertyOpportunity::count();

exit
```

---

## 🎯 Ordre d'Exécution Recommandé

```powershell
# 1. Navigation
cd "c:\Users\PC\Desktop\work\canada\ghl dah\app-ghl\ghp-app"

# 2. Migration
php artisan migrate

# 3. Seeder
php artisan db:seed --class=UserSeeder

# 4. Vérification
php artisan tinker
```
```php
\App\Models\User::all();
exit
```
```powershell

# 5. Démarrer le serveur
php artisan serve

# 6. Tester dans le navigateur
# Ouvrir: http://localhost:8000/properties
```

---

## 📝 Checklist Post-Installation

Après avoir exécuté les commandes:

- [ ] Les 3 tables sont créées (users, property_persons, property_opportunities)
- [ ] Un utilisateur existe avec votre GHL_LOCATION_ID
- [ ] Le serveur Laravel démarre sans erreur
- [ ] L'API `/api/properties/{id}/persons` répond
- [ ] L'API `/api/properties/{id}/opportunities` répond
- [ ] La page des propriétés s'affiche correctement
- [ ] Les onglets "Personnes" et "Opportunités" fonctionnent

---

## 🚀 Prochaines Étapes

Après l'installation:

1. **Intégrer l'AJAX** dans `property-details.blade.php`
   - Suivre `AJAX_INTEGRATION_WITH_ID_LOCATION.js`
   - Ajouter le meta CSRF token
   - Remplacer les fonctions TODO

2. **Tester l'ajout/suppression**
   - Ajouter une personne depuis GHL
   - Vérifier qu'elle est sauvegardée
   - Rafraîchir la page
   - Vérifier qu'elle est toujours là

3. **Tester le multi-tenant**
   - Créer un deuxième utilisateur
   - Vérifier l'isolation des données

---

## 💾 Backup Avant Migration

**Optionnel mais recommandé:**

```powershell
# Si vous avez déjà des données, faire un backup
mysqldump -u root -p votre_base_de_donnees > backup_avant_migration.sql

# Pour restaurer plus tard si nécessaire
mysql -u root -p votre_base_de_donnees < backup_avant_migration.sql
```

---

## 🎉 Résumé

**3 commandes principales:**
```powershell
php artisan migrate                    # Créer les tables
php artisan db:seed --class=UserSeeder # Créer l'utilisateur
php artisan serve                      # Démarrer le serveur
```

**C'est tout!** Votre système multi-tenant est prêt! 🚀
