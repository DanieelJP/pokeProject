import csv
import mysql.connector

# Configuración
csv_file = 'pokemon_forms.csv'

# Conexión a la base de datos
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="Dosmiltres_2003",
    database="pokemon_db"
)
cursor = conn.cursor()

# Leer el archivo CSV
with open(csv_file, mode='r', encoding='utf-8') as file:
    reader = csv.DictReader(file)
    for row in reader:
        pokemon_id = row['pokemon_id'].split('-')[0]
        form_name = row['form_name']

        # Obtener el nombre del Pokémon desde la tabla pokemons
        cursor.execute("SELECT name FROM pokemons WHERE id = %s", (pokemon_id,))
        result = cursor.fetchone()

        if result:
            pokemon_name = result[0]  # Extraer el nombre
        else:
            print(f"Error: No se encontró el Pokémon con ID {pokemon_id}")
            continue  # Saltar la inserción si no se encuentra el Pokémon

        # Insertar en la base de datos
        try:
            cursor.execute(
                "INSERT INTO pokemon_forms (pokemon_id, pokemon_name, form_name) VALUES (%s, %s, %s)",
                (pokemon_id, pokemon_name, form_name)
            )
            conn.commit()
            print(f"Forma insertada: {pokemon_id} - {pokemon_name} - {form_name}")
        except Exception as e:
            print(f"Error al insertar la forma {pokemon_id} - {pokemon_name} - {form_name}: {e}")

cursor.close()
conn.close()
