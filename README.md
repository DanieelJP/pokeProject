# Pokédex - Aplicación Web con PHP, MySQL y Bootstrap

## Descripción

Esta aplicación web es una Pokédex interactiva que permite a los usuarios explorar información detallada sobre Pokémon, sus movimientos, formas y raids. Incluye un panel de administración para gestionar los datos y soporte para múltiples idiomas.

## Características

- **Interfaz Responsive**: Diseñada con Bootstrap 5 para una experiencia óptima en cualquier dispositivo.
- **Visualización de Datos**: Gráficos interactivos usando Chart.js para mostrar estadísticas de Pokémon.
- **Panel de Administración**: Gestión completa de Pokémon, movimientos, raids y formas.
- **Autenticación JWT**: Sistema de login seguro con tokens JWT para proteger el panel de administración.
- **Internacionalización**: Soporte completo para español e inglés usando gettext.
- **Filtros y Búsqueda**: Funcionalidad para filtrar Pokémon por nombre y región.
- **Routing en PHP**: Sistema de rutas personalizado para una navegación fluida.

## Tecnologías Utilizadas

- **Backend**: PHP 8.0+
- **Base de Datos**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Plantillas**: Twig
- **Gráficos**: Chart.js
- **Autenticación**: Firebase JWT
- **Internacionalización**: gettext

## Requisitos

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Extensión gettext de PHP habilitada
- Composer

## Instalación

1. Clonar el repositorio:
   ```
   git clone https://github.com/DanieelJP/pokeProject.git
   cd pokeProject
   ```

2. Instalar dependencias con Composer:
   ```
   composer install
   ```

3. Configurar la base de datos:
   - Crear una base de datos MySQL llamada `pokemon_db`
   - Importar el archivo `dump-pokemon_db-202503170220.sql`
   - Configurar las credenciales en `config/config.php`

4. Configurar el servidor web:
   - Asegurarse de que el directorio `public` sea la raíz del sitio web
   - Habilitar el módulo de reescritura de URL (mod_rewrite para Apache)
   - Crear virtual host en Apache

5. Compilar los archivos de traducción:
   ```
   msgfmt -o app/lang/en/LC_MESSAGES/messages.mo app/lang/en/LC_MESSAGES/messages.po
   msgfmt -o app/lang/es/LC_MESSAGES/messages.mo app/lang/es/LC_MESSAGES/messages.po
   ```

## Estructura del Proyecto 

## Uso

### Panel de Administración

1. Acceder a `/login` o al panel de administración `/admin` (te redirigirá a la página de login) con las credenciales:
   - Usuario: `admin`
   - Contraseña: `admin123`

2. Desde el panel de administración se puede:
   - Añadir, editar y eliminar Pokémon
   - Gestionar movimientos
   - Configurar raids
   - Administrar formas alternativas

### Cambio de Idioma

- Usar el selector de idioma en la barra de navegación para cambiar entre español e inglés.

## Scraping de Datos

El proyecto incluye un script de Python con Selenium para extraer datos de Pokémon:

1. Instalar dependencias de Python:
   ```
   pip install selenium pandas
   ```

2. Ejecutar el script de scraping:
   ```
   python public/scripts/script.py
   ```


## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo LICENSE para más detalles.

## Autor

Daniel Jiménez Parreño - [jimenezdaniel@elpuig.xeill.net](mailto:jimenezdaniel@elpuig.xeill.net)
