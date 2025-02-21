from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time

# URL de la página de Pokémon
url = "https://pokemon.gameinfo.io/"

# Función para hacer scraping con Selenium
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

    # Imprimir los resultados
    for pokemon_url in links:
        print(f"URL: {pokemon_url}")
        print("-" * 50)

    # Cerrar el navegador
    driver.quit()

# Llamar a la función para hacer scraping
scrape_with_selenium(url)
