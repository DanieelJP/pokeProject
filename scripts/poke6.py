from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

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

    # Imprimir los resultados
    for pokemon_url in links:
        print(f"URL: {pokemon_url}")
        print("-" * 50)

    # Ahora llamamos a la función para hacer scraping de cada uno de los enlaces
    scrape_pokemon_details(links)

# Función para extraer el bloque de información adicional (heading)
def extract_heading_block(driver):
    try:
        # Esperar a que el bloque con clase 'heading' esté presente
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'section.heading'))
        )
        
        # Encontrar el bloque de la sección con la clase 'heading'
        heading_block = driver.find_element(By.CSS_SELECTOR, 'section.heading')

        # Extraer el nombre del Pokémon
        pokemon_name = heading_block.find_element(By.CSS_SELECTOR, 'h1').text

        # Extraer el número del Pokémon y la región
        pokemon_number_and_region = heading_block.find_element(By.CSS_SELECTOR, 'h2').text

        # Extraer la opción seleccionada del dropdown (si existe)
        try:
            selected_form = heading_block.find_element(By.CSS_SELECTOR, 'select#forms option[selected]').text
        except:
            selected_form = "No disponible"

        # Imprimir la información extraída
        print(f"Nombre del Pokémon: {pokemon_name}")
        print(f"Número y región: {pokemon_number_and_region}")
        print(f"Forma seleccionada: {selected_form}")
        print("-" * 50)

    except Exception as e:
        print(f"No se pudo encontrar el bloque de encabezado: {e}")
        print("-" * 50)

# Función para hacer scraping de la información de Raid
def scrape_raid_block(driver, url):
    try:
        # Esperar a que el bloque de raid esté presente
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, '.raid-block'))
        )

        # Extraer el bloque de raid
        raid_block = driver.find_element(By.CSS_SELECTOR, '.raid-block')

        # Extraer los datos del bloque
        raid_tier = raid_block.find_element(By.CSS_SELECTOR, '.raid-tier').text
        boss_cp = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp').text
        suggested_players = raid_block.find_element(By.CSS_SELECTOR, '.raid-players').text
        boss_hp = raid_block.find_element(By.CSS_SELECTOR, '.raid-hp').text
        caught_cp_range = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp-range').text
        caught_cp_boosted = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp-boosted').text
        minimum_ivs = raid_block.find_element(By.CSS_SELECTOR, '.raid-ivs').text

        # Mostrar los resultados
        print(f"URL: {url}")
        print(f"Raid Tier: {raid_tier}")
        print(f"Boss CP: {boss_cp}")
        print(f"Suggested Players: {suggested_players}")
        print(f"Boss HP: {boss_hp}")
        print(f"Caught CP Range: {caught_cp_range}")
        print(f"Caught CP (Boosted): {caught_cp_boosted}")
        print(f"Minimum IVs: {minimum_ivs}")
        print("-" * 50)

    except Exception:
        print(f"No se encontró el bloque de Raid en {url}")
        print("-" * 50)

# Función para extraer los movimientos
def scrape_moves(driver, url):
    try:
        # Esperar a que el bloque de todos los movimientos esté presente
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'article.all-moves'))
        )
        
        # Extraer los movimientos rápidos y principales
        all_moves_section = driver.find_element(By.CSS_SELECTOR, 'article.all-moves')
        
        # Extraer los movimientos rápidos
        fast_moves_table = all_moves_section.find_elements(By.CSS_SELECTOR, 'table.moves')[0]
        fast_moves_rows = fast_moves_table.find_elements(By.CSS_SELECTOR, 'tbody tr')

        print(f"Movimientos rápidos para {url}:")
        for row in fast_moves_rows:
            move_name = row.find_element(By.CSS_SELECTOR, 'td a').text
            damage = row.find_element(By.CSS_SELECTOR, 'td.dmg').text
            eps = row.find_element(By.CSS_SELECTOR, 'td.eps').text
            dps = row.find_element(By.CSS_SELECTOR, 'td.dps').text
            print(f"Movimiento: {move_name}, Daño: {damage}, EPS: {eps}, DPS: {dps}")

        print("-" * 50)

        # Extraer los movimientos principales
        main_moves_table = all_moves_section.find_elements(By.CSS_SELECTOR, 'table.moves')[1]
        main_moves_rows = main_moves_table.find_elements(By.CSS_SELECTOR, 'tbody tr')

        print(f"Movimientos principales para {url}:")
        for row in main_moves_rows:
            move_name = row.find_element(By.CSS_SELECTOR, 'td a').text
            damage = row.find_element(By.CSS_SELECTOR, 'td.dmg').text
            eps = row.find_element(By.CSS_SELECTOR, 'td.eps').text
            dps = row.find_element(By.CSS_SELECTOR, 'td.dps').text
            print(f"Movimiento: {move_name}, Daño: {damage}, EPS: {eps}, DPS: {dps}")

        print("-" * 50)

    except Exception as e:
        print(f"No se pudo obtener los movimientos para {url}: {e}")
        print("-" * 50)

# Función para hacer scraping de la información de cada Pokémon
def scrape_pokemon_details(urls):
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

            # Extraer el bloque de encabezado
            extract_heading_block(driver)

            # Extraer el bloque de raid
            scrape_raid_block(driver, url)

            # Extraer los movimientos
            scrape_moves(driver, url)
        
        except Exception as e:
            print(f"Error al procesar {url}: {e}")
            print("-" * 50)

    # Cerrar el navegador
    driver.quit()

# Llamar a la función para hacer scraping
scrape_with_selenium(url)
