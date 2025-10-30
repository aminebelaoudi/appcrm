# Guide Multi-Tenant avec id_location

## Vue d'ensemble

L'application utilise une architecture multi-tenant où chaque client est identifié par un `id_location` unique. Toutes les données (personnes impliquées et opportunités liées) sont isolées par client.

## Structure de la Base de Données

### 1. Table `users`
```sql
- id (PK)
- name
- id_location (UNIQUE) - Identifiant unique du client (ex: GHL location_id)
- created_at
- updated_at
```

### 2. Table `property_persons`
```sql
- id (PK)
- id_location (FK → users.id_location) CASCADE DELETE
- property_listing_id
- contact_id
- name
- email
- phone
- implication (Acheteur/Vendeur)
- created_at
- updated_at

UNIQUE (id_location, property_listing_id, contact_id)
INDEX (id_location, property_listing_id, contact_id)
```

### 3. Table `property_opportunities`
```sql
- id (PK)
- id_location (FK → users.id_location) CASCADE DELETE
- property_listing_id
- opportunity_id
- name
- pipeline_id
- pipeline_stage_id
- source
- status
- monetary_value
- created_at
- updated_at

UNIQUE (id_location, property_listing_id, opportunity_id)
INDEX (id_location, property_listing_id, opportunity_id)
```

## Relations Eloquent

### User Model
```php
public function propertyPersons()
{
    return $this->hasMany(PropertyPerson::class, 'id_location', 'id_location');
}

public function propertyOpportunities()
{
    return $this->hasMany(PropertyOpportunity::class, 'id_location', 'id_location');
}
```

### PropertyPerson Model
```php
public function user()
{
    return $this->belongsTo(User::class, 'id_location', 'id_location');
}
```

### PropertyOpportunity Model
```php
public function user()
{
    return $this->belongsTo(User::class, 'id_location', 'id_location');
}
```

## Sécurité Multi-Tenant

### Contraintes Uniques
Chaque client peut avoir ses propres relations sur les mêmes propriétés:
- `(id_location, property_listing_id, contact_id)` - Empêche les doublons de personnes par client
- `(id_location, property_listing_id, opportunity_id)` - Empêche les doublons d'opportunités par client

### Cascade Delete
Si un utilisateur est supprimé, toutes ses données associées sont automatiquement supprimées:
```sql
FOREIGN KEY (id_location) REFERENCES users(id_location) ON DELETE CASCADE
```

## API Endpoints (avec id_location requis)

### Personnes Impliquées

#### GET `/api/properties/{listingId}/persons?id_location=xxx`
Récupère toutes les personnes pour une propriété d'un client spécifique.

**Query Parameters:**
- `id_location` (required)

**Response:**
```json
{
  "success": true,
  "persons": [...]
}
```

#### POST `/api/properties/persons`
Ajoute une personne à une propriété.

**Body:**
```json
{
  "id_location": "WuRVjwO5z3hMRuasHjG5",
  "property_listing_id": "12345",
  "contact_id": "contact_xyz",
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "514-555-0123",
  "implication": "Acheteur"
}
```

#### PUT `/api/properties/persons/{id}/role`
Met à jour l'implication d'une personne.

**Body:**
```json
{
  "id_location": "WuRVjwO5z3hMRuasHjG5",
  "implication": "Vendeur"
}
```

#### DELETE `/api/properties/persons/{id}?id_location=xxx`
Supprime une personne.

**Query Parameters:**
- `id_location` (required)

### Opportunités Liées

#### GET `/api/properties/{listingId}/opportunities?id_location=xxx`
Récupère toutes les opportunités pour une propriété d'un client spécifique.

#### POST `/api/properties/opportunities`
Ajoute une opportunité à une propriété.

**Body:**
```json
{
  "id_location": "WuRVjwO5z3hMRuasHjG5",
  "property_listing_id": "12345",
  "opportunity_id": "opp_xyz",
  "name": "Deal ABC",
  "pipeline_id": "pipeline_123",
  "pipeline_stage_id": "stage_456",
  "source": "Website",
  "status": "open",
  "monetary_value": 350000
}
```

#### DELETE `/api/properties/opportunities/{id}?id_location=xxx`
Supprime une opportunité.

## Implémentation Frontend (JavaScript)

### Récupération de l'id_location

Dans `property-details.blade.php`, l'id_location est passé depuis le controller:

```javascript
const idLocation = '{{ $idLocation }}';
```

### Exemple d'appel AJAX

```javascript
// Récupérer les personnes
fetch(`/api/properties/${listingId}/persons?id_location=${idLocation}`)
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log(data.persons);
    }
  });

// Ajouter une personne
fetch('/api/properties/persons', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    id_location: idLocation,
    property_listing_id: listingId,
    contact_id: contactId,
    name: name,
    email: email,
    phone: phone,
    implication: role
  })
});

// Supprimer une personne
fetch(`/api/properties/persons/${personId}?id_location=${idLocation}`, {
  method: 'DELETE',
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  }
});
```

## Migration et Configuration

### Étape 1: Exécuter les migrations
```bash
php artisan migrate
```

Cela créera les 3 tables:
1. `users`
2. `property_persons`
3. `property_opportunities`

### Étape 2: Créer un utilisateur initial

**Option A: Via Tinker**
```bash
php artisan tinker
```
```php
App\Models\User::create([
    'name' => 'Client Principal',
    'id_location' => env('GHL_LOCATION_ID')
]);
```

**Option B: Via Seeder**
Créer `database/seeds/UserSeeder.php`:
```php
<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Client Principal',
            'id_location' => env('GHL_LOCATION_ID')
        ]);
    }
}
```

Exécuter:
```bash
php artisan db:seed --class=UserSeeder
```

### Étape 3: Configuration .env

Assurez-vous que `GHL_LOCATION_ID` est défini dans votre `.env`:
```
GHL_LOCATION_ID=WuRVjwO5z3hMRuasHjG5
```

## Gestion Multi-Client

### Scénario: Plusieurs clients utilisant l'application

1. **Création d'utilisateur**: Chaque nouveau client doit avoir un enregistrement dans la table `users` avec son propre `id_location`

2. **Authentification**: Implémentez un système d'authentification qui identifie l'utilisateur actuel

3. **Middleware**: Créez un middleware pour injecter automatiquement l'`id_location` dans les requêtes:

```php
// app/Http/Middleware/InjectIdLocation.php
public function handle($request, Closure $next)
{
    $user = auth()->user();
    if ($user) {
        $request->merge(['id_location' => $user->id_location]);
    }
    return $next($request);
}
```

4. **Session**: Stockez l'`id_location` en session après connexion:
```php
session(['id_location' => auth()->user()->id_location]);
```

## TODO: Implémentation Future

### Actuellement (Version 1.0)
- ✅ id_location stocké dans .env (GHL_LOCATION_ID)
- ✅ id_location passé manuellement depuis le frontend
- ✅ Isolement des données par id_location

### Version Future (2.0)
- [ ] Système d'authentification Laravel (login/register)
- [ ] Middleware pour injection automatique id_location
- [ ] Interface admin pour gérer les utilisateurs
- [ ] Support multi-location par utilisateur
- [ ] Dashboard par client avec ses données isolées

## Vérification de l'Isolation des Données

### Test 1: Créer deux utilisateurs
```php
User::create(['name' => 'Client A', 'id_location' => 'loc_A']);
User::create(['name' => 'Client B', 'id_location' => 'loc_B']);
```

### Test 2: Ajouter des personnes pour chaque client
```php
// Client A
PropertyPerson::create([
    'id_location' => 'loc_A',
    'property_listing_id' => '12345',
    'contact_id' => 'contact_1',
    'name' => 'Person A1'
]);

// Client B (même propriété, différent client)
PropertyPerson::create([
    'id_location' => 'loc_B',
    'property_listing_id' => '12345',
    'contact_id' => 'contact_1',
    'name' => 'Person B1'
]);
```

### Test 3: Vérifier l'isolation
```php
// Récupérer seulement les données du Client A
$personsA = PropertyPerson::where('id_location', 'loc_A')->get();
// Ne retourne que Person A1

// Récupérer seulement les données du Client B
$personsB = PropertyPerson::where('id_location', 'loc_B')->get();
// Ne retourne que Person B1
```

## Résumé

L'architecture multi-tenant est maintenant complète:
- ✅ Tables créées avec relations FK
- ✅ Models Eloquent avec relations
- ✅ Controller filtrant par id_location
- ✅ Contraintes uniques par client
- ✅ Cascade delete pour intégrité
- ✅ API endpoints sécurisés
- ✅ Documentation complète

**Prochaine étape**: Exécuter les migrations et intégrer l'AJAX dans le frontend.
