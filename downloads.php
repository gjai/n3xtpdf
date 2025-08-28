<?php
/**
 * Script de téléchargement sécurisé pour les BAT PDF
 * 
 * @author n3xtpdf Team
 * @version 1.0.0
 */

// Chargement du contexte PrestaShop
require_once dirname(__FILE__) . '/../../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../../init.php';

// Vérification du token
$token = Tools::getValue('token');
if (empty($token)) {
    header('HTTP/1.1 404 Not Found');
    exit('Token manquant');
}

// Chargement du gestionnaire BAT
require_once dirname(__FILE__) . '/classes/BatManager.php';
$batManager = new BatManager();
$bat = $batManager->getBatByToken($token);

if (!$bat) {
    header('HTTP/1.1 404 Not Found');
    exit('BAT non trouvé');
}

// Vérification des droits d'accès
$context = Context::getContext();

// Si l'utilisateur est connecté en tant que client
if ($context->customer && $context->customer->isLogged()) {
    if ($bat['id_customer'] != $context->customer->id) {
        header('HTTP/1.1 403 Forbidden');
        exit('Accès non autorisé');
    }
    $user_id = $context->customer->id;
    $user_type = 'customer';
}
// Si l'utilisateur est connecté en tant qu'employé (admin)
elseif ($context->employee && $context->employee->isLoggedBack()) {
    $user_id = $context->employee->id;
    $user_type = 'employee';
}
// Aucune authentification valide
else {
    header('HTTP/1.1 401 Unauthorized');
    exit('Authentification requise');
}

// Chemin du fichier
$file_path = dirname(__FILE__) . '/uploads/' . $bat['filename'];

if (!file_exists($file_path)) {
    header('HTTP/1.1 404 Not Found');
    exit('Fichier non trouvé');
}

// Logger le téléchargement
if ($user_type === 'customer') {
    $batManager->logConsultation($bat['id_bat'], $user_id, 'download');
}

// Définir les en-têtes pour le téléchargement
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $bat['original_filename'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, must-revalidate');
header('Pragma: private');
header('Expires: 0');

// Lire et envoyer le fichier
readfile($file_path);
exit;