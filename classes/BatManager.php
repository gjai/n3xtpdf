<?php
/**
 * BatManager - Classe utilitaire pour la gestion des BAT en base de données
 * 
 * @author n3xtpdf Team
 * @version 1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BatManager
{
    /**
     * Créer un nouveau BAT
     */
    public function createBat($data)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'n3xtpdf_bat` 
                (id_customer, id_order, order_reference, filename, original_filename, 
                 secure_token, file_size, is_cmyk, has_cutting_layer, validation_notes, upload_date)
                VALUES (' . (int)$data['id_customer'] . ', ' . 
                (isset($data['id_order']) ? (int)$data['id_order'] : 'NULL') . ', 
                "' . pSQL($data['order_reference']) . '", 
                "' . pSQL($data['filename']) . '", 
                "' . pSQL($data['original_filename']) . '", 
                "' . pSQL($data['secure_token']) . '", 
                ' . (int)$data['file_size'] . ', 
                ' . (int)$data['is_cmyk'] . ', 
                ' . (int)$data['has_cutting_layer'] . ', 
                "' . pSQL($data['validation_notes']) . '", 
                NOW())';

        return Db::getInstance()->execute($sql) ? Db::getInstance()->Insert_ID() : false;
    }

    /**
     * Récupérer un BAT par son token sécurisé
     */
    public function getBatByToken($token)
    {
        $sql = 'SELECT b.*, c.firstname, c.lastname, c.email, o.reference as order_ref
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` b
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON b.id_customer = c.id_customer
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON b.id_order = o.id_order
                WHERE b.secure_token = "' . pSQL($token) . '"';

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Récupérer les BAT d'un client
     */
    public function getBatsByCustomer($id_customer, $limit = 10, $offset = 0)
    {
        $sql = 'SELECT b.*, o.reference as order_ref
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` b
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON b.id_order = o.id_order
                WHERE b.id_customer = ' . (int)$id_customer . '
                ORDER BY b.upload_date DESC
                LIMIT ' . (int)$offset . ', ' . (int)$limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Récupérer les BAT d'une commande
     */
    public function getBatsByOrder($id_order, $order_reference = null)
    {
        $where_clause = 'b.id_order = ' . (int)$id_order;
        if ($order_reference) {
            $where_clause .= ' OR b.order_reference = "' . pSQL($order_reference) . '"';
        }

        $sql = 'SELECT b.*, c.firstname, c.lastname, c.email
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` b
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON b.id_customer = c.id_customer
                WHERE ' . $where_clause . '
                ORDER BY b.upload_date DESC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Mettre à jour le statut d'un BAT
     */
    public function updateBatStatus($id_bat, $status, $notes = '', $employee_id = null)
    {
        $valid_statuses = ['pending', 'accepted', 'rejected'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'n3xtpdf_bat` 
                SET status = "' . pSQL($status) . '", 
                    validation_notes = "' . pSQL($notes) . '", 
                    response_date = NOW(),
                    updated_by = ' . ($employee_id ? (int)$employee_id : 'NULL');

        if ($status === 'accepted') {
            $sql .= ', production_start_date = NOW()';
        }

        $sql .= ' WHERE id_bat = ' . (int)$id_bat;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Récupérer tous les BAT avec pagination
     */
    public function getAllBats($status = null, $limit = 20, $offset = 0)
    {
        $where_clause = '1=1';
        if ($status && in_array($status, ['pending', 'accepted', 'rejected'])) {
            $where_clause = 'b.status = "' . pSQL($status) . '"';
        }

        $sql = 'SELECT b.*, c.firstname, c.lastname, c.email, o.reference as order_ref
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` b
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON b.id_customer = c.id_customer
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON b.id_order = o.id_order
                WHERE ' . $where_clause . '
                ORDER BY b.upload_date DESC
                LIMIT ' . (int)$offset . ', ' . (int)$limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Compter le nombre de BAT
     */
    public function countBats($status = null)
    {
        $where_clause = '1=1';
        if ($status && in_array($status, ['pending', 'accepted', 'rejected'])) {
            $where_clause = 'status = "' . pSQL($status) . '"';
        }

        $sql = 'SELECT COUNT(*) as total 
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` 
                WHERE ' . $where_clause;

        $result = Db::getInstance()->getRow($sql);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Logger une consultation de BAT
     */
    public function logConsultation($id_bat, $id_customer, $action = 'view')
    {
        $ip_address = Tools::getRemoteAddr();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'n3xtpdf_consultation` 
                (id_bat, id_customer, consultation_date, ip_address, user_agent, action)
                VALUES (' . (int)$id_bat . ', ' . (int)$id_customer . ', NOW(), 
                "' . pSQL($ip_address) . '", "' . pSQL($user_agent) . '", "' . pSQL($action) . '")';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Récupérer les consultations d'un BAT
     */
    public function getConsultations($id_bat = null, $limit = 50, $offset = 0)
    {
        $where_clause = '1=1';
        if ($id_bat) {
            $where_clause = 'cons.id_bat = ' . (int)$id_bat;
        }

        $sql = 'SELECT cons.*, b.filename, b.order_reference, c.firstname, c.lastname, c.email
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_consultation` cons
                LEFT JOIN `' . _DB_PREFIX_ . 'n3xtpdf_bat` b ON cons.id_bat = b.id_bat
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON cons.id_customer = c.id_customer
                WHERE ' . $where_clause . '
                ORDER BY cons.consultation_date DESC
                LIMIT ' . (int)$offset . ', ' . (int)$limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Générer un token sécurisé unique
     */
    public function generateSecureToken()
    {
        do {
            $token = bin2hex(random_bytes(32)); // 64 caractères
            $existing = $this->getBatByToken($token);
        } while ($existing);

        return $token;
    }

    /**
     * Valider un fichier PDF uploadé
     */
    public function validatePdfFile($file_path, $original_filename)
    {
        $validation_results = [
            'is_valid_pdf' => false,
            'is_cmyk' => false,
            'has_cutting_layer' => false,
            'validation_notes' => []
        ];

        // Vérifier que le fichier existe
        if (!file_exists($file_path)) {
            $validation_results['validation_notes'][] = 'Fichier non trouvé.';
            return $validation_results;
        }

        // Vérifier l'extension
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            $validation_results['validation_notes'][] = 'Seuls les fichiers PDF sont acceptés.';
            return $validation_results;
        }

        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        if ($mime_type !== 'application/pdf') {
            $validation_results['validation_notes'][] = 'Le fichier n\'est pas un PDF valide.';
            return $validation_results;
        }

        $validation_results['is_valid_pdf'] = true;
        $validation_results['validation_notes'][] = 'Fichier PDF valide détecté.';

        // Analyse basique du contenu PDF (simulation)
        // Dans un environnement de production, utilisez des bibliothèques spécialisées
        $file_content = file_get_contents($file_path);
        
        // Recherche de traces CMJN
        if (strpos($file_content, '/DeviceCMYK') !== false || 
            strpos($file_content, 'CMYK') !== false ||
            strpos($file_content, '/Separation') !== false) {
            $validation_results['is_cmyk'] = true;
            $validation_results['validation_notes'][] = 'Mode colorimétrique CMJN détecté.';
        } else {
            $validation_results['validation_notes'][] = 'Mode colorimétrique CMJN non détecté. Vérifiez votre fichier.';
        }

        // Recherche de calque de découpe
        if (strpos($file_content, 'découpe') !== false || 
            strpos($file_content, 'cutting') !== false ||
            strpos($file_content, '/OCG') !== false ||
            strpos($file_content, '/Layer') !== false) {
            $validation_results['has_cutting_layer'] = true;
            $validation_results['validation_notes'][] = 'Calque de découpe détecté.';
        } else {
            $validation_results['validation_notes'][] = 'Calque de découpe non détecté. Ajoutez un calque "découpe" si nécessaire.';
        }

        return $validation_results;
    }

    /**
     * Récupérer les BAT nécessitant des rappels
     */
    public function getBatsForReminders($days_threshold = 2)
    {
        $sql = 'SELECT b.*, c.firstname, c.lastname, c.email
                FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` b
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON b.id_customer = c.id_customer
                WHERE b.status = "pending" 
                AND DATEDIFF(NOW(), b.upload_date) >= ' . (int)$days_threshold . '
                AND b.id_bat NOT IN (
                    SELECT id_bat FROM `' . _DB_PREFIX_ . 'n3xtpdf_reminder` 
                    WHERE DATE(sent_date) = CURDATE()
                )
                ORDER BY b.upload_date ASC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Enregistrer l'envoi d'un rappel
     */
    public function logReminder($id_bat, $type = 'automatic')
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'n3xtpdf_reminder` 
                (id_bat, sent_date, reminder_type, email_sent)
                VALUES (' . (int)$id_bat . ', NOW(), "' . pSQL($type) . '", 1)';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Nettoyer les anciens fichiers et données
     */
    public function cleanupOldData($days_to_keep = 365)
    {
        // Supprimer les anciens BAT et leurs consultations
        $sql_consultations = 'DELETE FROM `' . _DB_PREFIX_ . 'n3xtpdf_consultation` 
                             WHERE id_bat IN (
                                 SELECT id_bat FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` 
                                 WHERE DATEDIFF(NOW(), upload_date) > ' . (int)$days_to_keep . '
                             )';
        
        $sql_reminders = 'DELETE FROM `' . _DB_PREFIX_ . 'n3xtpdf_reminder` 
                         WHERE id_bat IN (
                             SELECT id_bat FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` 
                             WHERE DATEDIFF(NOW(), upload_date) > ' . (int)$days_to_keep . '
                         )';

        $sql_bats = 'DELETE FROM `' . _DB_PREFIX_ . 'n3xtpdf_bat` 
                     WHERE DATEDIFF(NOW(), upload_date) > ' . (int)$days_to_keep;

        Db::getInstance()->execute($sql_consultations);
        Db::getInstance()->execute($sql_reminders);
        return Db::getInstance()->execute($sql_bats);
    }
}