from flask_wtf import FlaskForm
from wtforms import SelectField, IntegerField, TextAreaField, SubmitField, HiddenField
from wtforms.validators import DataRequired, NumberRange

class BatchActionForm(FlaskForm):
    action = SelectField('Action', choices=[
        ('', 'Sélectionner une action'),
        ('delete', 'Supprimer'),
        ('mark_accepted', 'Marquer comme accepté'),
        ('mark_rejected', 'Marquer comme refusé')
    ], validators=[DataRequired()])
    bat_ids = HiddenField('BAT IDs')
    submit = SubmitField('Exécuter')

class ReminderSettingsForm(FlaskForm):
    reminder_interval_days = IntegerField('Intervalle de relance (jours)', 
                                        validators=[DataRequired(), NumberRange(min=1, max=30)],
                                        default=2)
    submit = SubmitField('Sauvegarder')

class DeleteBATForm(FlaskForm):
    bat_id = HiddenField('BAT ID', validators=[DataRequired()])
    confirmation = TextAreaField('Tapez "SUPPRIMER" pour confirmer', validators=[DataRequired()])
    submit = SubmitField('Supprimer définitivement')