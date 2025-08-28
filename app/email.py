from flask import current_app, render_template
from flask_mail import Message
from app import mail, db
from app.models import BAT, ReminderLog
from datetime import datetime

def send_email(subject, recipient, text_body, html_body):
    """Send an email"""
    try:
        msg = Message(
            subject=subject,
            recipients=[recipient],
            body=text_body,
            html=html_body
        )
        mail.send(msg)
        return True
    except Exception as e:
        current_app.logger.error(f'Erreur envoi email: {str(e)}')
        return False

def send_bat_reminder(bat):
    """Send a reminder email for a pending BAT"""
    try:
        subject = f'Rappel: BAT en attente - Commande {bat.order_reference}'
        
        # Create secure URL for the BAT
        bat_url = bat.get_secure_url()
        
        # Count previous reminders
        reminder_count = bat.reminder_logs.count() + 1
        
        text_body = f"""
Bonjour,

Nous vous rappelons qu'un BAT (Bon À Tirer) est en attente de votre validation pour la commande {bat.order_reference}.

Détails du BAT:
- Commande: {bat.order_reference}
- Fichier: {bat.original_filename}
- Date de téléchargement: {bat.upload_date.strftime('%d/%m/%Y à %H:%M')}
- Statut: En attente de validation

Pour consulter et valider votre BAT, cliquez sur le lien sécurisé suivant:
{bat_url}

RAPPEL - Consignes techniques importantes:
- Mode colorimétrique CMJN obligatoire
- Calque "découpe" séparé si forme découpée
- Résolution minimale 300 DPI
- Fond perdu de 3mm minimum

Le délai de production + expédition commencera uniquement après votre validation du BAT.

Pour toute question, n'hésitez pas à nous contacter.

Cordialement,
L'équipe n3xtpdf

---
Ceci est un rappel automatique #{reminder_count}
        """
        
        html_body = f"""
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {{ font-family: Arial, sans-serif; line-height: 1.6; color: #333; }}
        .header {{ background-color: #f8f9fa; padding: 20px; text-align: center; }}
        .content {{ padding: 20px; }}
        .bat-details {{ background-color: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }}
        .guidelines {{ background-color: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ffc107; }}
        .button {{ 
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 20px 0; 
        }}
        .footer {{ background-color: #f8f9fa; padding: 15px; text-align: center; color: #6c757d; font-size: 12px; }}
    </style>
</head>
<body>
    <div class="header">
        <h1>Rappel: BAT en attente de validation</h1>
    </div>
    
    <div class="content">
        <p>Bonjour,</p>
        
        <p>Nous vous rappelons qu'un BAT (Bon À Tirer) est en attente de votre validation.</p>
        
        <div class="bat-details">
            <h3>Détails du BAT:</h3>
            <ul>
                <li><strong>Commande:</strong> {bat.order_reference}</li>
                <li><strong>Fichier:</strong> {bat.original_filename}</li>
                <li><strong>Date de téléchargement:</strong> {bat.upload_date.strftime('%d/%m/%Y à %H:%M')}</li>
                <li><strong>Statut:</strong> En attente de validation</li>
            </ul>
        </div>
        
        <p>Pour consulter et valider votre BAT, cliquez sur le bouton ci-dessous:</p>
        
        <a href="{bat_url}" class="button">Consulter le BAT</a>
        
        <div class="guidelines">
            <h3>⚠️ Consignes techniques importantes:</h3>
            <ul>
                <li>Mode colorimétrique <strong>CMJN obligatoire</strong></li>
                <li>Calque "découpe" séparé si forme découpée</li>
                <li>Résolution minimale 300 DPI</li>
                <li>Fond perdu de 3mm minimum</li>
            </ul>
        </div>
        
        <p><strong>Important:</strong> Le délai de production + expédition commencera uniquement après votre validation du BAT.</p>
        
        <p>Pour toute question, n'hésitez pas à nous contacter.</p>
        
        <p>Cordialement,<br>L'équipe n3xtpdf</p>
    </div>
    
    <div class="footer">
        Ceci est un rappel automatique #{reminder_count}
    </div>
</body>
</html>
        """
        
        # Send email
        success = send_email(subject, bat.client.email, text_body, html_body)
        
        if success:
            # Log the reminder
            reminder_log = ReminderLog(
                bat_id=bat.id,
                email_sent_to=bat.client.email,
                reminder_count=reminder_count
            )
            db.session.add(reminder_log)
            db.session.commit()
            
            current_app.logger.info(f'Rappel envoyé pour BAT {bat.id} à {bat.client.email}')
            return True
        else:
            current_app.logger.error(f'Échec envoi rappel pour BAT {bat.id}')
            return False
            
    except Exception as e:
        current_app.logger.error(f'Erreur lors de l\'envoi du rappel BAT {bat.id}: {str(e)}')
        return False

def send_bat_notification(bat, action):
    """Send notification when BAT status changes"""
    try:
        if action == 'uploaded':
            subject = f'BAT reçu - Commande {bat.order_reference}'
            message = 'Votre BAT a été reçu et est en cours de vérification.'
        elif action == 'accepted':
            subject = f'BAT accepté - Commande {bat.order_reference}'
            message = 'Votre BAT a été accepté. La production va commencer.'
        elif action == 'rejected':
            subject = f'BAT refusé - Commande {bat.order_reference}'
            message = 'Votre BAT a été refusé. Veuillez télécharger une nouvelle version.'
        else:
            return False
        
        text_body = f"""
Bonjour,

{message}

Commande: {bat.order_reference}
Fichier: {bat.original_filename}

Vous pouvez consulter le statut de votre BAT à l'adresse suivante:
{bat.get_secure_url()}

Cordialement,
L'équipe n3xtpdf
        """
        
        return send_email(subject, bat.client.email, text_body, text_body)
        
    except Exception as e:
        current_app.logger.error(f'Erreur notification BAT {bat.id}: {str(e)}')
        return False

def check_and_send_reminders():
    """Check for BATs that need reminders and send them"""
    try:
        reminder_interval = current_app.config['BAT_REMINDER_INTERVAL_DAYS']
        pending_bats = BAT.query.filter_by(status='pending').all()
        
        reminders_sent = 0
        for bat in pending_bats:
            if bat.needs_reminder(reminder_interval):
                if send_bat_reminder(bat):
                    reminders_sent += 1
        
        current_app.logger.info(f'Rappels envoyés: {reminders_sent}')
        return reminders_sent
        
    except Exception as e:
        current_app.logger.error(f'Erreur lors de la vérification des rappels: {str(e)}')
        return 0