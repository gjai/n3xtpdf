import os
from flask import render_template, redirect, url_for, flash, request, current_app, send_file, abort
from flask_login import login_required, current_user
from werkzeug.utils import secure_filename
from app import db
from app.client import bp
from app.client.forms import UploadBATForm, BATResponseForm
from app.models import BAT, ConsultationLog, User
from app.utils import allowed_file, validate_pdf_file, secure_filename as custom_secure_filename

@bp.route('/dashboard')
@login_required
def dashboard():
    if current_user.is_admin:
        return redirect(url_for('admin.dashboard'))
    
    bats = current_user.bats.order_by(BAT.upload_date.desc()).all()
    guidelines = current_app.config['TECHNICAL_GUIDELINES']
    
    return render_template('client/dashboard.html', title='Espace Client', bats=bats, guidelines=guidelines)

@bp.route('/upload', methods=['GET', 'POST'])
@login_required
def upload_bat():
    if current_user.is_admin:
        flash('Les administrateurs ne peuvent pas télécharger de BAT.')
        return redirect(url_for('admin.dashboard'))
    
    form = UploadBATForm()
    guidelines = current_app.config['TECHNICAL_GUIDELINES']
    
    if form.validate_on_submit():
        file = form.pdf_file.data
        
        if file and allowed_file(file.filename, current_app.config['ALLOWED_EXTENSIONS']):
            # Create secure filename
            filename = custom_secure_filename(file.filename)
            file_path = os.path.join(current_app.config['UPLOAD_FOLDER'], filename)
            
            # Save file
            file.save(file_path)
            
            # Validate PDF
            validation_results = validate_pdf_file(file_path)
            
            # Create BAT record
            bat = BAT(
                filename=filename,
                original_filename=file.filename,
                order_reference=form.order_reference.data,
                client_id=current_user.id,
                is_cmyk=validation_results['is_cmyk'],
                has_cutting_layer=validation_results['has_cutting_layer'],
                validation_notes='\\n'.join(validation_results['validation_notes'])
            )
            
            db.session.add(bat)
            db.session.commit()
            
            # Log the upload as a consultation
            log = ConsultationLog(
                bat_id=bat.id,
                user_id=current_user.id,
                ip_address=request.environ.get('REMOTE_ADDR'),
                user_agent=request.environ.get('HTTP_USER_AGENT')
            )
            db.session.add(log)
            db.session.commit()
            
            flash(f'BAT téléchargé avec succès! Référence: {bat.order_reference}')
            
            # Show validation warnings if any
            if not validation_results['is_cmyk']:
                flash('Attention: Le mode colorimétrique CMJN n\'a pas été détecté.', 'warning')
            if not validation_results['has_cutting_layer']:
                flash('Attention: Aucun calque de découpe détecté.', 'warning')
            
            return redirect(url_for('client.dashboard'))
        else:
            flash('Erreur lors du téléchargement du fichier.')
    
    return render_template('client/upload.html', title='Télécharger un BAT', form=form, guidelines=guidelines)

@bp.route('/bat/<token>')
def view_bat(token):
    bat = BAT.query.filter_by(secure_token=token).first_or_404()
    
    # Log the consultation
    if current_user.is_authenticated:
        log = ConsultationLog(
            bat_id=bat.id,
            user_id=current_user.id,
            ip_address=request.environ.get('REMOTE_ADDR'),
            user_agent=request.environ.get('HTTP_USER_AGENT')
        )
        db.session.add(log)
        db.session.commit()
    
    # Only allow client to view their own BATs or admins to view any
    if not current_user.is_authenticated or (not current_user.is_admin and bat.client_id != current_user.id):
        abort(403)
    
    form = BATResponseForm()
    guidelines = current_app.config['TECHNICAL_GUIDELINES']
    
    return render_template('client/view_bat.html', title=f'BAT {bat.order_reference}', 
                         bat=bat, form=form, guidelines=guidelines)

@bp.route('/bat/<token>/download')
@login_required
def download_bat(token):
    bat = BAT.query.filter_by(secure_token=token).first_or_404()
    
    # Only allow client to download their own BATs or admins to download any
    if not current_user.is_admin and bat.client_id != current_user.id:
        abort(403)
    
    file_path = os.path.join(current_app.config['UPLOAD_FOLDER'], bat.filename)
    
    if not os.path.exists(file_path):
        flash('Fichier non trouvé.')
        return redirect(url_for('client.dashboard'))
    
    # Log the download
    log = ConsultationLog(
        bat_id=bat.id,
        user_id=current_user.id,
        ip_address=request.environ.get('REMOTE_ADDR'),
        user_agent=request.environ.get('HTTP_USER_AGENT')
    )
    db.session.add(log)
    db.session.commit()
    
    return send_file(file_path, as_attachment=True, download_name=bat.original_filename)

@bp.route('/bat/respond', methods=['POST'])
@login_required
def respond_to_bat():
    form = BATResponseForm()
    
    if form.validate_on_submit():
        bat = BAT.query.get_or_404(form.bat_id.data)
        
        # Only allow client to respond to their own BATs
        if bat.client_id != current_user.id:
            abort(403)
        
        if bat.status != 'pending':
            flash('Ce BAT a déjà été traité.')
            return redirect(url_for('client.view_bat', token=bat.secure_token))
        
        action = form.action.data
        if action == 'accept':
            bat.mark_accepted()
            flash('BAT accepté! La production va commencer.')
        elif action == 'reject':
            bat.mark_rejected()
            flash('BAT refusé. Vous pouvez télécharger une nouvelle version.')
        
        # Log the response
        log = ConsultationLog(
            bat_id=bat.id,
            user_id=current_user.id,
            ip_address=request.environ.get('REMOTE_ADDR'),
            user_agent=request.environ.get('HTTP_USER_AGENT')
        )
        db.session.add(log)
        db.session.commit()
        
        return redirect(url_for('client.view_bat', token=bat.secure_token))
    
    flash('Erreur lors du traitement de la réponse.')
    return redirect(url_for('client.dashboard'))