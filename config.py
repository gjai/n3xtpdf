import os
from datetime import timedelta

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'dev-secret-key-change-in-production'
    SQLALCHEMY_DATABASE_URI = os.environ.get('DATABASE_URL') or 'sqlite:///n3xtpdf.db'
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    
    # File upload settings
    MAX_CONTENT_LENGTH = 50 * 1024 * 1024  # 50MB max file size
    UPLOAD_FOLDER = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'secure_files')
    ALLOWED_EXTENSIONS = {'pdf'}
    
    # Security settings
    PERMANENT_SESSION_LIFETIME = timedelta(hours=24)
    
    # Email settings
    MAIL_SERVER = os.environ.get('MAIL_SERVER') or 'localhost'
    MAIL_PORT = int(os.environ.get('MAIL_PORT') or 587)
    MAIL_USE_TLS = os.environ.get('MAIL_USE_TLS', 'true').lower() in ['true', 'on', '1']
    MAIL_USERNAME = os.environ.get('MAIL_USERNAME')
    MAIL_PASSWORD = os.environ.get('MAIL_PASSWORD')
    MAIL_DEFAULT_SENDER = os.environ.get('MAIL_DEFAULT_SENDER') or 'noreply@n3xtpdf.local'
    
    # BAT reminder settings
    BAT_REMINDER_INTERVAL_DAYS = int(os.environ.get('BAT_REMINDER_INTERVAL_DAYS') or 2)
    
    # Technical guidelines
    TECHNICAL_GUIDELINES = {
        'color_mode': 'CMJN obligatoire pour tous les fichiers',
        'cutting_layer': 'Calque "découpe" séparé obligatoire pour les formes découpées',
        'resolution': 'Résolution minimale 300 DPI',
        'bleed': 'Fond perdu de 3mm minimum'
    }