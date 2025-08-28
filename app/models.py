from datetime import datetime, timedelta
from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
import secrets
import string
from app import db, login_manager

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    is_admin = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    bats = db.relationship('BAT', backref='client', lazy='dynamic')
    consultation_logs = db.relationship('ConsultationLog', backref='user', lazy='dynamic')
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)
    
    def __repr__(self):
        return f'<User {self.username}>'

class BAT(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    filename = db.Column(db.String(255), nullable=False)
    original_filename = db.Column(db.String(255), nullable=False)
    secure_token = db.Column(db.String(64), unique=True, nullable=False)
    order_reference = db.Column(db.String(100), nullable=False)
    status = db.Column(db.String(20), default='pending')  # pending, accepted, rejected
    upload_date = db.Column(db.DateTime, default=datetime.utcnow)
    response_date = db.Column(db.DateTime)
    client_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    
    # Technical validation results
    is_cmyk = db.Column(db.Boolean)
    has_cutting_layer = db.Column(db.Boolean)
    validation_notes = db.Column(db.Text)
    
    # Production tracking
    production_start_date = db.Column(db.DateTime)
    estimated_delivery_date = db.Column(db.DateTime)
    
    # Relationships
    consultation_logs = db.relationship('ConsultationLog', backref='bat', lazy='dynamic', cascade='all, delete-orphan')
    reminder_logs = db.relationship('ReminderLog', backref='bat', lazy='dynamic', cascade='all, delete-orphan')
    
    def __init__(self, **kwargs):
        super().__init__(**kwargs)
        if not self.secure_token:
            self.secure_token = self.generate_secure_token()
    
    @staticmethod
    def generate_secure_token():
        return ''.join(secrets.choice(string.ascii_letters + string.digits) for _ in range(64))
    
    def get_secure_url(self):
        from flask import url_for
        return url_for('client.view_bat', token=self.secure_token, _external=True)
    
    def mark_accepted(self):
        self.status = 'accepted'
        self.response_date = datetime.utcnow()
        self.production_start_date = datetime.utcnow()
        # Add 5 business days for production + shipping
        self.estimated_delivery_date = self.production_start_date + timedelta(days=7)
    
    def mark_rejected(self):
        self.status = 'rejected'
        self.response_date = datetime.utcnow()
    
    def needs_reminder(self, reminder_interval_days=2):
        if self.status != 'pending':
            return False
        
        last_reminder = self.reminder_logs.order_by(ReminderLog.sent_date.desc()).first()
        if last_reminder:
            return datetime.utcnow() > last_reminder.sent_date + timedelta(days=reminder_interval_days)
        else:
            return datetime.utcnow() > self.upload_date + timedelta(days=reminder_interval_days)
    
    def __repr__(self):
        return f'<BAT {self.order_reference}>'

class ConsultationLog(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    bat_id = db.Column(db.Integer, db.ForeignKey('bat.id'), nullable=False)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    consultation_date = db.Column(db.DateTime, default=datetime.utcnow)
    ip_address = db.Column(db.String(45))
    user_agent = db.Column(db.String(500))
    
    def __repr__(self):
        return f'<ConsultationLog BAT:{self.bat_id} User:{self.user_id}>'

class ReminderLog(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    bat_id = db.Column(db.Integer, db.ForeignKey('bat.id'), nullable=False)
    sent_date = db.Column(db.DateTime, default=datetime.utcnow)
    email_sent_to = db.Column(db.String(120), nullable=False)
    reminder_count = db.Column(db.Integer, default=1)
    
    def __repr__(self):
        return f'<ReminderLog BAT:{self.bat_id} Count:{self.reminder_count}>'