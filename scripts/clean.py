import mysql.connector

# Conexión a la base de datos
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="Dosmiltres_2003",
    database="pokemon_db"
)

cursor = db.cursor()

# Suponiendo que ejecutas una consulta SELECT (aquí ejemplo con la tabla pokemons)
cursor.execute("SELECT * FROM pokemons")
resultados = cursor.fetchall()  # Obtiene todos los resultados, si es necesario

# Si no necesitas los resultados, simplemente usa:
# cursor.fetchall()  # Esto los descarta

# Si tienes múltiples consultas SELECT en tu script, usa nextset() para avanzar entre ellas
# cursor.nextset()  # Avanza al siguiente conjunto de resultados si hay más

# Realiza otra consulta, como un INSERT en la tabla moves
cursor.execute(
    "INSERT INTO moves (move_name, move_type) VALUES (%s, %s)",
    ("Tackle", "Normal")
)

# Hacer commit para guardar los cambios
db.commit()

# Realiza otra consulta, por ejemplo, en la tabla move_details
cursor.execute(
    "INSERT INTO move_details (move_id, power, accuracy) VALUES (%s, %s, %s)",
    (1, 50, 95)
)

# Commit de los cambios
db.commit()

# Cierra el cursor y la conexión
cursor.close()
db.close()
