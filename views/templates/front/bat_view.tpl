{*
* Template pour l'affichage d'un BAT spécifique
* 
* @author n3xtpdf Team
* @version 1.0.0
*}

{extends file='page.tpl'}

{block name='page_title'}
    <h1>Consultation du BAT PDF</h1>
{/block}

{block name='page_content'}
<div class="n3xtpdf-bat-view">
    
    {if isset($errors) && $errors}
        <div class="alert alert-danger">
            <h4><i class="icon icon-exclamation-triangle"></i> Erreurs</h4>
            <ul>
                {foreach from=$errors item=error}
                    <li>{$error|escape:'html':'UTF-8'}</li>
                {/foreach}
            </ul>
        </div>
    {/if}
    
    {if isset($success_message)}
        <div class="alert alert-success">
            <i class="icon icon-check"></i> {$success_message|escape:'html':'UTF-8'}
        </div>
    {/if}

    {if isset($bat) && $bat}
        <div class="bat-item status-{$bat.status|escape:'html':'UTF-8'}">
            <div class="bat-header">
                <h2 class="bat-title">{$bat.original_filename|escape:'html':'UTF-8'}</h2>
                <span class="bat-status {$bat.status|escape:'html':'UTF-8'}">
                    {if $bat.status == 'pending'}
                        <i class="icon icon-clock"></i> En attente
                    {elseif $bat.status == 'accepted'}
                        <i class="icon icon-check"></i> Accepté
                    {elseif $bat.status == 'rejected'}
                        <i class="icon icon-times"></i> Refusé
                    {/if}
                </span>
            </div>

            <div class="bat-info">
                <div class="info-section">
                    <h6>Informations générales</h6>
                    <ul class="info-list">
                        <li><strong>Référence commande :</strong> {$bat.order_reference|escape:'html':'UTF-8'}</li>
                        <li><strong>Date de téléchargement :</strong> {$bat.upload_date|date_format:'%d/%m/%Y à %H:%M'}</li>
                        <li><strong>Taille du fichier :</strong> {($bat.file_size/1024/1024)|string_format:"%.2f"} MB</li>
                        {if $bat.response_date}
                            <li><strong>Date de réponse :</strong> {$bat.response_date|date_format:'%d/%m/%Y à %H:%M'}</li>
                        {/if}
                        {if $bat.production_start_date}
                            <li><strong>Production démarrée :</strong> {$bat.production_start_date|date_format:'%d/%m/%Y à %H:%M'}</li>
                        {/if}
                        {if $bat.estimated_delivery_date}
                            <li><strong>Livraison estimée :</strong> {$bat.estimated_delivery_date|date_format:'%d/%m/%Y'}</li>
                        {/if}
                    </ul>
                </div>
                
                <div class="info-section">
                    <h6>Vérifications techniques</h6>
                    <div class="validation-checks">
                        <div class="check-item {if $bat.is_cmyk}valid{else}invalid{/if}">
                            <i class="icon {if $bat.is_cmyk}icon-check{else}icon-times{/if}"></i>
                            <span>Mode CMJN {if $bat.is_cmyk}détecté{else}non détecté{/if}</span>
                        </div>
                        <div class="check-item {if $bat.has_cutting_layer}valid{else}invalid{/if}">
                            <i class="icon {if $bat.has_cutting_layer}icon-check{else}icon-times{/if}"></i>
                            <span>Calque découpe {if $bat.has_cutting_layer}détecté{else}non détecté{/if}</span>
                        </div>
                    </div>
                </div>
            </div>

            {if $bat.validation_notes}
                <div class="validation-notes">
                    <h6><i class="icon icon-info-circle"></i> Notes de validation :</h6>
                    <div class="notes-content">
                        {$bat.validation_notes|nl2br|escape:'html':'UTF-8'}
                    </div>
                </div>
            {/if}

            {if isset($guidelines) && $guidelines}
                <div class="technical-guidelines">
                    <h6><i class="icon icon-exclamation-triangle"></i> Consignes techniques</h6>
                    <ul class="guidelines-list">
                        {foreach from=$guidelines key=key item=guideline}
                            <li>{$guideline|escape:'html':'UTF-8'}</li>
                        {/foreach}
                    </ul>
                </div>
            {/if}

            <div class="bat-actions">
                <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'download', 'token' => $bat.secure_token])|escape:'html':'UTF-8'}" 
                   class="btn btn-primary">
                    <i class="icon icon-download"></i> Télécharger le PDF
                </a>

                {if $can_validate && $bat.status == 'pending'}
                    <form method="post" style="display: inline-block;">
                        <input type="hidden" name="token" value="{$bat.secure_token|escape:'html':'UTF-8'}">
                        <button type="submit" name="validateBat" class="btn btn-success">
                            <i class="icon icon-check"></i> Accepter le BAT
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger" onclick="showRejectModal()">
                        <i class="icon icon-times"></i> Refuser le BAT
                    </button>
                {/if}

                <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'list'])|escape:'html':'UTF-8'}" 
                   class="btn btn-secondary">
                    <i class="icon icon-list"></i> Retour à la liste
                </a>
            </div>

            {if $bat.status == 'accepted'}
                <div class="alert alert-success">
                    <h6><i class="icon icon-check-circle"></i> BAT accepté</h6>
                    <p>Votre BAT a été accepté. La production a commencé le {$bat.production_start_date|date_format:'%d/%m/%Y à %H:%M'}.</p>
                    {if $bat.estimated_delivery_date}
                        <p><strong>Livraison estimée :</strong> {$bat.estimated_delivery_date|date_format:'%d/%m/%Y'}</p>
                    {/if}
                </div>
            {elseif $bat.status == 'rejected'}
                <div class="alert alert-danger">
                    <h6><i class="icon icon-times-circle"></i> BAT refusé</h6>
                    <p>Votre BAT a été refusé. Veuillez télécharger une nouvelle version corrigée.</p>
                    <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'upload'])|escape:'html':'UTF-8'}" 
                       class="btn btn-primary">
                        <i class="icon icon-upload"></i> Télécharger une nouvelle version
                    </a>
                </div>
            {elseif $bat.status == 'pending'}
                <div class="alert alert-warning">
                    <h6><i class="icon icon-clock"></i> En attente de validation</h6>
                    <p>Votre BAT est en cours d'examen. Vous recevrez une notification dès qu'il sera validé ou si des modifications sont nécessaires.</p>
                </div>
            {/if}
        </div>

        <!-- Modal de rejet -->
        <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h4 class="modal-title">Refuser le BAT</h4>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="token" value="{$bat.secure_token|escape:'html':'UTF-8'}">
                            <div class="form-group">
                                <label for="rejection_notes">Raison du refus <span class="required">*</span></label>
                                <textarea id="rejection_notes" 
                                          name="rejection_notes" 
                                          class="form-control" 
                                          rows="4" 
                                          placeholder="Expliquez pourquoi vous refusez ce BAT..."
                                          required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                            <button type="submit" name="rejectBat" class="btn btn-danger">
                                <i class="icon icon-times"></i> Confirmer le refus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    {else}
        <div class="alert alert-danger">
            <h4><i class="icon icon-exclamation-triangle"></i> BAT non trouvé</h4>
            <p>Le BAT demandé n'existe pas ou vous n'avez pas les droits pour le consulter.</p>
            <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'list'])|escape:'html':'UTF-8'}" 
               class="btn btn-primary">
                <i class="icon icon-list"></i> Voir mes BAT
            </a>
        </div>
    {/if}
</div>
{/block}

{block name='page_footer'}
    <script>
    function showRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (modal) {
            if (typeof $ !== 'undefined' && $.fn.modal) {
                $(modal).modal('show');
            } else {
                modal.style.display = 'block';
            }
        }
    }

    // Auto-actualisation pour les BAT en attente
    {if $bat.status == 'pending'}
        setTimeout(function() {
            location.reload();
        }, 60000); // Actualiser toutes les minutes
    {/if}
    </script>
{/block}