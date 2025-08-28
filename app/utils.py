import os
import PyPDF2
import magic
from PIL import Image
import io

def allowed_file(filename, allowed_extensions):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in allowed_extensions

def validate_pdf_file(file_path):
    """
    Validate PDF file for CMYK color mode and cutting layer
    Returns dict with validation results
    """
    validation_results = {
        'is_valid_pdf': False,
        'is_cmyk': False,
        'has_cutting_layer': False,
        'validation_notes': []
    }
    
    try:
        # Check if file is actually a PDF
        file_type = magic.from_file(file_path, mime=True)
        if file_type != 'application/pdf':
            validation_results['validation_notes'].append('Le fichier n\'est pas un PDF valide.')
            return validation_results
        
        validation_results['is_valid_pdf'] = True
        
        # Open and analyze PDF
        with open(file_path, 'rb') as file:
            pdf_reader = PyPDF2.PdfReader(file)
            
            # Check number of pages
            num_pages = len(pdf_reader.pages)
            validation_results['validation_notes'].append(f'Nombre de pages: {num_pages}')
            
            # Basic PDF structure validation
            if num_pages == 0:
                validation_results['validation_notes'].append('PDF vide détecté.')
                return validation_results
            
            # Check for CMYK color space in PDF metadata
            # This is a simplified check - in production, you'd need more sophisticated analysis
            try:
                page = pdf_reader.pages[0]
                if '/ColorSpace' in str(page.get('/Resources', {})):
                    # Look for CMYK indicators in the PDF structure
                    resources_str = str(page.get('/Resources', {}))
                    if 'CMYK' in resources_str or 'DeviceCMYK' in resources_str:
                        validation_results['is_cmyk'] = True
                        validation_results['validation_notes'].append('Mode colorimétrique CMJN détecté.')
                    else:
                        validation_results['validation_notes'].append('Mode colorimétrique CMJN non détecté. Vérifiez votre fichier.')
                
                # Check for cutting layer (look for layer named "découpe" or similar)
                if '/OCProperties' in str(pdf_reader.metadata) or 'calque' in str(page).lower() or 'découpe' in str(page).lower():
                    validation_results['has_cutting_layer'] = True
                    validation_results['validation_notes'].append('Calque de découpe détecté.')
                else:
                    validation_results['validation_notes'].append('Calque de découpe non détecté. Ajoutez un calque "découpe" si nécessaire.')
                    
            except Exception as e:
                validation_results['validation_notes'].append(f'Erreur lors de l\'analyse: {str(e)}')
    
    except Exception as e:
        validation_results['validation_notes'].append(f'Erreur lors de la validation du PDF: {str(e)}')
    
    return validation_results

def secure_filename(filename):
    """
    Create a secure filename by removing dangerous characters
    """
    import re
    import uuid
    
    # Keep only alphanumeric characters, dots, and hyphens
    filename = re.sub(r'[^a-zA-Z0-9.-_]', '_', filename)
    
    # Generate unique prefix to avoid collisions
    unique_prefix = str(uuid.uuid4())[:8]
    
    return f"{unique_prefix}_{filename}"