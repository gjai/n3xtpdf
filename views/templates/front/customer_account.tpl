{*
* Template pour l'affichage dans le compte client
* 
* @author n3xtpdf Team
* @version 1.0.0
*}

<div class="n3xtpdf-customer-account">
    <div class="links">
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" 
           href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'upload'])|escape:'html':'UTF-8'}"
           title="Télécharger un nouveau BAT PDF">
            <span class="link-item">
                <i class="material-icons">&#xE2C6;</i>
                Télécharger un BAT PDF
            </span>
        </a>
        
        <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" 
           href="{$link->getModuleLink('n3xtpdf', 'batupload', ['action' => 'list'])|escape:'html':'UTF-8'}"
           title="Consulter mes BAT PDF">
            <span class="link-item">
                <i class="material-icons">&#xE2C7;</i>
                Mes BAT PDF
            </span>
        </a>
    </div>
</div>