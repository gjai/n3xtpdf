from flask import render_template, current_app
from app.main import bp

@bp.route('/')
@bp.route('/index')
def index():
    guidelines = current_app.config['TECHNICAL_GUIDELINES']
    return render_template('index.html', title='Accueil - n3xtpdf', guidelines=guidelines)