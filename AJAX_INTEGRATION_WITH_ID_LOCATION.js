// ========================================
// GUIDE D'INTÉGRATION AJAX AVEC id_location
// ========================================

// ÉTAPE 1: Ajouter le token CSRF dans le <head> de property-details.blade.php
// Cherchez la balise <head> et ajoutez:
/*
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- reste du head -->
</head>
*/

// ÉTAPE 2: Ajouter l'id_location dans le JavaScript
// Le controller passe déjà $idLocation à la vue, ajoutez après les variables existantes:
/*
<script>
    const listingId = '{{ $property['ListingId'] }}';
    const idLocation = '{{ $idLocation }}';  // <-- AJOUTER CETTE LIGNE
</script>
*/

// ========================================
// FONCTIONS AJAX POUR PERSONNES IMPLIQUÉES
// ========================================

// Charger les personnes depuis la base de données
function loadPersonsFromDatabase() {
    fetch(`/api/properties/${listingId}/persons?id_location=${idLocation}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentPersons = data.persons;
            updatePersonsTable();
        } else {
            alert('Erreur lors du chargement des personnes');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement des personnes');
    });
}

// Ajouter une personne à la base de données
function addPersonToDatabase(contactId, name, email, phone, role) {
    return fetch('/api/properties/persons', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            id_location: idLocation,  // <-- IMPORTANT
            property_listing_id: listingId,
            contact_id: contactId,
            name: name,
            email: email || null,
            phone: phone || null,
            implication: role || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.person;
        } else {
            throw new Error(data.message || 'Erreur lors de l\'ajout');
        }
    });
}

// Mettre à jour le rôle d'une personne
function updatePersonRoleInDatabase(personId, newRole) {
    return fetch(`/api/properties/persons/${personId}/role`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            id_location: idLocation,  // <-- IMPORTANT
            implication: newRole
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.person;
        } else {
            throw new Error(data.message || 'Erreur lors de la mise à jour');
        }
    });
}

// Supprimer une personne de la base de données
function removePersonFromDatabase(personId) {
    return fetch(`/api/properties/persons/${personId}?id_location=${idLocation}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return true;
        } else {
            throw new Error(data.message || 'Erreur lors de la suppression');
        }
    });
}

// ========================================
// FONCTIONS AJAX POUR OPPORTUNITÉS LIÉES
// ========================================

// Charger les opportunités depuis la base de données
function loadOpportunitiesFromDatabase() {
    fetch(`/api/properties/${listingId}/opportunities?id_location=${idLocation}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentOpportunities = data.opportunities;
            updateOpportunitiesTable();
        } else {
            alert('Erreur lors du chargement des opportunités');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement des opportunités');
    });
}

// Ajouter une opportunité à la base de données
function addOpportunityToDatabase(opportunity) {
    return fetch('/api/properties/opportunities', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            id_location: idLocation,  // <-- IMPORTANT
            property_listing_id: listingId,
            opportunity_id: opportunity.id,
            name: opportunity.name,
            pipeline_id: opportunity.pipelineId || null,
            pipeline_stage_id: opportunity.pipelineStageId || null,
            source: opportunity.source || null,
            status: opportunity.status || null,
            monetary_value: opportunity.monetaryValue || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.opportunity;
        } else {
            throw new Error(data.message || 'Erreur lors de l\'ajout');
        }
    });
}

// Supprimer une opportunité de la base de données
function removeOpportunityFromDatabase(opportunityId) {
    return fetch(`/api/properties/opportunities/${opportunityId}?id_location=${idLocation}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return true;
        } else {
            throw new Error(data.message || 'Erreur lors de la suppression');
        }
    });
}

// ========================================
// INTÉGRATION DANS LES FONCTIONS EXISTANTES
// ========================================

// REMPLACER LA FONCTION addSelectedContact
function addSelectedContact(contactId, name, email, phone) {
    // Vérifier si déjà ajouté
    if (currentPersons.some(p => p.contactId === contactId)) {
        alert('Cette personne est déjà ajoutée');
        return;
    }

    // Ajouter à la base de données
    addPersonToDatabase(contactId, name, email, phone, null)
        .then(person => {
            // Ajouter à l'array local
            currentPersons.push({
                id: person.id,
                contactId: person.contact_id,
                name: person.name,
                email: person.email,
                phone: person.phone,
                role: person.implication
            });
            
            // Mettre à jour la table
            updatePersonsTable();
            
            // Fermer le modal
            document.getElementById('addPersonModal').style.display = 'none';
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'ajout de la personne: ' + error.message);
        });
}

// REMPLACER LA FONCTION removePerson
function removePerson(personId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette personne?')) {
        return;
    }

    removePersonFromDatabase(personId)
        .then(() => {
            // Retirer de l'array local
            currentPersons = currentPersons.filter(p => p.id !== personId);
            
            // Mettre à jour la table
            updatePersonsTable();
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression: ' + error.message);
        });
}

// MODIFIER LA FONCTION EXISTANTE DE CHANGEMENT DE RÔLE
// Trouver dans property-details.blade.php la partie où le rôle change:
/*
<select onchange="changePersonRole(${person.id}, this.value)">
*/

// Ajouter cette fonction:
function changePersonRole(personId, newRole) {
    updatePersonRoleInDatabase(personId, newRole)
        .then(updatedPerson => {
            // Mettre à jour l'array local
            const person = currentPersons.find(p => p.id === personId);
            if (person) {
                person.role = updatedPerson.implication;
            }
            
            // Optionnel: Afficher un message de succès
            console.log('Rôle mis à jour avec succès');
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la mise à jour du rôle: ' + error.message);
            
            // Recharger pour annuler le changement visuel
            loadPersonsFromDatabase();
        });
}

// REMPLACER LA FONCTION addSelectedOpportunity
function addSelectedOpportunity(opportunity) {
    // Vérifier si déjà ajoutée
    if (currentOpportunities.some(o => o.opportunityId === opportunity.id)) {
        alert('Cette opportunité est déjà ajoutée');
        return;
    }

    // Ajouter à la base de données
    addOpportunityToDatabase(opportunity)
        .then(dbOpportunity => {
            // Ajouter à l'array local
            currentOpportunities.push({
                id: dbOpportunity.id,
                opportunityId: dbOpportunity.opportunity_id,
                name: dbOpportunity.name,
                pipelineId: dbOpportunity.pipeline_id,
                pipelineStageId: dbOpportunity.pipeline_stage_id,
                source: dbOpportunity.source,
                status: dbOpportunity.status
            });
            
            // Mettre à jour la table
            updateOpportunitiesTable();
            
            // Fermer le modal
            document.getElementById('addOpportunityModal').style.display = 'none';
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'ajout de l\'opportunité: ' + error.message);
        });
}

// REMPLACER LA FONCTION removeOpportunity
function removeOpportunity(opportunityId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette opportunité?')) {
        return;
    }

    removeOpportunityFromDatabase(opportunityId)
        .then(() => {
            // Retirer de l'array local
            currentOpportunities = currentOpportunities.filter(o => o.id !== opportunityId);
            
            // Mettre à jour la table
            updateOpportunitiesTable();
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression: ' + error.message);
        });
}

// ========================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// ========================================

// AJOUTER À LA FIN DU <script> DANS property-details.blade.php:
/*
// Charger les données sauvegardées au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Les données sont déjà chargées depuis PHP
    // Pas besoin de recharger, elles sont dans currentPersons et currentOpportunities
    
    // Optionnel: Si vous voulez forcer le rechargement depuis l'API:
    // loadPersonsFromDatabase();
    // loadOpportunitiesFromDatabase();
});
*/

// ========================================
// RÉSUMÉ DES MODIFICATIONS À FAIRE
// ========================================

/*
1. ✅ Ajouter <meta name="csrf-token" content="{{ csrf_token() }}"> dans <head>
2. ✅ Ajouter const idLocation = '{{ $idLocation }}'; dans <script>
3. ✅ Remplacer addSelectedContact() avec la version AJAX
4. ✅ Remplacer removePerson() avec la version AJAX
5. ✅ Ajouter changePersonRole() pour mettre à jour le rôle
6. ✅ Remplacer addSelectedOpportunity() avec la version AJAX
7. ✅ Remplacer removeOpportunity() avec la version AJAX
8. ✅ (Optionnel) Ajouter loadPersonsFromDatabase() et loadOpportunitiesFromDatabase()

IMPORTANT:
- Toutes les requêtes API doivent inclure id_location
- GET: Ajouter ?id_location=${idLocation} dans l'URL
- POST/PUT: Ajouter id_location: idLocation dans le body JSON
- DELETE: Ajouter ?id_location=${idLocation} dans l'URL
*/

// ========================================
// VÉRIFICATION DE L'INTÉGRATION
// ========================================

// Après intégration, testez dans la console du navigateur:
/*
// 1. Vérifier que idLocation est défini
console.log('idLocation:', idLocation);

// 2. Vérifier que le CSRF token est présent
console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').content);

// 3. Tester l'ajout d'une personne
addPersonToDatabase('test_contact', 'Test Person', 'test@test.com', '514-555-0000', 'Acheteur')
    .then(person => console.log('Personne ajoutée:', person))
    .catch(error => console.error('Erreur:', error));

// 4. Vérifier la persistance (rafraîchir la page et vérifier que la personne est toujours là)
*/
