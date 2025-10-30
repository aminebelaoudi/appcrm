/**
 * Fichier de documentation pour l'intégration AJAX
 * 
 * Ce fichier contient le code JavaScript à ajouter dans property-details.blade.php
 * pour remplacer les TODO et implémenter la sauvegarde dans la base de données
 */

// ========== FONCTIONS UTILITAIRES ==========

/**
 * Récupérer le MLS ID de la propriété actuelle
 */
function getCurrentPropertyId() {
    // Le MLS ID est affiché dans la page
    const mlsBadge = document.querySelector('.mls-badge');
    if (mlsBadge) {
        return mlsBadge.textContent.replace('MLS:', '').trim();
    }
    return null;
}

// ========== PERSONNESNMP IMPLIQUÉES ==========

/**
 * REMPLACER LE TODO DANS confirmAddPerson()
 * 
 * Ajouter ceci après: closeAddPersonModal();
 */
async function savePersonToDatabase(person, propertyId) {
    try {
        const response = await fetch('/api/properties/persons', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                property_listing_id: propertyId,
                contact_id: person.contactId,
                name: person.name,
                email: person.email,
                phone: person.phone,
                company_name: person.companyName,
                role: person.role || ''
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Erreur:', data.message);
            alert('Erreur lors de la sauvegarde');
            return null;
        }

        return data.person;
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la sauvegarde');
        return null;
    }
}

/**
 * REMPLACER LE TODO DANS updatePersonRole()
 * 
 * Ajouter ceci après: person.role = role;
 */
async function updatePersonRoleInDatabase(personId, role) {
    try {
        const response = await fetch(`/api/properties/persons/${personId}/role`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ role })
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Erreur:', data.message);
            alert('Erreur lors de la mise à jour');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour');
    }
}

/**
 * REMPLACER LE TODO DANS removePerson()
 * 
 * Ajouter ceci après la suppression de la ligne
 */
async function removePersonFromDatabase(personId) {
    try {
        const response = await fetch(`/api/properties/persons/${personId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Erreur:', data.message);
            alert('Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}

// ========== OPPORTUNITÉS LIÉES ==========

/**
 * REMPLACER LE TODO DANS confirmAddOpportunity()
 * 
 * Ajouter ceci après: closeAddOpportunityModal();
 */
async function saveOpportunityToDatabase(opportunity, propertyId) {
    try {
        const response = await fetch('/api/properties/opportunities', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                property_listing_id: propertyId,
                opportunity_id: opportunity.id,
                name: opportunity.name,
                pipeline_id: opportunity.pipelineId,
                pipeline_stage_id: opportunity.pipelineStageId,
                source: opportunity.source,
                status: opportunity.status
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Erreur:', data.message);
            alert('Erreur lors de la sauvegarde');
            return null;
        }

        return data.opportunity;
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la sauvegarde');
        return null;
    }
}

/**
 * REMPLACER LE TODO DANS removeOpportunity()
 * 
 * Ajouter ceci après la suppression de la ligne
 */
async function removeOpportunityFromDatabase(opportunityId) {
    try {
        const response = await fetch(`/api/properties/opportunities/${opportunityId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Erreur:', data.message);
            alert('Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}
