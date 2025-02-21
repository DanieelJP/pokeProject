import csv
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# URL de la página de Pokémon
url = "https://pokemon.gameinfo.io/es"

# Función para hacer scraping de los enlaces de Pokémon
def scrape_with_selenium(url):
    options = Options()
    options.headless = True  # Para que el navegador no se abra en pantalla
    driver = webdriver.Chrome(options=options)

    # Navegar a la página
    driver.get(url)

    # Aceptar las cookies si aparece la opción (esto puede ser opcional dependiendo de la página)
    try:
        cookies_button = driver.find_element(By.ID, 'onetrust-accept-btn-handler')
        cookies_button.click()
    except:
        pass

    # Dejar tiempo para cargar los elementos dinámicos
    time.sleep(3)

    # Extraer los enlaces de los Pokémon
    pokemon_links = driver.find_elements(By.CSS_SELECTOR, 'a.pokemon')

    # Crear una lista para almacenar los enlaces
    links = []

    # Iterar sobre los enlaces y extraer la URL
    for link in pokemon_links:
        href = link.get_attribute('href')
        # Verificar si es un enlace relativo y agregar solo la base si es necesario
        if href.startswith("/en/pokemon/"):
            pokemon_url = "https://pokemon.gameinfo.io" + href
        else:
            pokemon_url = href  # Si ya es una URL completa, la dejamos tal cual
        links.append(pokemon_url)

    # Cerrar el navegador después de recolectar los enlaces
    driver.quit()

    # Crear y escribir los datos en un archivo CSV
    with open('pokemons_data.csv', mode='w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        
        # Escribir encabezados para la tabla de Pokémon
        writer.writerow(['Pokemon Name', 'Number', 'Region', 'Move Type', 'Move Name', 'Damage', 'EPS', 'DPS', 'Form', 'Raid Tier', 'Boss CP', 'Suggested Players', 'Boss HP', 'Caught CP Range', 'Caught CP Boosted', 'Minimum IVs'])
        
        # Ahora llamamos a la función para hacer scraping de cada uno de los enlaces
        scrape_pokemon_details(links, writer)

# Función para extraer y escribir la información de cada Pokémon en el CSV
def scrape_pokemon_details(urls, writer):
    options = Options()
    options.headless = True  # Para que el navegador no se abra en pantalla
    driver = webdriver.Chrome(options=options)

    for url in urls:
        try:
            # Navegar a la página del Pokémon
            driver.get(url)

            # Aceptar las cookies si aparece la opción
            try:
                cookies_button = driver.find_element(By.ID, 'onetrust-accept-btn-handler')
                cookies_button.click()
            except:
                pass

            # Dejar tiempo para cargar los elementos dinámicos
            time.sleep(3)

            # Extraer los datos del bloque de encabezado
            pokemon_name, pokemon_number, pokemon_region, selected_form = extract_heading_block(driver)

            # Extraer los movimientos
            moves_data = extract_moves(driver)

            # Extraer los datos del bloque de raid
            raid_data = scrape_raid_block(driver)

            # Escribir los datos de Pokémon, movimientos y raid en el CSV
            for move in moves_data:
                writer.writerow([pokemon_name, pokemon_number, pokemon_region, move['move_type'], move['move_name'], move['damage'], move['eps'], move['dps'], selected_form, raid_data['raid_tier'], raid_data['boss_cp'], raid_data['suggested_players'], raid_data['boss_hp'], raid_data['caught_cp_range'], raid_data['caught_cp_boosted'], raid_data['minimum_ivs']])

        except Exception as e:
            print(f"Error al procesar {url}: {e}")

    # Cerrar el navegador
    driver.quit()

# Función para extraer el bloque de encabezado
def extract_heading_block(driver):
    try:
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'section.heading'))
        )
        
        heading_block = driver.find_element(By.CSS_SELECTOR, 'section.heading')

        pokemon_name = heading_block.find_element(By.CSS_SELECTOR, 'h1').text
        pokemon_number_and_region = heading_block.find_element(By.CSS_SELECTOR, 'h2').text
        selected_form = "No disponible"

        try:
            selected_form = heading_block.find_element(By.CSS_SELECTOR, 'select#forms option[selected]').text
        except:
            pass

        return pokemon_name, pokemon_number_and_region, selected_form
    except Exception as e:
        return "No disponible", "No disponible", "No disponible"

# Función para extraer los movimientos
def extract_moves(driver):
    moves_data = []
    try:
        moves_table = driver.find_elements(By.CSS_SELECTOR, '.moves tbody tr')
        for row in moves_table:
            move_type = row.find_element(By.CSS_SELECTOR, '.move-tooltip').get_attribute('data-type')
            move_name = row.find_element(By.CSS_SELECTOR, 'a').text
            damage = row.find_element(By.CSS_SELECTOR, '.dmg').text
            eps = row.find_element(By.CSS_SELECTOR, '.eps').text
            dps = row.find_element(By.CSS_SELECTOR, '.dps').text
            moves_data.append({'move_type': move_type, 'move_name': move_name, 'damage': damage, 'eps': eps, 'dps': dps})
    except Exception:
        pass
    return moves_data

# Función para extraer el bloque de raid
def scrape_raid_block(driver):
    raid_data = {}
    try:
        raid_block = driver.find_element(By.CSS_SELECTOR, '.raid-block')
        raid_data['raid_tier'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-tier').text
        raid_data['boss_cp'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp').text
        raid_data['suggested_players'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-players').text
        raid_data['boss_hp'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-hp').text
        raid_data['caught_cp_range'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp-range').text
        raid_data['caught_cp_boosted'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp-boosted').text
        raid_data['minimum_ivs'] = raid_block.find_element(By.CSS_SELECTOR, '.raid-ivs').text
    except Exception:
        pass
    return raid_data

# Llamar a la función para hacer scraping
scrape_with_selenium(url)

