# Guide d'Installation - Sauvegarde des Relations Propriété

## Étapes à suivre :

### 1. Exécuter les migrations
```powershell
php artisan migrate
```

### 2. Ajouter le token CSRF dans property-details.blade.php

Ajouter dans la section `<head>` :
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### 3. Mettre à jour les fonctions JavaScript

#### Dans `confirmAddPerson()`, remplacer :
```javascript
// TODO: Sauvegarder dans la base de données via AJAX
```

Par :
```javascript
// Sauvegarder dans la base de données
const propertyId = '{{ $property["ListingId"] ?? "" }}';
const savedPerson = await savePersonToDatabase({
    contactId: selectedContact.id,
    name: selectedContact.name,
    email: selectedContact.email,
    phone: selectedContact.phone,
    companyName: selectedContact.companyName,
    role: ''
}, propertyId);

if (savedPerson) {
    // Mettre à jour l'ID de la personne avec l'ID de la base de données
    const index = currentPersons.findIndex(p => p.contactId === selectedContact.id);
    if (index !== -1) {
        currentPersons[index].id = savedPerson.id;
    }
}
```

#### Dans `updatePersonRole()`, remplacer :
```javascript
// TODO: Sauvegarder dans la base de données via AJAX
console.log('Rôle mis à jour:', name, '→', role);
```

Par :
```javascript
// Sauvegarder le rôle dans la base de données
if (person && person.id) {
    await updatePersonRoleInDatabase(person.id, role);
}
```

#### Dans `removePerson()`, remplacer :
```javascript
// TODO: Supprimer de la base de données via AJAX
```

Par :
```javascript
// Supprimer de la base de données
const person = currentPersons.find(p => p.name === name);
if (person && person.id) {
    await removePersonFromDatabase(person.id);
}
```

#### Dans `confirmAddOpportunity()`, remplacer :
```javascript
// TODO: Sauvegarder dans la base de données via AJAX
```

Par :
```javascript
// Sauvegarder dans la base de données
const propertyId = '{{ $property["ListingId"] ?? "" }}';
const savedOpportunity = await saveOpportunityToDatabase({
    id: selectedOpportunity.id,
    name: selectedOpportunity.name,
    pipelineId: selectedOpportunity.pipelineId,
    pipelineStageId: selectedOpportunity.pipelineStageId,
    source: selectedOpportunity.source,
    status: selectedOpportunity.status
}, propertyId);

if (savedOpportunity) {
    // Mettre à jour l'ID avec l'ID de la base de données
    const index = currentOpportunities.findIndex(o => o.id === selectedOpportunity.id);
    if (index !== -1) {
        currentOpportunities[index].dbId = savedOpportunity.id;
    }
}
```

#### Dans `removeOpportunity()`, remplacer :
```javascript
// TODO: Supprimer de la base de données via AJAX
```

Par :
```javascript
// Supprimer de la base de données
const opportunity = currentOpportunities.find(o => o.name === name);
if (opportunity && opportunity.dbId) {
    await removeOpportunityFromDatabase(opportunity.dbId);
}
```

### 4. Ajouter les fonctions utilitaires au script

Copier toutes les fonctions du fichier `AJAX_INTEGRATION_GUIDE.js` avant la fermeture de la balise `</script>`.

### 5. Tester les fonctionnalités

1. Ajouter une personne → Vérifier dans la base de données `property_persons`
2. Modifier le rôle → Vérifier la mise à jour
3. Supprimer une personne → Vérifier la suppression
4. Ajouter une opportunité → Vérifier dans `property_opportunities`
5. Supprimer une opportunité → Vérifier la suppression
6. Recharger la page → Les données doivent persister

## Structure de la base de données

### Table: property_persons
- id (PK)
- property_listing_id (MLS ID)
- contact_id (GHL Contact ID)
- name
- email
- phone
- company_name
- role (Acheteur/Vendeur)
- created_at
- updated_at

### Table: property_opportunities
- id (PK)
- property_listing_id (MLS ID)
- opportunity_id (GHL Opportunity ID)
- name
- pipeline_id
- pipeline_stage_id
- source
- status
- monetary_value
- created_at
- updated_at

## Routes API disponibles

### Personnes
- GET `/api/properties/{listingId}/persons` - Récupérer toutes les personnes
- POST `/api/properties/persons` - Ajouter une personne
- PUT `/api/properties/persons/{id}/role` - Mettre à jour le rôle
- DELETE `/api/properties/persons/{id}` - Supprimer une personne

### Opportunités
- GET `/api/properties/{listingId}/opportunities` - Récupérer toutes les opportunités
- POST `/api/properties/opportunities` - Ajouter une opportunité
- DELETE `/api/properties/opportunities/{id}` - Supprimer une opportunité

## Note importante

Toutes les routes API sont protégées par :
- Rate limiting (30 requêtes/minute/IP)
- CSRF protection
- Validation des données

Les données sont automatiquement chargées depuis la base de données lors de l'affichage de la page.
