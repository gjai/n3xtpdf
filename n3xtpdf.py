#!/usr/bin/env python3
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

if __name__ == '__main__':
    app.run(debug=True)