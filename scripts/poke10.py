import csv
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Función para guardar los datos en CSV
def save_to_csv(filename, data, header):
    try:
        with open(filename, mode='a', newline='', encoding='utf-8') as file:
            writer = csv.writer(file)
            if file.tell() == 0:  # Solo escribir el encabezado si el archivo está vacío
                writer.writerow(header)
            writer.writerows(data)
    except Exception as e:
        print(f"Error al guardar en CSV: {e}")

# Función para hacer scraping de los enlaces de Pokémon
def scrape_with_selenium(url):
    options = Options()
    options.headless = True
    driver = webdriver.Chrome(options=options)
    
    driver.get(url)

    try:
        cookies_button = driver.find_element(By.ID, 'onetrust-accept-btn-handler')
        cookies_button.click()
    except:
        pass

    time.sleep(3)

    pokemon_links = driver.find_elements(By.CSS_SELECTOR, 'a.pokemon')
    links = []

    for link in pokemon_links:
        href = link.get_attribute('href')
        if href.startswith("/es/pokemon/"):
            pokemon_url = "https://pokemon.gameinfo.io" + href
        else:
            pokemon_url = href
        links.append(pokemon_url)

    driver.quit()

    # Guardar los enlaces para luego hacer scraping de los detalles de cada Pokémon
    save_to_csv('pokemons.csv', [(url.split("/")[-1], url)], ['pokemon_id', 'url'])
    scrape_pokemon_details(links)

# Función para extraer la información básica de cada Pokémon
def extract_heading_block(driver):
    try:
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'section.heading'))
        )
        
        heading_block = driver.find_element(By.CSS_SELECTOR, 'section.heading')
        pokemon_name = heading_block.find_element(By.CSS_SELECTOR, 'h1').text
        pokemon_number_and_region = heading_block.find_element(By.CSS_SELECTOR, 'h2').text
        
        # Extraer la opción seleccionada del dropdown (si existe)
        try:
            selected_form = heading_block.find_element(By.CSS_SELECTOR, 'select#forms option[selected]').text
        except:
            selected_form = "No disponible"

        # Guardar en la tabla pokemons y pokemon_forms
        pokemon_data = [(pokemon_name, pokemon_number_and_region.split()[0], pokemon_number_and_region.split()[1])]
        save_to_csv('pokemons.csv', pokemon_data, ['name', 'number', 'region'])
        
        form_data = [(pokemon_name, selected_form)]
        save_to_csv('pokemon_forms.csv', form_data, ['pokemon_name', 'form_name'])

    except Exception as e:
        print(f"No se pudo encontrar el bloque de encabezado: {e}")

# Función para extraer la información de raid
def scrape_raid_block(driver, url):
    try:
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, '.raid-block'))
        )
        
        raid_block = driver.find_element(By.CSS_SELECTOR, '.raid-block')
        raid_tier = raid_block.find_element(By.CSS_SELECTOR, '.raid-tier').text
        boss_cp = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp').text
        suggested_players = raid_block.find_element(By.CSS_SELECTOR, '.raid-players').text
        boss_hp = raid_block.find_element(By.CSS_SELECTOR, '.raid-hp').text
        caught_cp_range = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp-range').text
        caught_cp_boosted = raid_block.find_element(By.CSS_SELECTOR, '.raid-cp-boosted').text
        minimum_ivs = raid_block.find_element(By.CSS_SELECTOR, '.raid-ivs').text

        raid_data = [(url.split("/")[-1], raid_tier, boss_cp, suggested_players, boss_hp, caught_cp_range, caught_cp_boosted, minimum_ivs)]
        save_to_csv('raids.csv', raid_data, ['pokemon_id', 'raid_tier', 'boss_cp', 'suggested_players', 'boss_hp', 'caught_cp_range', 'caught_cp_boosted', 'minimum_ivs'])

    except Exception:
        print(f"No se encontró el bloque de Raid en {url}")

# Función para extraer los movimientos
def scrape_moves(driver, url):
    try:
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'article.all-moves'))
        )
        
        all_moves_section = driver.find_element(By.CSS_SELECTOR, 'article.all-moves')
        
        # Extraer movimientos rápidos
        fast_moves_table = all_moves_section.find_elements(By.CSS_SELECTOR, 'table.moves')[0]
        fast_moves_rows = fast_moves_table.find_elements(By.CSS_SELECTOR, 'tbody tr')

        move_data = []
        for row in fast_moves_rows:
            move_name = row.find_element(By.CSS_SELECTOR, 'td a').text
            damage = row.find_element(By.CSS_SELECTOR, 'td.dmg').text
            eps = row.find_element(By.CSS_SELECTOR, 'td.eps').text
            dps = row.find_element(By.CSS_SELECTOR, 'td.dps').text
            move_data.append((url.split("/")[-1], 'fast', move_name, damage, eps, dps))
        
        # Extraer movimientos principales
        main_moves_table = all_moves_section.find_elements(By.CSS_SELECTOR, 'table.moves')[1]
        main_moves_rows = main_moves_table.find_elements(By.CSS_SELECTOR, 'tbody tr')

        for row in main_moves_rows:
            move_name = row.find_element(By.CSS_SELECTOR, 'td a').text
            damage = row.find_element(By.CSS_SELECTOR, 'td.dmg').text
            eps = row.find_element(By.CSS_SELECTOR, 'td.eps').text
            dps = row.find_element(By.CSS_SELECTOR, 'td.dps').text
            move_data.append((url.split("/")[-1], 'main', move_name, damage, eps, dps))

        save_to_csv('moves.csv', move_data, ['pokemon_id', 'move_type', 'move_name', 'damage', 'eps', 'dps'])

    except Exception as e:
        print(f"No se pudo obtener los movimientos para {url}: {e}")

# Función para extraer los enlaces de las imágenes
def scrape_image_links(driver, url):
    try:
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'div.preview'))
        )
        
        preview_div = driver.find_element(By.CSS_SELECTOR, 'div.preview')
        img_element = preview_div.find_element(By.CSS_SELECTOR, 'img')
        img_url = img_element.get_attribute('src')

        # Guardar el enlace de la imagen en el CSV
        save_to_csv('pokemon_images.csv', [(url.split("/")[-1], img_url)], ['pokemon_id', 'image_url'])

    except Exception as e:
        print(f"No se pudo obtener la imagen para {url}: {e}")

# Función principal de scraping
def scrape_pokemon_details(urls):
    options = Options()
    options.headless = True
    driver = webdriver.Chrome(options=options)

    for url in urls:
        try:
            driver.get(url)

            # Aceptar cookies
            try:
                cookies_button = driver.find_element(By.ID, 'onetrust-accept-btn-handler')
                cookies_button.click()
            except:
                pass

            time.sleep(3)

            # Extraer los datos básicos del Pokémon
            extract_heading_block(driver)

            # Extraer los datos de raid
            scrape_raid_block(driver, url)

            # Extraer los movimientos
            scrape_moves(driver, url)

            # Extraer los enlaces de las imágenes
            scrape_image_links(driver, url)
        
        except Exception as e:
            print(f"Error al procesar {url}: {e}")

    driver.quit()

# Iniciar el scraping
scrape_with_selenium("https://pokemon.gameinfo.io/es")