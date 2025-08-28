from flask_wtf import FlaskForm
from flask_wtf.file import FileField, FileRequired, FileAllowed
from wtforms import StringField, TextAreaField, SubmitField, HiddenField
from wtforms.validators import DataRequired, Length

class UploadBATForm(FlaskForm):
    order_reference = StringField('Référence commande', validators=[DataRequired(), Length(min=1, max=100)])
    pdf_file = FileField('Fichier PDF', validators=[
        FileRequired(),
        FileAllowed(['pdf'], 'Seuls les fichiers PDF sont autorisés!')
    ])
    notes = TextAreaField('Notes (optionnel)', validators=[Length(max=500)])
    submit = SubmitField('Télécharger le BAT')

class BATResponseForm(FlaskForm):
    bat_id = HiddenField('BAT ID', validators=[DataRequired()])
    action = HiddenField('Action', validators=[DataRequired()])
    comments = TextAreaField('Commentaires (optionnel)', validators=[Length(max=1000)])
    submit = SubmitField('Confirmer')