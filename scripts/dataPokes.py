import csv
import mysql.connector

# Conexión a la base de datos MySQL
try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",  # Cambia esto si es necesario
        password="Dosmiltres_2003",  # Tu contraseña
        database="pokemon_db"  # El nombre de tu base de datos
    )
    cursor = conn.cursor()
    print("Conexión a la base de datos establecida correctamente.")
except mysql.connector.Error as err:
    print(f"Error conectando a la base de datos: {err}")
    exit()

# Leer el archivo CSV
try:
    with open('pokemons.csv', 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        next(reader)  # Salta la primera línea (cabecera)
        next(reader)  # Salta la segunda línea (URL)
        print("Archivo CSV abierto correctamente.")

        # Contador de filas procesadas
        filas_procesadas = 0
        filas_insertadas = 0

        # Iterar sobre las filas del CSV
        for row in reader:
            filas_procesadas += 1
            if len(row) < 3:  # Asegurarse de que la fila tenga al menos 3 elementos
                print(f"Fila ignorada (demasiado corta): {row}")
                continue
            
            try:
                # Extraer los datos de la fila
                pokemon_name = row[0].strip()  # Nombre del Pokémon
                pokemon_id = row[1].replace('#', '').strip()  # ID del Pokémon
                pokemon_region = row[2].replace('(', '').replace(')', '').strip()  # Región del Pokémon

                # Validar el ID del Pokémon
                if not pokemon_id.isdigit():
                    print(f"Fila ignorada (ID no válido): {row}")
                    continue

                # Validar la región
                if not pokemon_region:
                    print(f"Fila ignorada (región vacía): {row}")
                    continue

                # Insertar datos en la base de datos
                cursor.execute("""
                    INSERT INTO pokemons (name, pokemon_id, region)
                    VALUES (%s, %s, %s)
                """, (pokemon_name, pokemon_id, pokemon_region))
                filas_insertadas += 1
                print(f"Fila insertada: {pokemon_name}, {pokemon_id}, {pokemon_region}")

            except mysql.connector.Error as err:
                print(f"Error insertando fila: {row} -> {err}")
                continue
            except Exception as e:
                print(f"Error inesperado procesando la fila: {row} -> {e}")
                continue

        # Confirmar los cambios en la base de datos
        conn.commit()
        print(f"Proceso completado. Filas procesadas: {filas_procesadas}, Filas insertadas: {filas_insertadas}.")

except FileNotFoundError:
    print("Error: El archivo 'pokemons.csv' no se encontró.")
except Exception as e:
    print(f"Error inesperado al procesar el archivo CSV: {e}")
finally:
    # Cerrar la conexión
    if conn.is_connected():
        cursor.close()
        conn.close()
        print("Conexión a la base de datos cerrada.")