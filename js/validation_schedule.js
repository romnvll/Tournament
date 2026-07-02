/**
 * Validation des doublons dans la table de planification
 * Vérifie qu'il n'y a pas de doublon d'équipe ou d'arbitre par ligne (TR)
 */
document.addEventListener('DOMContentLoaded', function() {
    validateScheduleTable();
});

function validateScheduleTable() {
    const table = document.getElementById('planification-table');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr:not(.changement-row)');
    
    rows.forEach((row, index) => {
        const validation = validateRow(row);
        
        if (!validation.isValid) {
            markRowAsInvalid(row, validation.errors);
        } else {
            removeRowInvalidMarking(row);
        }
    });
}

/**
 * Valide une ligne de la table
 * @param {HTMLElement} row - La ligne à valider
 * @returns {Object} {isValid: boolean, errors: Array}
 */
function validateRow(row) {
    const errors = [];
    
    // Récupérer tous les arbitres de la ligne
    const arbitres = extractArbitresFromRow(row);
    const arbitresDoublons = findDuplicates(arbitres);
    if (arbitresDoublons.length > 0) {
        errors.push({
            type: 'arbitre',
            message: `Arbitre(s) en doublon: ${arbitresDoublons.join(', ')}`
        });
    }
    
    // Récupérer toutes les équipes de la ligne
    const equipes = extractEquipesFromRow(row);
    const equipesDoublons = findDuplicates(equipes);
    if (equipesDoublons.length > 0) {
        errors.push({
            type: 'equipe',
            message: `Équipe(s) en doublon: ${equipesDoublons.join(', ')}`
        });
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Extrait les noms des arbitres d'une ligne (SEULEMENT le nom, pas le club)
 * @param {HTMLElement} row - La ligne à analyser
 * @returns {Array} Liste des noms d'arbitres (sans les clubs)
 */
function extractArbitresFromRow(row) {
    const arbitres = [];
    const arbitreCards = row.querySelectorAll('.arbitre-card');
    
    arbitreCards.forEach(card => {
        const divContent = card.querySelector('div:last-child');
        if (divContent) {
            // Extraire uniquement les textNodes (pas les icônes)
            let fullText = '';
            divContent.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    fullText += node.textContent;
                }
            });
            
            // Split par whitespace et prendre SEULEMENT le premier token (le nom)
            // Le club est le 2e token et on l'ignore
            const tokens = fullText.trim().split(/\s+/).filter(t => t);
            if (tokens.length > 0) {
                const nomArbitre = tokens[0]; // Juste le nom, pas le club
                if (nomArbitre) arbitres.push(nomArbitre);
            }
        }
    });
    
    return arbitres;
}

/**
 * Extrait les noms des équipes d'une ligne
 * @param {HTMLElement} row - La ligne à analyser
 * @returns {Array} Liste des noms d'équipes
 */
function extractEquipesFromRow(row) {
    const equipes = [];
    const matchCards = row.querySelectorAll('.match-card');
    
    matchCards.forEach(card => {
        // Les équipes sont dans des <span> 
        const matchText = card.querySelector('.match-text');
        if (matchText) {
            const spans = matchText.querySelectorAll('span');
            spans.forEach(span => {
                const text = span.textContent.trim();
                // Ignorer "vs" et les textes vides et les petites classes
                if (text && text !== 'vs' && !span.classList.contains('text-muted')) {
                    equipes.push(text);
                }
            });
        }
    });
    
    return equipes;
}

/**
 * Trouve les doublons dans un tableau
 * @param {Array} array - Tableau à analyser
 * @returns {Array} Liste des doublons trouvés
 */
function findDuplicates(array) {
    const seen = new Set();
    const duplicates = new Set();
    
    array.forEach(item => {
        if (seen.has(item)) {
            duplicates.add(item);
        }
        seen.add(item);
    });
    
    return Array.from(duplicates);
}

/**
 * Marque une ligne comme invalide
 * @param {HTMLElement} row - La ligne à marquer
 * @param {Array} errors - Les erreurs détectées
 */
function markRowAsInvalid(row, errors) {
    // Ajouter la classe visuelle
    row.classList.add('row-validation-error');
    
    // Créer un message d'erreur
    const errorMessages = errors.map(e => e.message).join(' | ');
    row.setAttribute('data-validation-error', errorMessages);
    
    // Ajouter une icône d'alerte
    addErrorIcon(row, errorMessages);
}

/**
 * Retire le marquage d'erreur d'une ligne
 * @param {HTMLElement} row - La ligne à nettoyer
 */
function removeRowInvalidMarking(row) {
    row.classList.remove('row-validation-error');
    row.removeAttribute('data-validation-error');
    
    // Retirer l'icône d'alerte
    const errorIcon = row.querySelector('.validation-error-icon');
    if (errorIcon) {
        errorIcon.remove();
    }
}

/**
 * Ajoute une icône d'alerte à la ligne
 * @param {HTMLElement} row - La ligne à marquer
 * @param {String} message - Le message d'erreur
 */
function addErrorIcon(row, message) {
    // Vérifier si l'icône existe déjà
    if (row.querySelector('.validation-error-icon')) {
        row.querySelector('.validation-error-icon').remove();
    }
    
    const timeslotCell = row.querySelector('.timeslot-cell');
    if (timeslotCell) {
        const icon = document.createElement('div');
        icon.className = 'validation-error-icon';
        icon.title = message;
        icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        
        // Ajouter le tooltip
        icon.setAttribute('data-bs-toggle', 'tooltip');
        icon.setAttribute('data-bs-placement', 'right');
        
        timeslotCell.appendChild(icon);
        
        // Initialiser le tooltip Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            try {
                new bootstrap.Tooltip(icon);
            } catch (e) {
                console.log('Bootstrap Tooltip non disponible');
            }
        }
    }
}

/**
 * Révalide la table après une modification
 */
function revalidateScheduleTable() {
    validateScheduleTable();
}

// Ré-exporter les fonctions pour utilisation externe
window.validateScheduleTable = validateScheduleTable;
window.revalidateScheduleTable = revalidateScheduleTable;