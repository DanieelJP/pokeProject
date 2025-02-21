from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time

# URL de la página de Pokémon
url = "https://pokemon.gameinfo.io/"

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

# Función para hacer scraping de la información del bloque en cada enlace de Pokémon
def scrape_pokemon_details(urls):
    options = Options()
    options.headless = True  # Para que el navegador no se abra en pantalla
    driver = webdriver.Chrome(options=options)

    for url in urls:
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

        try:
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

        except Exception as e:
            print(f"Error al extraer los datos de {url}: {e}")

    # Cerrar el navegador
    driver.quit()

# Llamar a la función para hacer scraping
scrape_with_selenium(url)
