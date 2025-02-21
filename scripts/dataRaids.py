import csv
import mysql.connector

# Conexión a la base de datos
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="Dosmiltres_2003",
    database="pokemon_db"
)
cursor = conn.cursor(buffered=True)  # Usar buffered=True para evitar el error "Unread result found"

# Leer el CSV y cargar los datos en la base de datos
with open('raids.csv', newline='', encoding='utf-8') as csvfile:
    reader = csv.DictReader(csvfile)
    for row in reader:
        pokemon_name = row['pokemon_id'].split('-')[1]  # Extraemos el nombre del Pokémon
        raid_tier = row['raid_tier']
        
        # Validar y convertir a int los campos numéricos
        boss_cp = float(row['boss_cp']) if row['boss_cp'].replace('.', '', 1).isdigit() else 0.0
        boss_cp = int(boss_cp)  # Convertir a int (trunca los decimales)
        
        suggested_players = int(row['suggested_players']) if row['suggested_players'].isdigit() else 1
        boss_hp = int(row['boss_hp']) if row['boss_hp'].isdigit() else 600

        caught_cp_range = row['caught_cp_range']
        caught_cp_boosted = row['caught_cp_boosted']
        minimum_ivs = row['minimum_ivs']

        # Buscar el ID del Pokémon en la tabla pokemons
        cursor.execute("SELECT id FROM pokemons WHERE name = %s", (pokemon_name,))
        result = cursor.fetchone()
        
        # Asegurarse de que el resultado ha sido encontrado
        if result:
            pokemon_id = result[0]
            # Realizar el insert solo si se ha encontrado el Pokémon
            cursor.execute(
                "INSERT INTO raids (pokemon_id, raid_tier, boss_cp, suggested_players, boss_hp, caught_cp_range, caught_cp_boosted, minimum_ivs) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
                (pokemon_id, raid_tier, boss_cp, suggested_players, boss_hp, caught_cp_range, caught_cp_boosted, minimum_ivs)
            )
            conn.commit()

# Asegurarse de cerrar la conexión al final
cursor.close()
conn.close()