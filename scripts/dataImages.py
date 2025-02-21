import os
import mysql.connector

# Configuración
image_folder = 'pokemon_images'  # Carpeta donde están las imágenes descargadas

# Conexión a la base de datos
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="Dosmiltres_2003",
    database="pokemon_db"
)
cursor = conn.cursor()

# Recorrer las imágenes descargadas
for filename in os.listdir(image_folder):
    if filename.endswith('.png') or filename.endswith('.webp'):  # Solo procesar imágenes
        # Extraer el pokemon_id del nombre del archivo
        pokemon_id = filename.split('_')[0]  # El formato es "1_bulbasaur.png"
        image_path = os.path.join(image_folder, filename)

        # Insertar en la base de datos
        try:
            cursor.execute(
                "INSERT INTO pokemon_images (pokemon_id, image_path) VALUES (%s, %s)",
                (pokemon_id, image_path)
            )
            conn.commit()
            print(f"Imagen insertada en la base de datos: {filename}")
        except Exception as e:
            print(f"Error al insertar la imagen {filename}: {e}")

cursor.close()
conn.close()
