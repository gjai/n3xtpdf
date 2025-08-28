{*
* Template pour la liste des BAT du client
* 
* @author n3xtpdf Team
* @version 1.0.0
*}

{extends file='page.tpl'}

{block name='page_title'}
    <h1>Mes BAT PDF</h1>
{/block}

{block name='page_content'}
<div class="n3xtpdf-bat-list">
    
    <div class="page-actions">
        <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'upload'])|escape:'html':'UTF-8'}" 
           class="btn btn-primary">
            <i class="icon icon-upload"></i> Télécharger un nouveau BAT
        </a>
    </div>

    {if isset($bats) && $bats && count($bats) > 0}
        <div class="bats-grid">
            {foreach from=$bats item=bat}
                <div class="bat-item status-{$bat.status|escape:'html':'UTF-8'}" data-bat-id="{$bat.id_bat|escape:'html':'UTF-8'}">
                    <div class="bat-header">
                        <h3 class="bat-title">{$bat.original_filename|escape:'html':'UTF-8'}</h3>
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
                        <div class="info-row">
                            <div class="info-item">
                                <label>Commande :</label>
                                <span>{$bat.order_reference|escape:'html':'UTF-8'}</span>
                            </div>
                            <div class="info-item">
                                <label>Date :</label>
                                <span>{$bat.upload_date|date_format:'%d/%m/%Y'}</span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-item">
                                <label>Taille :</label>
                                <span>{($bat.file_size/1024/1024)|string_format:"%.1f"} MB</span>
                            </div>
                            {if $bat.response_date}
                                <div class="info-item">
                                    <label>Réponse :</label>
                                    <span>{$bat.response_date|date_format:'%d/%m/%Y'}</span>
                                </div>
                            {/if}
                        </div>

                        <div class="validation-checks">
                            <div class="check-item {if $bat.is_cmyk}valid{else}invalid{/if}">
                                <i class="icon {if $bat.is_cmyk}icon-check{else}icon-times{/if}"></i>
                                <small>CMJN</small>
                            </div>
                            <div class="check-item {if $bat.has_cutting_layer}valid{else}invalid{/if}">
                                <i class="icon {if $bat.has_cutting_layer}icon-check{else}icon-times{/if}"></i>
                                <small>Découpe</small>
                            </div>
                        </div>
                    </div>

                    {if $bat.status == 'accepted'}
                        <div class="status-info accepted">
                            <i class="icon icon-check-circle"></i>
                            <div>
                                <strong>Production en cours</strong>
                                {if $bat.production_start_date}
                                    <small>Démarrée le {$bat.production_start_date|date_format:'%d/%m/%Y'}</small>
                                {/if}
                            </div>
                        </div>
                    {elseif $bat.status == 'rejected'}
                        <div class="status-info rejected">
                            <i class="icon icon-times-circle"></i>
                            <div>
                                <strong>BAT refusé</strong>
                                <small>Correction nécessaire</small>
                            </div>
                        </div>
                    {elseif $bat.status == 'pending'}
                        <div class="status-info pending">
                            <i class="icon icon-clock"></i>
                            <div>
                                <strong>En attente</strong>
                                <small>Validation en cours</small>
                            </div>
                        </div>
                    {/if}

                    <div class="bat-actions">
                        <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'view', 'token' => $bat.secure_token])|escape:'html':'UTF-8'}" 
                           class="btn btn-primary btn-sm">
                            <i class="icon icon-eye"></i> Consulter
                        </a>
                        
                        <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'download', 'token' => $bat.secure_token])|escape:'html':'UTF-8'}" 
                           class="btn btn-secondary btn-sm">
                            <i class="icon icon-download"></i> Télécharger
                        </a>

                        {if $bat.status == 'rejected'}
                            <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'upload'])|escape:'html':'UTF-8'}" 
                               class="btn btn-warning btn-sm">
                                <i class="icon icon-upload"></i> Nouveau BAT
                            </a>
                        {/if}
                    </div>
                </div>
            {/foreach}
        </div>

        <!-- Statistiques -->
        <div class="bat-statistics mt-4">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">
                        {assign var="pending_count" value=0}
                        {foreach from=$bats item=bat}
                            {if $bat.status == 'pending'}
                                {assign var="pending_count" value=$pending_count+1}
                            {/if}
                        {/foreach}
                        {$pending_count}
                    </div>
                    <div class="stat-label">En attente</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">
                        {assign var="accepted_count" value=0}
                        {foreach from=$bats item=bat}
                            {if $bat.status == 'accepted'}
                                {assign var="accepted_count" value=$accepted_count+1}
                            {/if}
                        {/foreach}
                        {$accepted_count}
                    </div>
                    <div class="stat-label">Acceptés</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">
                        {assign var="rejected_count" value=0}
                        {foreach from=$bats item=bat}
                            {if $bat.status == 'rejected'}
                                {assign var="rejected_count" value=$rejected_count+1}
                            {/if}
                        {/foreach}
                        {$rejected_count}
                    </div>
                    <div class="stat-label">Refusés</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">{count($bats)}</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
        </div>

    {else}
        <div class="no-bats">
            <div class="no-bats-icon">
                <i class="icon icon-file-pdf"></i>
            </div>
            <h3>Aucun BAT téléchargé</h3>
            <p>Vous n'avez pas encore téléchargé de BAT PDF. Commencez par télécharger votre premier fichier.</p>
            <a href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'upload'])|escape:'html':'UTF-8'}" 
               class="btn btn-primary btn-lg">
                <i class="icon icon-upload"></i> Télécharger mon premier BAT
            </a>
        </div>
    {/if}

    <div class="help-section mt-5">
        <h4>Besoin d'aide ?</h4>
        <div class="help-grid">
            <div class="help-item">
                <h6><i class="icon icon-question-circle"></i> Statuts des BAT</h6>
                <ul>
                    <li><span class="status-indicator pending"></span> <strong>En attente :</strong> Votre BAT est en cours d'examen</li>
                    <li><span class="status-indicator accepted"></span> <strong>Accepté :</strong> Production en cours</li>
                    <li><span class="status-indicator rejected"></span> <strong>Refusé :</strong> Corrections nécessaires</li>
                </ul>
            </div>
            
            <div class="help-item">
                <h6><i class="icon icon-info-circle"></i> Consignes techniques</h6>
                <ul>
                    <li>Format PDF avec mode colorimétrique CMJN</li>
                    <li>Calque "découpe" séparé si nécessaire</li>
                    <li>Résolution minimum 300 DPI</li>
                    <li>Fond perdu de 3mm</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.bats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.bat-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background: white;
    transition: all 0.3s ease;
}

.bat-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.info-item {
    flex: 1;
}

.info-item label {
    font-weight: bold;
    color: #666;
    font-size: 0.9em;
}

.status-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 5px;
    margin: 15px 0;
}

.status-info.pending {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
}

.status-info.accepted {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
}

.status-info.rejected {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

.no-bats {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.no-bats-icon {
    font-size: 4em;
    color: #dee2e6;
    margin-bottom: 20px;
}

.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.help-item ul {
    list-style: none;
    padding-left: 0;
}

.help-item li {
    padding: 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.pending {
    background-color: #ffc107;
}

.status-indicator.accepted {
    background-color: #28a745;
}

.status-indicator.rejected {
    background-color: #dc3545;
}

@media (max-width: 768px) {
    .bats-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .info-row {
        flex-direction: column;
        gap: 5px;
    }
}
</style>
{/block}