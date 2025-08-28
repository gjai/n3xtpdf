#!/usr/bin/env python3
import os
from flask import Flask
from app import create_app, db
from app.models import User, BAT, ConsultationLog, ReminderLog

app = create_app()

@app.shell_context_processor
def make_shell_context():
    return {
        'db': db, 
        'User': User, 
        'BAT': BAT, 
        'ConsultationLog': ConsultationLog,
        'ReminderLog': ReminderLog
    }

@app.cli.command()
def init_db():
    """Initialize the database."""
    db.create_all()
    print('Database initialized.')

@app.cli.command()
def create_admin():
    """Create an admin user."""
    username = input('Admin username: ')
    email = input('Admin email: ')
    password = input('Admin password: ')
    
    existing_user = User.query.filter(
        (User.username == username) | (User.email == email)
    ).first()
    
    if existing_user:
        print('User with this username or email already exists.')
        return
    
    admin = User(username=username, email=email, is_admin=True)
    admin.set_password(password)
    
    db.session.add(admin)
    db.session.commit()
    
    print(f'Admin user {username} created successfully.')

@app.cli.command()
def send_reminders():
    """Send BAT reminders to clients."""
    from app.email import check_and_send_reminders
    
    with app.app_context():
        count = check_and_send_reminders()
        print(f'Sent {count} reminders.')

@app.cli.command()
def create_test_data():
    """Create test data for development."""
    # Create a test client user
    client = User.query.filter_by(username='testclient').first()
    if not client:
        client = User(username='testclient', email='client@test.com')
        client.set_password('testpass123')
        db.session.add(client)
        db.session.commit()
        print('Test client user created.')
    
    # Create a test BAT
    test_bat = BAT.query.filter_by(order_reference='TEST-001').first()
    if not test_bat:
        test_bat = BAT(
            filename='test_document.pdf',
            original_filename='Mon_Document_Test.pdf',
            order_reference='TEST-001',
            client_id=client.id,
            is_cmyk=True,
            has_cutting_layer=False,
            validation_notes='Document de test généré automatiquement'
        )
        db.session.add(test_bat)
        db.session.commit()
        
        # Create a consultation log
        consultation = ConsultationLog(
            bat_id=test_bat.id,
            user_id=client.id,
            ip_address='127.0.0.1',
            user_agent='Test User Agent'
        )
        db.session.add(consultation)
        db.session.commit()
        
        print('Test BAT created.')
    
    print('Test data ready.')

if __name__ == '__main__':
    app.run(debug=True)