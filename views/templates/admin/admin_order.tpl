{*
* Template pour l'affichage des BAT dans l'administration des commandes
* 
* @author n3xtpdf Team
* @version 1.0.0
*}

<div class="panel n3xtpdf-admin-order">
    <div class="panel-heading">
        <i class="icon-file-pdf"></i>
        BAT PDF liés à cette commande
    </div>
    <div class="panel-body">
        {if $bats && count($bats) > 0}
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fichier</th>
                            <th>Date d'upload</th>
                            <th>Statut</th>
                            <th>Validations techniques</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$bats item=bat}
                            <tr class="bat-row bat-status-{$bat.status|escape:'html':'UTF-8'}">
                                <td>
                                    <strong>{$bat.original_filename|escape:'html':'UTF-8'}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {($bat.file_size/1024/1024)|string_format:"%.2f"} MB
                                    </small>
                                </td>
                                <td>
                                    {$bat.upload_date|date_format:'%d/%m/%Y à %H:%M'}
                                    <br>
                                    <small class="text-muted">
                                        Par: {$bat.firstname|escape:'html':'UTF-8'} {$bat.lastname|escape:'html':'UTF-8'}
                                    </small>
                                </td>
                                <td>
                                    {if $bat.status == 'pending'}
                                        <span class="badge badge-warning">
                                            <i class="icon-clock"></i> En attente
                                        </span>
                                    {elseif $bat.status == 'accepted'}
                                        <span class="badge badge-success">
                                            <i class="icon-check"></i> Accepté
                                        </span>
                                        {if $bat.production_start_date}
                                            <br><small>Production: {$bat.production_start_date|date_format:'%d/%m/%Y'}</small>
                                        {/if}
                                    {elseif $bat.status == 'rejected'}
                                        <span class="badge badge-danger">
                                            <i class="icon-times"></i> Refusé
                                        </span>
                                    {/if}
                                    
                                    {if $bat.response_date}
                                        <br><small>Réponse: {$bat.response_date|date_format:'%d/%m/%Y'}</small>
                                    {/if}
                                </td>
                                <td>
                                    <div class="validation-checks">
                                        <div class="check-item">
                                            {if $bat.is_cmyk}
                                                <i class="icon-check text-success"></i>
                                            {else}
                                                <i class="icon-times text-danger"></i>
                                            {/if}
                                            <small>CMJN</small>
                                        </div>
                                        <div class="check-item">
                                            {if $bat.has_cutting_layer}
                                                <i class="icon-check text-success"></i>
                                            {else}
                                                <i class="icon-times text-danger"></i>
                                            {/if}
                                            <small>Découpe</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="{$module_dir|escape:'html':'UTF-8'}downloads.php?token={$bat.secure_token|escape:'html':'UTF-8'}" 
                                           class="btn btn-default btn-sm"
                                           title="Télécharger le PDF">
                                            <i class="icon-download"></i> Télécharger
                                        </a>
                                        
                                        {if $bat.status == 'pending'}
                                            <button type="button" 
                                                    class="btn btn-success btn-sm" 
                                                    onclick="validateBat({$bat.id_bat|escape:'html':'UTF-8'})"
                                                    title="Accepter le BAT">
                                                <i class="icon-check"></i> Accepter
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="rejectBat({$bat.id_bat|escape:'html':'UTF-8'})"
                                                    title="Refuser le BAT">
                                                <i class="icon-times"></i> Refuser
                                            </button>
                                        {/if}
                                        
                                        <button type="button" 
                                                class="btn btn-info btn-sm" 
                                                onclick="showBatDetails({$bat.id_bat|escape:'html':'UTF-8'})"
                                                title="Voir les détails">
                                            <i class="icon-info"></i> Détails
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            {if $bat.validation_notes}
                                <tr class="bat-notes-row">
                                    <td colspan="5">
                                        <div class="alert alert-info">
                                            <strong>Notes de validation :</strong><br>
                                            {$bat.validation_notes|nl2br|escape:'html':'UTF-8'}
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                        {/foreach}
                    </tbody>
                </table>
            </div>
            
            <div class="panel-footer">
                <div class="btn-group">
                    <button type="button" class="btn btn-default" onclick="refreshBatList()">
                        <i class="icon-refresh"></i> Actualiser
                    </button>
                    <button type="button" class="btn btn-info" onclick="showBatConsultations()">
                        <i class="icon-eye"></i> Voir consultations
                    </button>
                </div>
            </div>
            
        {else}
            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                Aucun BAT n'a été téléchargé pour cette commande.
                <br><br>
                <strong>Référence de commande :</strong> {$order->reference|escape:'html':'UTF-8'}
                <br>
                Le client peut télécharger un BAT en utilisant cette référence.
            </div>
        {/if}
    </div>
</div>

<!-- Modal pour validation/rejet -->
<div class="modal fade" id="batActionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title" id="batActionModalTitle">Action sur le BAT</h4>
            </div>
            <div class="modal-body">
                <form id="batActionForm">
                    <input type="hidden" id="batActionId" name="id_bat">
                    <input type="hidden" id="batActionType" name="action_type">
                    
                    <div class="form-group">
                        <label for="batActionNotes">Notes :</label>
                        <textarea id="batActionNotes" 
                                  name="notes" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Ajoutez vos commentaires..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="submitBatAction()">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
function validateBat(idBat) {
    $('#batActionModalTitle').text('Accepter le BAT');
    $('#batActionId').val(idBat);
    $('#batActionType').val('accept');
    $('#batActionNotes').attr('placeholder', 'Notes sur l\'acceptation (optionnel)...');
    $('#batActionModal').modal('show');
}

function rejectBat(idBat) {
    $('#batActionModalTitle').text('Refuser le BAT');
    $('#batActionId').val(idBat);
    $('#batActionType').val('reject');
    $('#batActionNotes').attr('placeholder', 'Raison du refus (obligatoire)...');
    $('#batActionModal').modal('show');
}

function submitBatAction() {
    const formData = new FormData(document.getElementById('batActionForm'));
    const actionType = formData.get('action_type');
    const notes = formData.get('notes');
    
    if (actionType === 'reject' && !notes.trim()) {
        alert('Les notes sont obligatoires pour un refus.');
        return;
    }
    
    // Ici, vous feriez un appel AJAX vers un contrôleur d'administration
    // pour traiter l'action sur le BAT
    
    $.ajax({
        url: 'ajax_bat_action.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#batActionModal').modal('hide');
                location.reload(); // Recharger la page pour voir les changements
            } else {
                alert('Erreur : ' + response.message);
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur.');
        }
    });
}

function showBatDetails(idBat) {
    // Afficher les détails complets d'un BAT
    window.open('index.php?controller=AdminN3xtpdf&action=viewBat&id_bat=' + idBat, '_blank');
}

function refreshBatList() {
    location.reload();
}

function showBatConsultations() {
    // Afficher les consultations pour cette commande
    const orderId = {$order->id|escape:'html':'UTF-8'};
    window.open('index.php?controller=AdminN3xtpdf&action=consultations&id_order=' + orderId, '_blank');
}
</script>

<style>
.n3xtpdf-admin-order .validation-checks {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.n3xtpdf-admin-order .check-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.bat-status-pending {
    background-color: #fff3cd !important;
}

.bat-status-accepted {
    background-color: #d4edda !important;
}

.bat-status-rejected {
    background-color: #f8d7da !important;
}

.bat-notes-row td {
    border-top: none !important;
    background-color: #f8f9fa !important;
}

.btn-group-vertical .btn {
    margin-bottom: 2px;
}
</style>