<?php
/**
 * n3xtpdf Module for PrestaShop 9.0
 * Gestion des BAT PDF pour commandes personnalisées
 * 
 * @author n3xtpdf Team
 * @version 1.0.0
 * @copyright 2024
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class N3xtpdf extends Module
{
    public function __construct()
    {
        $this->name = 'n3xtpdf';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'n3xtpdf Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '9.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('n3xtpdf - Gestion des BAT PDF');
        $this->description = $this->l('Module de gestion des BAT (Bon À Tirer) PDF pour commandes personnalisées avec validation automatique et suivi complet.');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ? Toutes les données BAT seront perdues.');
    }

    /**
     * Installation du module
     */
    public function install()
    {
        return parent::install() &&
            $this->installDb() &&
            $this->installTabs() &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayMyAccountBlock') &&
            $this->registerHook('displayHeader') &&
            $this->createUploadDirectory();
    }

    /**
     * Désinstallation du module
     */
    public function uninstall()
    {
        return $this->uninstallDb() &&
            $this->uninstallTabs() &&
            parent::uninstall();
    }

    /**
     * Création des tables de base de données
     */
    private function installDb()
    {
        $sql = [];

        // Table principale des BAT
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'n3xtpdf_bat` (
            `id_bat` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `id_order` int(11) DEFAULT NULL,
            `order_reference` varchar(255) NOT NULL,
            `filename` varchar(255) NOT NULL,
            `original_filename` varchar(255) NOT NULL,
            `secure_token` varchar(64) NOT NULL,
            `file_size` bigint(20) NOT NULL,
            `is_cmyk` tinyint(1) DEFAULT 0,
            `has_cutting_layer` tinyint(1) DEFAULT 0,
            `status` enum("pending","accepted","rejected") DEFAULT "pending",
            `validation_notes` text,
            `upload_date` datetime NOT NULL,
            `response_date` datetime DEFAULT NULL,
            `production_start_date` datetime DEFAULT NULL,
            `estimated_delivery_date` datetime DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            PRIMARY KEY (`id_bat`),
            KEY `idx_customer` (`id_customer`),
            KEY `idx_order` (`id_order`),
            KEY `idx_token` (`secure_token`),
            KEY `idx_status` (`status`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        // Table des consultations
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'n3xtpdf_consultation` (
            `id_consultation` int(11) NOT NULL AUTO_INCREMENT,
            `id_bat` int(11) NOT NULL,
            `id_customer` int(11) NOT NULL,
            `consultation_date` datetime NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_agent` text,
            `action` varchar(50) DEFAULT "view",
            PRIMARY KEY (`id_consultation`),
            KEY `idx_bat` (`id_bat`),
            KEY `idx_customer` (`id_customer`),
            KEY `idx_date` (`consultation_date`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        // Table des rappels
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'n3xtpdf_reminder` (
            `id_reminder` int(11) NOT NULL AUTO_INCREMENT,
            `id_bat` int(11) NOT NULL,
            `sent_date` datetime NOT NULL,
            `reminder_type` varchar(50) DEFAULT "automatic",
            `email_sent` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`id_reminder`),
            KEY `idx_bat` (`id_bat`),
            KEY `idx_date` (`sent_date`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Suppression des tables de base de données
     */
    private function uninstallDb()
    {
        $sql = [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'n3xtpdf_reminder`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'n3xtpdf_consultation`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'n3xtpdf_bat`'
        ];

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Installation des onglets d'administration
     */
    private function installTabs()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminN3xtpdf';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestion BAT PDF';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * Suppression des onglets d'administration
     */
    private function uninstallTabs()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminN3xtpdf');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Création du répertoire de stockage des fichiers
     */
    private function createUploadDirectory()
    {
        $upload_dir = _PS_MODULE_DIR_ . $this->name . '/uploads/';
        
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return false;
            }
        }

        // Création du fichier .htaccess pour sécuriser le répertoire
        $htaccess_content = "Order Deny,Allow\nDeny from all\n";
        if (!file_put_contents($upload_dir . '.htaccess', $htaccess_content)) {
            return false;
        }

        // Création du fichier index.php pour éviter le listing
        $index_content = "<?php\n// Silence is golden\n";
        if (!file_put_contents($upload_dir . 'index.php', $index_content)) {
            return false;
        }

        return true;
    }

    /**
     * Hook d'affichage dans l'en-tête pour charger les CSS/JS
     */
    public function hookDisplayHeader()
    {
        if (Tools::getValue('controller') == 'batupload' || 
            strpos($_SERVER['REQUEST_URI'], 'batupload') !== false) {
            $this->context->controller->addCSS($this->_path . 'views/css/n3xtpdf.css');
            $this->context->controller->addJS($this->_path . 'views/js/n3xtpdf.js');
        }
    }

    /**
     * Hook d'affichage dans l'administration des commandes
     */
    public function hookDisplayAdminOrder($params)
    {
        if (!isset($params['id_order'])) {
            return '';
        }

        $id_order = (int)$params['id_order'];
        $order = new Order($id_order);
        
        // Chargement des BAT liés à cette commande
        require_once(_PS_MODULE_DIR_ . $this->name . '/classes/BatManager.php');
        $batManager = new BatManager();
        $bats = $batManager->getBatsByOrder($id_order, $order->reference);

        $this->context->smarty->assign([
            'bats' => $bats,
            'order' => $order,
            'module_dir' => $this->_path
        ]);

        return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
    }

    /**
     * Hook d'affichage dans le compte client
     */
    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'views/templates/front/customer_account.tpl');
    }

    /**
     * Hook d'affichage dans le bloc mon compte
     */
    public function hookDisplayMyAccountBlock()
    {
        return $this->hookDisplayCustomerAccount();
    }

    /**
     * Hook lors du changement de statut de commande
     */
    public function hookActionOrderStatusUpdate($params)
    {
        // Logique pour traiter les changements de statut
        // Par exemple, envoyer des notifications BAT selon le statut
        if (isset($params['newOrderStatus']) && isset($params['id_order'])) {
            $id_order = (int)$params['id_order'];
            $new_status = $params['newOrderStatus'];
            
            // Si la commande est validée, on peut envoyer des notifications BAT
            if ($new_status->id == Configuration::get('PS_OS_PAYMENT')) {
                $this->sendBatNotification($id_order);
            }
        }
    }

    /**
     * Envoi de notification BAT pour une commande
     */
    private function sendBatNotification($id_order)
    {
        // Logique d'envoi de notification BAT
        // À implémenter selon les besoins
    }

    /**
     * Configuration du module
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitN3xtpdfConfig')) {
            $this->_postProcess();
            $output .= $this->displayConfirmation($this->l('Configuration mise à jour.'));
        }

        return $output . $this->displayForm();
    }

    /**
     * Traitement de la configuration
     */
    private function _postProcess()
    {
        Configuration::updateValue('N3XTPDF_CMYK_REQUIRED', (int)Tools::getValue('N3XTPDF_CMYK_REQUIRED'));
        Configuration::updateValue('N3XTPDF_CUTTING_LAYER_REQUIRED', (int)Tools::getValue('N3XTPDF_CUTTING_LAYER_REQUIRED'));
        Configuration::updateValue('N3XTPDF_MAX_FILE_SIZE', (int)Tools::getValue('N3XTPDF_MAX_FILE_SIZE'));
        Configuration::updateValue('N3XTPDF_REMINDER_DAYS', (int)Tools::getValue('N3XTPDF_REMINDER_DAYS'));
    }

    /**
     * Formulaire de configuration
     */
    private function displayForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration n3xtpdf'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Mode CMJN obligatoire'),
                        'name' => 'N3XTPDF_CMYK_REQUIRED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activé')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé')
                            ]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Calque découpe obligatoire'),
                        'name' => 'N3XTPDF_CUTTING_LAYER_REQUIRED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activé')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé')
                            ]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Taille maximale fichier (MB)'),
                        'name' => 'N3XTPDF_MAX_FILE_SIZE',
                        'suffix' => 'MB'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Fréquence rappels (jours)'),
                        'name' => 'N3XTPDF_REMINDER_DAYS',
                        'suffix' => 'jours'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submitN3xtpdfConfig';

        $helper->fields_value['N3XTPDF_CMYK_REQUIRED'] = Configuration::get('N3XTPDF_CMYK_REQUIRED', 1);
        $helper->fields_value['N3XTPDF_CUTTING_LAYER_REQUIRED'] = Configuration::get('N3XTPDF_CUTTING_LAYER_REQUIRED', 0);
        $helper->fields_value['N3XTPDF_MAX_FILE_SIZE'] = Configuration::get('N3XTPDF_MAX_FILE_SIZE', 50);
        $helper->fields_value['N3XTPDF_REMINDER_DAYS'] = Configuration::get('N3XTPDF_REMINDER_DAYS', 2);

        return $helper->generateForm([$fields_form]);
    }
}