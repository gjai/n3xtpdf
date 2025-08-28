# n3xtpdf - Module PrestaShop 9.0
Module de gestion des BAT (Bon À Tirer) PDF pour commandes personnalisées

## Fonctionnalités

- **Stockage sécurisé** : Fichiers BAT stockés dans un répertoire sécurisé avec accès uniquement par liens sécurisés
- **Validation automatique** : Vérification du mode colorimétrique CMJN et de la présence du calque "découpe"
- **Consultation loggée** : Toutes les consultations sont enregistrées avec date, client et informations de session
- **Rappels automatiques** : Système de rappels configurables pour les BAT en attente
- **Interface admin** : Intégration complète dans l'administration des commandes PrestaShop
- **Espace client** : Interface dédiée dans le compte client pour consulter, accepter ou refuser les BAT
- **Consignes techniques** : Affichage des exigences techniques à toutes les étapes
- **Suivi production** : Le délai de production commence à l'acceptation du BAT
- **Traçabilité complète** : Audit trail complet de toutes les actions sur les BAT

## Installation

### Prérequis
- PrestaShop 9.0 ou supérieur
- PHP 7.4 ou supérieur
- Extension PHP `fileinfo` activée
- Droits d'écriture sur le répertoire modules

### Installation du module

1. **Télécharger le module** :
```bash
git clone https://github.com/gjai/n3xtpdf.git
cd n3xtpdf
```

2. **Copier dans PrestaShop** :
```bash
# Copier le dossier module dans PrestaShop
cp -r . /path/to/prestashop/modules/n3xtpdf/
```

3. **Installer via l'administration** :
- Aller dans `Modules > Gestionnaire de modules`
- Rechercher "n3xtpdf"
- Cliquer sur "Installer"
- Configurer les paramètres selon vos besoins

### Configuration post-installation

1. **Paramètres du module** :
- Mode CMJN obligatoire : Activé/Désactivé
- Calque découpe obligatoire : Activé/Désactivé
- Taille maximale fichier : 50MB (par défaut)
- Fréquence rappels : 2 jours (par défaut)

2. **Permissions fichiers** :
```bash
# Assurer les droits d'écriture sur le dossier uploads
chmod 755 modules/n3xtpdf/uploads/
```

## Utilisation

### Interface Client

#### Téléchargement de BAT
- Accès via le compte client > "Télécharger un BAT PDF"
- URL directe : `/module/n3xtpdf/batupload`
- Remplir la référence de commande
- Télécharger le fichier PDF
- Ajouter des notes optionnelles

#### Consultation des BAT
- Liste des BAT : `/module/n3xtpdf/batupload?action=list`
- Consultation détaillée via token sécurisé
- Téléchargement du PDF original
- Validation/Refus directement depuis l'interface

### Interface Administrateur

#### Gestion dans les commandes
- Affichage automatique des BAT dans la fiche commande
- Actions rapides : Accepter, Refuser, Télécharger
- Visualisation des validations techniques (CMJN, découpe)

#### Administration centralisée
- Menu `Commandes > Gestion BAT PDF`
- Liste complète de tous les BAT
- Actions en lot
- Statistiques et consultations

### Workflow type

1. **Client télécharge le BAT** avec la référence de commande
2. **Validation automatique** des critères techniques
3. **Notification** à l'équipe de production
4. **Examen par l'admin** dans la fiche commande
5. **Acceptation/Refus** avec notes
6. **Notification client** du statut
7. **Démarrage production** si accepté

## Configuration technique

### Consignes par défaut
- Mode colorimétrique CMJN obligatoire
- Calque "découpe" séparé pour les formes découpées  
- Résolution minimale 300 DPI
- Fond perdu de 3mm minimum
- Format PDF uniquement
- Taille maximale 50MB

### Sécurité

- **Tokens sécurisés** : 64 caractères pour l'accès aux fichiers
- **Validation stricte** : Types MIME et extensions vérifiés
- **Logging complet** : Toutes les consultations tracées
- **Protection d'accès** : Fichiers non accessibles directement
- **Authentification** : Accès client et administrateur séparés

## Architecture du module

```
n3xtpdf/
├── n3xtpdf.php                          # Fichier principal du module
├── config.xml                           # Configuration module
├── classes/
│   └── BatManager.php                   # Gestionnaire BAT
├── controllers/
│   ├── front/
│   │   └── batupload.php               # Contrôleur front-office
│   └── admin/
│       └── AdminN3xtpdfController.php  # Contrôleur admin
├── views/
│   ├── templates/
│   │   ├── front/
│   │   │   ├── batupload.tpl           # Formulaire upload
│   │   │   ├── bat_view.tpl            # Consultation BAT
│   │   │   ├── bat_list.tpl            # Liste BAT client
│   │   │   └── customer_account.tpl     # Liens compte client
│   │   └── admin/
│   │       └── admin_order.tpl         # Intégration commande
│   ├── css/
│   │   └── n3xtpdf.css                 # Styles CSS
│   └── js/
│       └── n3xtpdf.js                  # JavaScript
├── uploads/                             # Stockage sécurisé fichiers
└── downloads.php                        # Script téléchargement sécurisé
```

## Hooks PrestaShop utilisés

- `displayAdminOrder` : Affichage dans les commandes admin
- `displayCustomerAccount` : Liens dans le compte client  
- `displayMyAccountBlock` : Bloc mon compte
- `displayHeader` : Chargement CSS/JS
- `actionOrderStatusUpdate` : Notifications changement statut

## Base de données

### Tables créées
- `ps_n3xtpdf_bat` : Stockage des BAT
- `ps_n3xtpdf_consultation` : Logs de consultation
- `ps_n3xtpdf_reminder` : Historique des rappels

### Champs principaux
- Informations fichier (nom, taille, token sécurisé)
- Validations techniques (CMJN, découpe)
- Statuts et dates (upload, réponse, production)
- Traçabilité (qui, quand, depuis où)

## Évolutions prévues

- **Notifications email** automatiques
- **Templates d'emails** personnalisables
- **API REST** pour intégrations externes
- **Rapports avancés** et statistiques
- **Validation PDF avancée** avec bibliothèques spécialisées
- **Workflow multi-étapes** de validation
- **Intégration ERP** pour suivi production
