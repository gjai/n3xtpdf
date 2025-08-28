<?php
/**
 * Contrôleur d'administration pour la gestion des BAT
 * 
 * @author n3xtpdf Team
 * @version 1.0.0
 */

class AdminN3xtpdfController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'n3xtpdf_bat';
        $this->className = 'N3xtpdfBat';
        $this->identifier = 'id_bat';
        $this->lang = false;
        
        parent::__construct();
        
        $this->addRowAction('view');
        $this->addRowAction('accept');
        $this->addRowAction('reject');
        $this->addRowAction('download');
        
        $this->fields_list = [
            'id_bat' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'original_filename' => [
                'title' => 'Fichier',
                'search' => true
            ],
            'order_reference' => [
                'title' => 'Commande',
                'search' => true
            ],
            'customer_name' => [
                'title' => 'Client',
                'search' => false,
                'callback' => 'getCustomerName'
            ],
            'upload_date' => [
                'title' => 'Date upload',
                'type' => 'datetime'
            ],
            'status' => [
                'title' => 'Statut',
                'type' => 'select',
                'list' => [
                    'pending' => 'En attente',
                    'accepted' => 'Accepté',
                    'rejected' => 'Refusé'
                ],
                'filter_key' => 'a!status',
                'callback' => 'displayStatus'
            ],
            'is_cmyk' => [
                'title' => 'CMJN',
                'align' => 'center',
                'callback' => 'displayBoolean',
                'type' => 'bool'
            ],
            'has_cutting_layer' => [
                'title' => 'Découpe',
                'align' => 'center',
                'callback' => 'displayBoolean',
                'type' => 'bool'
            ]
        ];
        
        $this->_select = 'CONCAT(c.firstname, " ", c.lastname) as customer_name';
        $this->_join = 'LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (a.id_customer = c.id_customer)';
        
        $this->bulk_actions = [
            'accept' => [
                'text' => 'Accepter les BAT sélectionnés',
                'icon' => 'icon-check',
                'confirm' => 'Accepter les BAT sélectionnés ?'
            ],
            'reject' => [
                'text' => 'Refuser les BAT sélectionnés',
                'icon' => 'icon-times',
                'confirm' => 'Refuser les BAT sélectionnés ?'
            ]
        ];
    }
    
    public function getCustomerName($value, $row)
    {
        return $row['customer_name'];
    }
    
    public function displayStatus($value, $row)
    {
        $statuses = [
            'pending' => ['class' => 'label-warning', 'text' => 'En attente'],
            'accepted' => ['class' => 'label-success', 'text' => 'Accepté'],
            'rejected' => ['class' => 'label-danger', 'text' => 'Refusé']
        ];
        
        $status = $statuses[$value] ?? ['class' => 'label-default', 'text' => $value];
        
        return '<span class="label ' . $status['class'] . '">' . $status['text'] . '</span>';
    }
    
    public function displayBoolean($value, $row)
    {
        return $value ? '<i class="icon-check text-success"></i>' : '<i class="icon-times text-danger"></i>';
    }
    
    public function renderView()
    {
        $id_bat = (int)Tools::getValue('id_bat');
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        $bat = Db::getInstance()->getRow('
            SELECT b.*, CONCAT(c.firstname, " ", c.lastname) as customer_name, c.email
            FROM ' . _DB_PREFIX_ . 'n3xtpdf_bat b
            LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON b.id_customer = c.id_customer
            WHERE b.id_bat = ' . $id_bat
        );
        
        if (!$bat) {
            $this->errors[] = 'BAT non trouvé.';
            return parent::renderList();
        }
        
        $consultations = $batManager->getConsultations($id_bat, 20);
        
        $this->context->smarty->assign([
            'bat' => $bat,
            'consultations' => $consultations,
            'current_index' => self::$currentIndex,
            'token' => $this->token
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'n3xtpdf/views/templates/admin/bat_detail.tpl');
    }
    
    public function processAccept()
    {
        $id_bat = (int)Tools::getValue('id_bat');
        $notes = Tools::getValue('notes', '');
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        if ($batManager->updateBatStatus($id_bat, 'accepted', $notes, $this->context->employee->id)) {
            $this->confirmations[] = 'BAT accepté avec succès.';
        } else {
            $this->errors[] = 'Erreur lors de l\'acceptation du BAT.';
        }
    }
    
    public function processReject()
    {
        $id_bat = (int)Tools::getValue('id_bat');
        $notes = Tools::getValue('notes', '');
        
        if (empty($notes)) {
            $this->errors[] = 'Les notes sont obligatoires pour un refus.';
            return;
        }
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        if ($batManager->updateBatStatus($id_bat, 'rejected', $notes, $this->context->employee->id)) {
            $this->confirmations[] = 'BAT refusé avec succès.';
        } else {
            $this->errors[] = 'Erreur lors du refus du BAT.';
        }
    }
    
    public function processBulkAccept()
    {
        $ids = Tools::getValue('n3xtpdf_batBox');
        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = 'Aucun BAT sélectionné.';
            return;
        }
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        $success_count = 0;
        foreach ($ids as $id_bat) {
            if ($batManager->updateBatStatus((int)$id_bat, 'accepted', 'Acceptation en lot', $this->context->employee->id)) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $this->confirmations[] = $success_count . ' BAT(s) accepté(s) avec succès.';
        }
        
        if ($success_count < count($ids)) {
            $this->errors[] = (count($ids) - $success_count) . ' BAT(s) n\'ont pas pu être traités.';
        }
    }
    
    public function processBulkReject()
    {
        $ids = Tools::getValue('n3xtpdf_batBox');
        $notes = Tools::getValue('bulk_notes', '');
        
        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = 'Aucun BAT sélectionné.';
            return;
        }
        
        if (empty($notes)) {
            $this->errors[] = 'Les notes sont obligatoires pour un refus en lot.';
            return;
        }
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        $success_count = 0;
        foreach ($ids as $id_bat) {
            if ($batManager->updateBatStatus((int)$id_bat, 'rejected', $notes, $this->context->employee->id)) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $this->confirmations[] = $success_count . ' BAT(s) refusé(s) avec succès.';
        }
        
        if ($success_count < count($ids)) {
            $this->errors[] = (count($ids) - $success_count) . ' BAT(s) n\'ont pas pu être traités.';
        }
    }
    
    public function displayDownloadLink($token, $id_bat, $name = null)
    {
        $link = Context::getContext()->link->getModuleLink('n3xtpdf', 'downloads', ['token' => $token]);
        return '<a href="' . $link . '" target="_blank" class="btn btn-default btn-xs">
                    <i class="icon-download"></i> ' . ($name ?: 'Télécharger') . '
                </a>';
    }
    
    public function ajaxProcessQuickAccept()
    {
        $id_bat = (int)Tools::getValue('id_bat');
        $notes = Tools::getValue('notes', '');
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        if ($batManager->updateBatStatus($id_bat, 'accepted', $notes, $this->context->employee->id)) {
            die(json_encode(['success' => true, 'message' => 'BAT accepté avec succès.']));
        } else {
            die(json_encode(['success' => false, 'message' => 'Erreur lors de l\'acceptation.']));
        }
    }
    
    public function ajaxProcessQuickReject()
    {
        $id_bat = (int)Tools::getValue('id_bat');
        $notes = Tools::getValue('notes', '');
        
        if (empty($notes)) {
            die(json_encode(['success' => false, 'message' => 'Les notes sont obligatoires.']));
        }
        
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        if ($batManager->updateBatStatus($id_bat, 'rejected', $notes, $this->context->employee->id)) {
            die(json_encode(['success' => true, 'message' => 'BAT refusé avec succès.']));
        } else {
            die(json_encode(['success' => false, 'message' => 'Erreur lors du refus.']));
        }
    }
}