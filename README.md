# n3xtpdf
Système de gestion des BAT (Bon À Tirer) - Upload PDF Client

## Fonctionnalités

- **Stockage sécurisé** : Fichiers BAT stockés dans un répertoire sécurisé avec accès uniquement par liens sécurisés
- **Validation automatique** : Vérification du mode colorimétrique CMJN et de la présence du calque "découpe"
- **Consultation loggée** : Toutes les consultations sont enregistrées avec date, client et informations de session
- **Rappels automatiques** : Envoi d'emails de rappel tous les 2 jours (paramétrable) pour les BAT en attente
- **Interface admin** : Tableau de bord complet pour la gestion des BAT, consultations et actions groupées
- **Espace client** : Interface dédiée pour consulter, accepter ou refuser les BAT
- **Consignes techniques** : Affichage des exigences techniques à toutes les étapes
- **Suivi production** : Le délai de production commence à l'acceptation du BAT

## Installation

1. Cloner le repository :
```bash
git clone https://github.com/gjai/n3xtpdf.git
cd n3xtpdf
```

2. Installer les dépendances :
```bash
pip install -r requirements.txt
```

3. Configurer les variables d'environnement (optionnel) :
```bash
export SECRET_KEY="your-secret-key"
export MAIL_SERVER="your-smtp-server"
export MAIL_USERNAME="your-email"
export MAIL_PASSWORD="your-password"
```

4. Initialiser la base de données :
```bash
export FLASK_APP=n3xtpdf.py
flask init-db
```

5. Créer un utilisateur administrateur :
```bash
flask create-admin
```

6. Lancer l'application :
```bash
python n3xtpdf.py
```

## Utilisation

### Interface Client
- Connexion/Inscription sur `/auth/login` ou `/auth/register`
- Téléchargement de BAT via `/client/upload`
- Consultation des BAT dans l'espace client `/client/dashboard`
- Validation des BAT via liens sécurisés

### Interface Admin
- Accès admin via `/admin/dashboard`
- Gestion des BAT via `/admin/bats`
- Visualisation des consultations via `/admin/consultations`
- Paramètres via `/admin/settings`

### Commandes CLI
- `flask init-db` : Initialiser la base de données
- `flask create-admin` : Créer un utilisateur administrateur
- `flask send-reminders` : Envoyer les rappels manuellement

## Configuration

Les consignes techniques sont configurables dans `config.py` :
- Mode colorimétrique CMJN obligatoire
- Calque "découpe" séparé pour les formes découpées
- Résolution minimale 300 DPI
- Fond perdu de 3mm minimum

## Sécurité

- Authentification utilisateur avec sessions sécurisées
- Accès aux fichiers uniquement via tokens sécurisés de 64 caractères
- Logging complet de toutes les consultations
- Validation des types de fichiers (PDF uniquement)
- Protection CSRF sur tous les formulaires

## Architecture

```
app/
├── auth/           # Authentification
├── client/         # Interface client
├── admin/          # Interface administrateur
├── main/           # Pages principales
├── templates/      # Templates HTML
├── models.py       # Modèles de base de données
├── utils.py        # Utilitaires (validation PDF)
└── email.py        # Système d'emails
```
