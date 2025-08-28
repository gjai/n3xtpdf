import os
from flask import render_template, redirect, url_for, flash, request, current_app, jsonify
from flask_login import login_required, current_user
from sqlalchemy import func, desc
from app import db
from app.admin import bp
from app.admin.forms import BatchActionForm, ReminderSettingsForm, DeleteBATForm
from app.models import BAT, ConsultationLog, User, ReminderLog
from functools import wraps

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or not current_user.is_admin:
            flash('Accès administrateur requis.')
            return redirect(url_for('auth.login'))
        return f(*args, **kwargs)
    return decorated_function

@bp.route('/dashboard')
@login_required
@admin_required
def dashboard():
    # Get statistics
    total_bats = BAT.query.count()
    pending_bats = BAT.query.filter_by(status='pending').count()
    accepted_bats = BAT.query.filter_by(status='accepted').count()
    rejected_bats = BAT.query.filter_by(status='rejected').count()
    
    # Get recent BATs
    recent_bats = BAT.query.order_by(desc(BAT.upload_date)).limit(10).all()
    
    # Get consultation statistics
    total_consultations = ConsultationLog.query.count()
    unique_consultations = db.session.query(func.distinct(ConsultationLog.bat_id)).count()
    
    # Get BATs that need reminders
    reminder_interval = current_app.config['BAT_REMINDER_INTERVAL_DAYS']
    bats_needing_reminder = [bat for bat in BAT.query.filter_by(status='pending').all() 
                           if bat.needs_reminder(reminder_interval)]
    
    stats = {
        'total_bats': total_bats,
        'pending_bats': pending_bats,
        'accepted_bats': accepted_bats,
        'rejected_bats': rejected_bats,
        'total_consultations': total_consultations,
        'unique_consultations': unique_consultations,
        'bats_needing_reminder': len(bats_needing_reminder)
    }
    
    return render_template('admin/dashboard.html', title='Tableau de bord Admin', 
                         stats=stats, recent_bats=recent_bats)

@bp.route('/bats')
@login_required
@admin_required
def list_bats():
    page = request.args.get('page', 1, type=int)
    status_filter = request.args.get('status', '')
    
    query = BAT.query
    
    if status_filter:
        query = query.filter_by(status=status_filter)
    
    bats = query.order_by(desc(BAT.upload_date)).paginate(
        page=page, per_page=20, error_out=False)
    
    batch_form = BatchActionForm()
    
    return render_template('admin/list_bats.html', title='Gestion des BAT', 
                         bats=bats, batch_form=batch_form, status_filter=status_filter)

@bp.route('/bat/<int:bat_id>')
@login_required
@admin_required
def view_bat_details(bat_id):
    bat = BAT.query.get_or_404(bat_id)
    
    # Get consultation history
    consultations = bat.consultation_logs.order_by(desc(ConsultationLog.consultation_date)).all()
    
    # Get reminder history
    reminders = bat.reminder_logs.order_by(desc(ReminderLog.sent_date)).all()
    
    delete_form = DeleteBATForm()
    
    return render_template('admin/bat_details.html', title=f'BAT {bat.order_reference}', 
                         bat=bat, consultations=consultations, reminders=reminders, 
                         delete_form=delete_form)

@bp.route('/bat/<int:bat_id>/delete', methods=['POST'])
@login_required
@admin_required
def delete_bat(bat_id):
    bat = BAT.query.get_or_404(bat_id)
    form = DeleteBATForm()
    
    if form.validate_on_submit():
        if form.confirmation.data.strip().upper() == 'SUPPRIMER':
            # Delete physical file
            file_path = os.path.join(current_app.config['UPLOAD_FOLDER'], bat.filename)
            if os.path.exists(file_path):
                try:
                    os.remove(file_path)
                except Exception as e:
                    flash(f'Erreur lors de la suppression du fichier: {str(e)}', 'error')
            
            # Delete database record (cascades to logs)
            db.session.delete(bat)
            db.session.commit()
            
            flash(f'BAT {bat.order_reference} supprimé avec succès.')
            return redirect(url_for('admin.list_bats'))
        else:
            flash('Confirmation incorrecte. Tapez "SUPPRIMER" pour confirmer.')
    
    return redirect(url_for('admin.view_bat_details', bat_id=bat_id))

@bp.route('/batch-action', methods=['POST'])
@login_required
@admin_required
def batch_action():
    form = BatchActionForm()
    
    if form.validate_on_submit():
        bat_ids = [int(id_str) for id_str in form.bat_ids.data.split(',') if id_str.strip()]
        action = form.action.data
        
        if not bat_ids:
            flash('Aucun BAT sélectionné.')
            return redirect(url_for('admin.list_bats'))
        
        bats = BAT.query.filter(BAT.id.in_(bat_ids)).all()
        
        if action == 'delete':
            for bat in bats:
                # Delete physical file
                file_path = os.path.join(current_app.config['UPLOAD_FOLDER'], bat.filename)
                if os.path.exists(file_path):
                    try:
                        os.remove(file_path)
                    except Exception:
                        pass  # Continue even if file deletion fails
                
                db.session.delete(bat)
            
            db.session.commit()
            flash(f'{len(bats)} BAT(s) supprimé(s) avec succès.')
            
        elif action == 'mark_accepted':
            for bat in bats:
                if bat.status == 'pending':
                    bat.mark_accepted()
            
            db.session.commit()
            flash(f'{len(bats)} BAT(s) marqué(s) comme accepté(s).')
            
        elif action == 'mark_rejected':
            for bat in bats:
                if bat.status == 'pending':
                    bat.mark_rejected()
            
            db.session.commit()
            flash(f'{len(bats)} BAT(s) marqué(s) comme refusé(s).')
    
    return redirect(url_for('admin.list_bats'))

@bp.route('/consultations')
@login_required
@admin_required
def view_consultations():
    page = request.args.get('page', 1, type=int)
    
    consultations = ConsultationLog.query.order_by(desc(ConsultationLog.consultation_date)).paginate(
        page=page, per_page=50, error_out=False)
    
    return render_template('admin/consultations.html', title='Historique des consultations', 
                         consultations=consultations)

@bp.route('/settings', methods=['GET', 'POST'])
@login_required
@admin_required
def settings():
    form = ReminderSettingsForm()
    
    if form.validate_on_submit():
        # In a real application, you'd save this to a settings table
        # For now, we'll just show a success message
        flash(f'Intervalle de relance mis à jour: {form.reminder_interval_days.data} jour(s)')
        return redirect(url_for('admin.settings'))
    
    # Load current settings
    form.reminder_interval_days.data = current_app.config['BAT_REMINDER_INTERVAL_DAYS']
    
    return render_template('admin/settings.html', title='Paramètres', form=form)

@bp.route('/api/stats')
@login_required
@admin_required
def api_stats():
    """API endpoint for dashboard statistics"""
    total_bats = BAT.query.count()
    pending_bats = BAT.query.filter_by(status='pending').count()
    accepted_bats = BAT.query.filter_by(status='accepted').count()
    rejected_bats = BAT.query.filter_by(status='rejected').count()
    
    return jsonify({
        'total_bats': total_bats,
        'pending_bats': pending_bats,
        'accepted_bats': accepted_bats,
        'rejected_bats': rejected_bats
    })