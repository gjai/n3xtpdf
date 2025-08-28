/**
 * JavaScript pour le module n3xtpdf
 * 
 * @author n3xtpdf Team
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialisation du module
    N3xtpdf.init();
    
});

var N3xtpdf = {
    
    // Configuration
    config: {
        maxFileSize: 50 * 1024 * 1024, // 50MB par défaut
        allowedTypes: ['application/pdf'],
        uploadUrl: '',
        validateUrl: ''
    },
    
    // Initialisation
    init: function() {
        this.initFileUpload();
        this.initBatActions();
        this.initValidation();
        this.initTooltips();
    },
    
    // Initialisation de l'upload de fichiers
    initFileUpload: function() {
        const fileInput = document.getElementById('pdf_file');
        const form = document.querySelector('.bat-upload-form');
        
        if (fileInput) {
            // Drag & Drop
            this.initDragDrop(fileInput);
            
            // Validation en temps réel
            fileInput.addEventListener('change', this.validateFile.bind(this));
            
            // Progress bar (si upload AJAX)
            if (form && form.hasAttribute('data-ajax')) {
                form.addEventListener('submit', this.handleAjaxUpload.bind(this));
            }
        }
    },
    
    // Initialisation du drag & drop
    initDragDrop: function(fileInput) {
        const dropZone = fileInput.closest('.form-group');
        
        if (!dropZone) return;
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, this.preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function() {
                dropZone.classList.add('drag-over');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function() {
                dropZone.classList.remove('drag-over');
            }, false);
        });
        
        dropZone.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                N3xtpdf.validateFile.call(N3xtpdf, { target: fileInput });
            }
        }, false);
    },
    
    // Prévenir les comportements par défaut
    preventDefaults: function(e) {
        e.preventDefault();
        e.stopPropagation();
    },
    
    // Validation de fichier
    validateFile: function(e) {
        const file = e.target.files[0];
        const messageContainer = this.getOrCreateMessageContainer(e.target);
        
        if (!file) {
            this.clearMessages(messageContainer);
            return;
        }
        
        const errors = [];
        
        // Vérification du type
        if (!this.config.allowedTypes.includes(file.type)) {
            errors.push('Seuls les fichiers PDF sont autorisés.');
        }
        
        // Vérification de la taille
        if (file.size > this.config.maxFileSize) {
            const maxSizeMB = Math.round(this.config.maxFileSize / 1024 / 1024);
            errors.push(`Le fichier est trop volumineux. Taille maximale : ${maxSizeMB}MB.`);
        }
        
        // Affichage des erreurs ou des informations
        if (errors.length > 0) {
            this.showErrors(messageContainer, errors);
            e.target.value = ''; // Vider le champ
        } else {
            this.showFileInfo(messageContainer, file);
        }
    },
    
    // Obtenir ou créer le conteneur de messages
    getOrCreateMessageContainer: function(input) {
        let container = input.parentNode.querySelector('.file-validation-messages');
        if (!container) {
            container = document.createElement('div');
            container.className = 'file-validation-messages';
            input.parentNode.appendChild(container);
        }
        return container;
    },
    
    // Vider les messages
    clearMessages: function(container) {
        container.innerHTML = '';
    },
    
    // Afficher les erreurs
    showErrors: function(container, errors) {
        const html = errors.map(error => `
            <div class="alert alert-danger">
                <i class="icon icon-exclamation-triangle"></i> ${error}
            </div>
        `).join('');
        container.innerHTML = html;
    },
    
    // Afficher les informations du fichier
    showFileInfo: function(container, file) {
        const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
        const html = `
            <div class="alert alert-info file-info">
                <i class="icon icon-file-pdf"></i> 
                <strong>${file.name}</strong> (${sizeInMB} MB)
                <div class="file-analysis">
                    <small>Analyse en cours...</small>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             style="width: 100%"></div>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML = html;
        
        // Simuler l'analyse (remplacer par une vraie analyse côté serveur)
        setTimeout(() => {
            this.showFileAnalysis(container, file);
        }, 2000);
    },
    
    // Afficher l'analyse du fichier
    showFileAnalysis: function(container, file) {
        // Simulation d'analyse - remplacer par un appel AJAX réel
        const analysis = {
            isValid: true,
            isCmyk: Math.random() > 0.5,
            hasCuttingLayer: Math.random() > 0.5,
            pages: Math.floor(Math.random() * 5) + 1
        };
        
        const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
        const html = `
            <div class="alert alert-success file-info">
                <i class="icon icon-file-pdf"></i> 
                <strong>${file.name}</strong> (${sizeInMB} MB)
                <div class="file-analysis-results">
                    <div class="analysis-grid">
                        <div class="analysis-item ${analysis.isCmyk ? 'valid' : 'warning'}">
                            <i class="icon ${analysis.isCmyk ? 'icon-check' : 'icon-exclamation-triangle'}"></i>
                            <span>CMJN ${analysis.isCmyk ? 'détecté' : 'non détecté'}</span>
                        </div>
                        <div class="analysis-item ${analysis.hasCuttingLayer ? 'valid' : 'warning'}">
                            <i class="icon ${analysis.hasCuttingLayer ? 'icon-check' : 'icon-exclamation-triangle'}"></i>
                            <span>Découpe ${analysis.hasCuttingLayer ? 'détectée' : 'non détectée'}</span>
                        </div>
                        <div class="analysis-item valid">
                            <i class="icon icon-file"></i>
                            <span>${analysis.pages} page(s)</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML = html;
    },
    
    // Initialisation des actions sur les BAT
    initBatActions: function() {
        // Boutons de validation/rejet
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('bat-validate-btn')) {
                e.preventDefault();
                N3xtpdf.showValidationModal(e.target.dataset.batId, 'accept');
            }
            
            if (e.target.classList.contains('bat-reject-btn')) {
                e.preventDefault();
                N3xtpdf.showValidationModal(e.target.dataset.batId, 'reject');
            }
        });
        
        // Actualisation automatique du statut
        this.initStatusRefresh();
    },
    
    // Afficher la modal de validation
    showValidationModal: function(batId, action) {
        const modal = document.getElementById('batValidationModal');
        if (!modal) {
            this.createValidationModal();
        }
        
        const modalTitle = document.getElementById('validationModalTitle');
        const modalBody = document.getElementById('validationModalBody');
        const actionInput = document.getElementById('validationAction');
        const batIdInput = document.getElementById('validationBatId');
        
        actionInput.value = action;
        batIdInput.value = batId;
        
        if (action === 'accept') {
            modalTitle.textContent = 'Accepter le BAT';
            modalBody.innerHTML = `
                <p>Êtes-vous sûr de vouloir accepter ce BAT ?</p>
                <div class="form-group">
                    <label>Notes (optionnel):</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Commentaires sur l'acceptation..."></textarea>
                </div>
            `;
        } else {
            modalTitle.textContent = 'Refuser le BAT';
            modalBody.innerHTML = `
                <p>Veuillez indiquer la raison du refus :</p>
                <div class="form-group">
                    <label>Raison du refus <span class="required">*</span>:</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Expliquez pourquoi ce BAT est refusé..." required></textarea>
                </div>
            `;
        }
        
        // Afficher la modal (adaptation selon le framework utilisé)
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modal).modal('show');
        } else {
            modal.style.display = 'block';
        }
    },
    
    // Créer la modal de validation
    createValidationModal: function() {
        const modalHTML = `
            <div class="modal fade" id="batValidationModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="validationModalTitle">Action sur le BAT</h4>
                            <button type="button" class="close" data-dismiss="modal">×</button>
                        </div>
                        <form id="batValidationForm">
                            <div class="modal-body" id="validationModalBody">
                                <input type="hidden" id="validationAction" name="action">
                                <input type="hidden" id="validationBatId" name="bat_id">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Confirmer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Gestionnaire de soumission
        document.getElementById('batValidationForm').addEventListener('submit', this.submitBatAction.bind(this));
    },
    
    // Soumettre une action sur un BAT
    submitBatAction: function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const action = formData.get('action');
        
        // Validation côté client
        if (action === 'reject' && !formData.get('notes').trim()) {
            alert('Les notes sont obligatoires pour un refus.');
            return;
        }
        
        // Affichage du loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Traitement...';
        submitBtn.disabled = true;
        
        // Appel AJAX
        fetch('index.php?fc=module&module=n3xtpdf&controller=batupload', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer la modal
                this.closeModal('batValidationModal');
                
                // Actualiser l'affichage
                this.refreshBatDisplay();
                
                // Afficher un message de succès
                this.showNotification('success', data.message || 'Action effectuée avec succès.');
            } else {
                this.showNotification('error', data.message || 'Erreur lors du traitement.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.showNotification('error', 'Erreur de communication avec le serveur.');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    },
    
    // Fermer une modal
    closeModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            if (typeof $ !== 'undefined' && $.fn.modal) {
                $(modal).modal('hide');
            } else {
                modal.style.display = 'none';
            }
        }
    },
    
    // Actualiser l'affichage des BAT
    refreshBatDisplay: function() {
        // Recharger la page ou mettre à jour via AJAX
        setTimeout(() => {
            location.reload();
        }, 1000);
    },
    
    // Afficher une notification
    showNotification: function(type, message) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} notification`;
        notification.innerHTML = `
            <i class="icon icon-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
            ${message}
            <button type="button" class="close" onclick="this.parentNode.remove()">×</button>
        `;
        
        // Insérer en haut de la page
        const container = document.querySelector('.n3xtpdf-upload') || document.body;
        container.insertBefore(notification, container.firstChild);
        
        // Auto-masquage après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    },
    
    // Initialisation de la validation de formulaire
    initValidation: function() {
        const forms = document.querySelectorAll('.bat-upload-form');
        forms.forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
        });
    },
    
    // Validation de formulaire
    validateForm: function(e) {
        const form = e.target;
        const orderRef = form.querySelector('[name="order_reference"]');
        const fileInput = form.querySelector('[name="pdf_file"]');
        
        let isValid = true;
        
        // Validation de la référence de commande
        if (!orderRef.value.trim()) {
            this.showFieldError(orderRef, 'La référence de commande est obligatoire.');
            isValid = false;
        } else {
            this.clearFieldError(orderRef);
        }
        
        // Validation du fichier
        if (!fileInput.files.length) {
            this.showFieldError(fileInput, 'Veuillez sélectionner un fichier PDF.');
            isValid = false;
        } else {
            this.clearFieldError(fileInput);
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    },
    
    // Afficher une erreur de champ
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        const error = document.createElement('div');
        error.className = 'field-error text-danger';
        error.textContent = message;
        
        field.parentNode.appendChild(error);
        field.classList.add('is-invalid');
    },
    
    // Vider les erreurs de champ
    clearFieldError: function(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.classList.remove('is-invalid');
    },
    
    // Initialisation des tooltips
    initTooltips: function() {
        // Si Bootstrap est disponible
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip();
        }
    },
    
    // Actualisation du statut des BAT
    initStatusRefresh: function() {
        const statusElements = document.querySelectorAll('[data-bat-status="pending"]');
        if (statusElements.length > 0) {
            // Vérifier les mises à jour toutes les 30 secondes
            setInterval(this.checkStatusUpdates.bind(this), 30000);
        }
    },
    
    // Vérifier les mises à jour de statut
    checkStatusUpdates: function() {
        const pendingBats = Array.from(document.querySelectorAll('[data-bat-id]'))
            .map(el => el.dataset.batId)
            .filter(id => id);
        
        if (pendingBats.length === 0) return;
        
        fetch('index.php?fc=module&module=n3xtpdf&controller=batupload&action=checkStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ bat_ids: pendingBats })
        })
        .then(response => response.json())
        .then(data => {
            if (data.updates && data.updates.length > 0) {
                this.refreshBatDisplay();
            }
        })
        .catch(error => console.error('Erreur lors de la vérification des statuts:', error));
    }
};