import os
import requests
import csv

# Configuración
csv_file = 'pokemon_images.csv'  # Archivo CSV con los enlaces de las imágenes
output_folder = 'pokemon_images'  # Carpeta donde se guardarán las imágenes

# Crear la carpeta de salida si no existe
if not os.path.exists(output_folder):
    os.makedirs(output_folder)

# Leer el archivo CSV
with open(csv_file, mode='r', encoding='utf-8') as file:
    reader = csv.DictReader(file)
    for row in reader:
        # Extraer el pokemon_id y el nombre del Pokémon
        pokemon_id = row['pokemon_id']  # Formato: "1-bulbasaur"
        image_url = row['image_url']

        # Separar el número y el nombre del Pokémon
        pokemon_number, pokemon_name = pokemon_id.split('-', 1)  # Dividir en el primer guion

        # Descargar la imagen
        try:
            response = requests.get(image_url, stream=True)
            if response.status_code == 200:
                # Obtener la extensión del archivo (por ejemplo, .png, .webp)
                extension = image_url.split('.')[-1]
                # Crear el nombre del archivo
                filename = f"{pokemon_number}_{pokemon_name}.{extension}"
                filepath = os.path.join(output_folder, filename)

                # Guardar la imagen
                with open(filepath, 'wb') as img_file:
                    for chunk in response.iter_content(1024):
                        img_file.write(chunk)
                print(f"Imagen descargada: {filename}")
            else:
                print(f"Error al descargar la imagen para Pokémon ID {pokemon_id}: {response.status_code}")
        except Exception as e:
            print(f"Error al descargar la imagen para Pokémon ID {pokemon_id}: {e}")