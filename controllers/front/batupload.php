<?php
/**
 * Contrôleur Front-Office pour l'upload des BAT PDF
 * 
 * @author n3xtpdf Team
 * @version 1.0.0
 */

class N3xtpdfBatuploadModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
    }

    /**
     * Initialisation du contrôleur
     */
    public function init()
    {
        parent::init();

        // Vérifier que le client est connecté
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication&back=' . urlencode($_SERVER['REQUEST_URI']));
        }
    }

    /**
     * Traitement des données POST
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitBatUpload')) {
            $this->processBatUpload();
        } elseif (Tools::isSubmit('validateBat')) {
            $this->processBatValidation();
        } elseif (Tools::isSubmit('rejectBat')) {
            $this->processBatRejection();
        }
    }

    /**
     * Traitement de l'upload de BAT
     */
    private function processBatUpload()
    {
        $errors = [];
        
        // Validation des champs
        $order_reference = Tools::getValue('order_reference');
        $notes = Tools::getValue('notes', '');

        if (empty($order_reference)) {
            $errors[] = 'La référence de commande est obligatoire.';
        }

        // Validation du fichier uploadé
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors de l\'upload du fichier.';
        } else {
            $file = $_FILES['pdf_file'];
            $max_size = (int)Configuration::get('N3XTPDF_MAX_FILE_SIZE', 50) * 1024 * 1024; // MB en bytes

            if ($file['size'] > $max_size) {
                $errors[] = 'Le fichier est trop volumineux. Taille maximale : ' . ($max_size / 1024 / 1024) . 'MB.';
            }

            $allowed_types = ['application/pdf'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = 'Seuls les fichiers PDF sont autorisés.';
            }
        }

        if (empty($errors)) {
            require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
            $batManager = new BatManager();

            // Générer un nom de fichier sécurisé
            $file = $_FILES['pdf_file'];
            $secure_token = $batManager->generateSecureToken();
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $secure_filename = $secure_token . '.' . $file_extension;
            $upload_path = _PS_MODULE_DIR_ . 'n3xtpdf/uploads/' . $secure_filename;

            // Déplacer le fichier uploadé
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Valider le fichier PDF
                $validation = $batManager->validatePdfFile($upload_path, $file['name']);

                // Créer l'entrée en base de données
                $bat_data = [
                    'id_customer' => $this->context->customer->id,
                    'id_order' => $this->getOrderIdByReference($order_reference),
                    'order_reference' => $order_reference,
                    'filename' => $secure_filename,
                    'original_filename' => $file['name'],
                    'secure_token' => $secure_token,
                    'file_size' => $file['size'],
                    'is_cmyk' => $validation['is_cmyk'] ? 1 : 0,
                    'has_cutting_layer' => $validation['has_cutting_layer'] ? 1 : 0,
                    'validation_notes' => implode("\n", $validation['validation_notes'])
                ];

                $id_bat = $batManager->createBat($bat_data);

                if ($id_bat) {
                    // Logger la consultation
                    $batManager->logConsultation($id_bat, $this->context->customer->id, 'upload');

                    // Envoyer email de notification (optionnel)
                    $this->sendUploadNotification($id_bat, $secure_token);

                    $this->context->smarty->assign([
                        'success' => true,
                        'bat_token' => $secure_token,
                        'validation_results' => $validation
                    ]);
                } else {
                    unlink($upload_path); // Supprimer le fichier en cas d'erreur
                    $errors[] = 'Erreur lors de l\'enregistrement en base de données.';
                }
            } else {
                $errors[] = 'Erreur lors de la sauvegarde du fichier.';
            }
        }

        if (!empty($errors)) {
            $this->context->smarty->assign('errors', $errors);
        }
    }

    /**
     * Traitement de la validation d'un BAT
     */
    private function processBatValidation()
    {
        $token = Tools::getValue('token');
        $notes = Tools::getValue('validation_notes', '');

        if (empty($token)) {
            $this->errors[] = 'Token manquant.';
            return;
        }

        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        $bat = $batManager->getBatByToken($token);

        if (!$bat || $bat['id_customer'] != $this->context->customer->id) {
            $this->errors[] = 'BAT non trouvé ou accès non autorisé.';
            return;
        }

        if ($bat['status'] !== 'pending') {
            $this->errors[] = 'Ce BAT a déjà été traité.';
            return;
        }

        if ($batManager->updateBatStatus($bat['id_bat'], 'accepted', $notes, null)) {
            $batManager->logConsultation($bat['id_bat'], $this->context->customer->id, 'accept');
            $this->context->smarty->assign('success_message', 'BAT accepté avec succès. La production va commencer.');
        } else {
            $this->errors[] = 'Erreur lors de la validation du BAT.';
        }
    }

    /**
     * Traitement du rejet d'un BAT
     */
    private function processBatRejection()
    {
        $token = Tools::getValue('token');
        $notes = Tools::getValue('rejection_notes', '');

        if (empty($token)) {
            $this->errors[] = 'Token manquant.';
            return;
        }

        if (empty($notes)) {
            $this->errors[] = 'Les notes de rejet sont obligatoires.';
            return;
        }

        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        $bat = $batManager->getBatByToken($token);

        if (!$bat || $bat['id_customer'] != $this->context->customer->id) {
            $this->errors[] = 'BAT non trouvé ou accès non autorisé.';
            return;
        }

        if ($bat['status'] !== 'pending') {
            $this->errors[] = 'Ce BAT a déjà été traité.';
            return;
        }

        if ($batManager->updateBatStatus($bat['id_bat'], 'rejected', $notes, null)) {
            $batManager->logConsultation($bat['id_bat'], $this->context->customer->id, 'reject');
            $this->context->smarty->assign('success_message', 'BAT refusé. Vous pouvez télécharger une nouvelle version.');
        } else {
            $this->errors[] = 'Erreur lors du rejet du BAT.';
        }
    }

    /**
     * Affichage principal
     */
    public function initContent()
    {
        parent::initContent();

        $action = Tools::getValue('action', 'upload');
        
        switch ($action) {
            case 'view':
                $this->displayBatView();
                break;
            case 'download':
                $this->downloadBat();
                break;
            case 'list':
                $this->displayBatList();
                break;
            default:
                $this->displayUploadForm();
                break;
        }
    }

    /**
     * Affichage du formulaire d'upload
     */
    private function displayUploadForm()
    {
        $guidelines = [
            'cmyk' => 'Mode colorimétrique CMJN obligatoire',
            'cutting' => 'Calque "découpe" séparé pour les formes découpées',
            'resolution' => 'Résolution minimale 300 DPI',
            'bleed' => 'Fond perdu de 3mm minimum'
        ];

        $this->context->smarty->assign([
            'guidelines' => $guidelines,
            'max_file_size' => Configuration::get('N3XTPDF_MAX_FILE_SIZE', 50),
            'customer' => $this->context->customer
        ]);

        $this->setTemplate('module:n3xtpdf/views/templates/front/batupload.tpl');
    }

    /**
     * Affichage d'un BAT spécifique
     */
    private function displayBatView()
    {
        $token = Tools::getValue('token');
        
        if (empty($token)) {
            Tools::redirect('index.php?fc=module&module=n3xtpdf&controller=batupload&action=list');
        }

        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        $bat = $batManager->getBatByToken($token);

        if (!$bat || $bat['id_customer'] != $this->context->customer->id) {
            $this->errors[] = 'BAT non trouvé ou accès non autorisé.';
            Tools::redirect('index.php?fc=module&module=n3xtpdf&controller=batupload&action=list');
        }

        // Logger la consultation
        $batManager->logConsultation($bat['id_bat'], $this->context->customer->id, 'view');

        $guidelines = [
            'cmyk' => 'Mode colorimétrique CMJN obligatoire',
            'cutting' => 'Calque "découpe" séparé pour les formes découpées',
            'resolution' => 'Résolution minimale 300 DPI',
            'bleed' => 'Fond perdu de 3mm minimum'
        ];

        $this->context->smarty->assign([
            'bat' => $bat,
            'guidelines' => $guidelines,
            'can_validate' => ($bat['status'] === 'pending')
        ]);

        $this->setTemplate('module:n3xtpdf/views/templates/front/bat_view.tpl');
    }

    /**
     * Téléchargement d'un BAT
     */
    private function downloadBat()
    {
        $token = Tools::getValue('token');
        
        if (empty($token)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        $bat = $batManager->getBatByToken($token);

        if (!$bat || $bat['id_customer'] != $this->context->customer->id) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $file_path = _PS_MODULE_DIR_ . 'n3xtpdf/uploads/' . $bat['filename'];
        
        if (!file_exists($file_path)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Logger le téléchargement
        $batManager->logConsultation($bat['id_bat'], $this->context->customer->id, 'download');

        // Forcer le téléchargement
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $bat['original_filename'] . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }

    /**
     * Affichage de la liste des BAT du client
     */
    private function displayBatList()
    {
        require_once(_PS_MODULE_DIR_ . 'n3xtpdf/classes/BatManager.php');
        $batManager = new BatManager();
        
        $page = (int)Tools::getValue('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $bats = $batManager->getBatsByCustomer($this->context->customer->id, $limit, $offset);

        $this->context->smarty->assign([
            'bats' => $bats,
            'customer' => $this->context->customer
        ]);

        $this->setTemplate('module:n3xtpdf/views/templates/front/bat_list.tpl');
    }

    /**
     * Récupérer l'ID de commande par référence
     */
    private function getOrderIdByReference($reference)
    {
        $sql = 'SELECT id_order FROM `' . _DB_PREFIX_ . 'orders` 
                WHERE reference = "' . pSQL($reference) . '" 
                AND id_customer = ' . (int)$this->context->customer->id;
        
        $result = Db::getInstance()->getRow($sql);
        return $result ? $result['id_order'] : null;
    }

    /**
     * Envoyer une notification d'upload
     */
    private function sendUploadNotification($id_bat, $token)
    {
        // Logique d'envoi d'email
        // À implémenter selon les besoins de notification
        return true;
    }

    /**
     * Définir le titre de la page
     */
    public function getPageTitle()
    {
        return 'Gestion des BAT PDF - ' . parent::getPageTitle();
    }
}