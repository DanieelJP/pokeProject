import csv
import mysql.connector

# Conectar a la base de datos
db = mysql.connector.connect(
    host="localhost",
    user="root",  # O tu usuario MySQL
    password="Dosmiltres_2003",
    database="pokemon_db"
)

cursor = db.cursor()

# Abrir el archivo CSV
with open('moves.csv', mode='r', encoding='utf-8') as file:
    reader = csv.DictReader(file)
    
    for row in reader:
        # Extraer los datos del CSV
        pokemon_id = int(row['pokemon_id'].split('-')[0])  # Obtener el ID del Pokémon desde 'pokemon_id'
        move_type = row['move_type']
        move_name = row['move_name']
        damage = int(row['damage'])
        eps = float(row['eps'])
        dps = float(row['dps'])

        # Insertar los datos en la tabla 'moves'
        sql = """
        INSERT INTO moves (pokemon_id, move_type, move_name, damage, eps, dps)
        VALUES (%s, %s, %s, %s, %s, %s)
        """
        cursor.execute(sql, (pokemon_id, move_type, move_name, damage, eps, dps))

# Confirmar cambios y cerrar la conexión
db.commit()
cursor.close()
db.close()
