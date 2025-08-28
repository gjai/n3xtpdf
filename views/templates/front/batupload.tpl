{*
* Template pour l'upload de BAT PDF
* 
* @author n3xtpdf Team
* @version 1.0.0
*}

{extends file='page.tpl'}

{block name='page_title'}
    <h1>Télécharger un BAT PDF</h1>
{/block}

{block name='page_content'}
<div class="n3xtpdf-upload">
    
    {if isset($success) && $success}
        <div class="alert alert-success">
            <h4><i class="icon icon-check"></i> BAT téléchargé avec succès !</h4>
            <p>Votre BAT a été téléchargé et analysé. Voici les résultats de la validation :</p>
            
            {if isset($validation_results)}
                <ul class="validation-results">
                    {foreach from=$validation_results.validation_notes item=note}
                        <li>{$note|escape:'html':'UTF-8'}</li>
                    {/foreach}
                </ul>
            {/if}
            
            <p>
                <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'view', 'token' => $bat_token])|escape:'html':'UTF-8'}" 
                   class="btn btn-primary">
                    <i class="icon icon-eye"></i> Consulter votre BAT
                </a>
            </p>
        </div>
    {else}
        
        {if isset($errors) && $errors}
            <div class="alert alert-danger">
                <h4><i class="icon icon-exclamation-triangle"></i> Erreurs détectées</h4>
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

        {if isset($guidelines) && $guidelines}
            <div class="technical-guidelines alert alert-info">
                <h4><i class="icon icon-info-circle"></i> Consignes techniques obligatoires</h4>
                <ul class="guidelines-list">
                    {foreach from=$guidelines key=key item=guideline}
                        <li><strong>{$guideline|escape:'html':'UTF-8'}</strong></li>
                    {/foreach}
                </ul>
                <p><small>Le respect de ces consignes est essentiel pour une production optimale.</small></p>
            </div>
        {/if}

        <div class="card">
            <div class="card-header">
                <h3><i class="icon icon-upload"></i> Télécharger votre BAT</h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="bat-upload-form">
                    
                    <div class="form-group">
                        <label for="order_reference" class="form-control-label required">
                            Référence de commande
                        </label>
                        <input type="text" 
                               id="order_reference" 
                               name="order_reference" 
                               class="form-control" 
                               value="{if isset($smarty.post.order_reference)}{$smarty.post.order_reference|escape:'html':'UTF-8'}{/if}"
                               required>
                        <small class="form-text text-muted">
                            Indiquez la référence de votre commande pour identifier le BAT
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="pdf_file" class="form-control-label required">
                            Fichier PDF
                        </label>
                        <input type="file" 
                               id="pdf_file" 
                               name="pdf_file" 
                               class="form-control-file" 
                               accept=".pdf"
                               required>
                        <small class="form-text text-muted">
                            <i class="icon icon-info-circle"></i> 
                            Seuls les fichiers PDF sont acceptés (max {$max_file_size|escape:'html':'UTF-8'}MB)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-control-label">
                            Notes (optionnel)
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Ajoutez des notes si nécessaire...">{if isset($smarty.post.notes)}{$smarty.post.notes|escape:'html':'UTF-8'}{/if}</textarea>
                        <small class="form-text text-muted">
                            Informations complémentaires sur votre projet
                        </small>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="icon icon-lightbulb"></i> Important à retenir :</h6>
                        <ul class="mb-0">
                            <li>Le fichier sera automatiquement vérifié pour les consignes techniques</li>
                            <li>Vous recevrez un lien sécurisé pour consulter votre BAT</li>
                            <li>Le délai de production commence à l'acceptation du BAT</li>
                            <li>Des rappels automatiques seront envoyés si le BAT n'est pas validé</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="submitBatUpload" class="btn btn-primary btn-lg">
                            <i class="icon icon-upload"></i> Télécharger le BAT
                        </button>
                        
                        <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'list'])|escape:'html':'UTF-8'}" 
                           class="btn btn-secondary">
                            <i class="icon icon-list"></i> Mes BAT
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="help-section mt-4">
            <h4>Aide et Support</h4>
            <div class="row">
                <div class="col-md-6">
                    <h6>Questions fréquentes</h6>
                    <ul>
                        <li>Comment vérifier que mon fichier est en CMJN ?</li>
                        <li>Comment créer un calque de découpe ?</li>
                        <li>Quelle résolution utiliser ?</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Contact</h6>
                    <p>En cas de problème, contactez notre équipe technique.</p>
                </div>
            </div>
        </div>
    {/if}
</div>
{/block}

{block name='page_footer'}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validation côté client
        const form = document.querySelector('.bat-upload-form');
        const fileInput = document.getElementById('pdf_file');
        const maxSize = {$max_file_size|escape:'html':'UTF-8'} * 1024 * 1024; // Conversion en bytes
        
        if (form) {
            form.addEventListener('submit', function(e) {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    
                    // Vérification de la taille
                    if (file.size > maxSize) {
                        e.preventDefault();
                        alert('Le fichier est trop volumineux. Taille maximale : {$max_file_size|escape:'html':'UTF-8'}MB');
                        return false;
                    }
                    
                    // Vérification du type
                    if (file.type !== 'application/pdf') {
                        e.preventDefault();
                        alert('Seuls les fichiers PDF sont autorisés.');
                        return false;
                    }
                }
            });
        }
        
        // Affichage des informations sur le fichier sélectionné
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
                    
                    let info = document.querySelector('.file-info');
                    if (!info) {
                        info = document.createElement('small');
                        info.className = 'file-info form-text text-info';
                        this.parentNode.appendChild(info);
                    }
                    
                    info.innerHTML = `<i class="icon icon-file-pdf"></i> ${file.name} (${sizeInMB} MB)`;
                }
            });
        }
    });
    </script>
{/block}