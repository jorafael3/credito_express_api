import os
import mysql.connector
import pdfplumber
import re

# Configura la conexión a MySQL
db_config = {
    'host': '50.87.184.179',
    'user': 'wsoqajmy_jorge',
    'password': 'Equilivre3*',
    'database': 'wsoqajmy_crediweb'
}

# Conectar a MySQL
conn = mysql.connector.connect(**db_config)
cursor = conn.cursor()

# Directorio que contiene los PDFs
pdf_directory = 'docs'
phone_pattern = re.compile(r'\b593(\d{9})\b')

# Listar todos los archivos en el directorio
pdf_files = os.listdir(pdf_directory)

# Iterar sobre cada archivo PDF
for pdf_file in pdf_files:
    if pdf_file.endswith('.pdf'):
        # Separar la cédula, fecha y término del nombre del archivo
        file_name = pdf_file.replace('.pdf', '')
        parts = file_name.split('_')
        
        if len(parts) == 3:
            cedula = parts[0]
            fecha = parts[1]
            termino = parts[2]

            with pdfplumber.open(os.path.join(pdf_directory, pdf_file)) as pdf:
                text = ''
                for page in pdf.pages:
                    text += page.extract_text()

            # Buscar el número de teléfono en el texto extraído
            phone_matches = phone_pattern.findall(text)
            telefono = phone_matches[0] if phone_matches else None

           
                
            
            cursor.execute("SELECT DISTINCT cedula FROM encript_agua WHERE cedula = %s", (cedula,))
            resultados = cursor.fetchall()
            for row in resultados:
                if(cedula == row[0]):
                    print(cedula,termino,row[0],telefono)
                    consulta2 = """
                            UPDATE encript_agua
                                SET 
                                    numero = %s
                            WHERE cedula = %s
                        """
                    valores = (telefono, cedula)
                    cursor.execute(consulta2, valores)
                    conn.commit()
          

# Cerrar la conexión a la base de datos
cursor.close()
conn.close()
