#!/bin/bash

import subprocess

# Lista de scripts de Python a ejecutar en el orden deseado
scripts = [
   # "poke11.py",
    "dataPokes.py",
    "dataMoves.py",
    "dataForms.py",
    "dataRaids.py",
    "downloadImage.py",
    "dataImages.py"
]

# Recorre la lista y ejecuta cada script
for script in scripts:
    try:
        print(f"Ejecutando {script}...")
        # Ejecuta el script usando subprocess
        result = subprocess.run(["python3", script], check=True)
        print(f"{script} ejecutado correctamente.")
    except subprocess.CalledProcessError as e:
        print(f"Error al ejecutar {script}: {e}")
        exit(1)
    except FileNotFoundError:
        print(f"El script {script} no existe.")
        exit(1)

print("Todos los scripts de Python se han ejecutado correctamente.")
