<?php
$lang = $_GET['lang'] ?? 'es';

// Mapear idiomas a locales disponibles
$localeMap = [
    'en' => 'en_US.utf8',
    'es' => 'es_ES.utf8'
];

$locale = $localeMap[$lang] ?? 'es_ES.utf8';

echo "Setting up locale: $locale<br>";
$result = setlocale(LC_ALL, $locale);
echo "Setlocale result: " . ($result !== false ? "SUCCESS ($result)" : "FAILED") . "<br>";

putenv("LC_ALL=$locale");
putenv("LANGUAGE=$locale");
putenv("LANG=$locale");

$domain = "messages";
$localePath = dirname(__DIR__) . "/app/lang";
bindtextdomain($domain, $localePath);
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

echo "<br>Debug Info:<br>";
echo "Current locale: " . setlocale(LC_ALL, 0) . "<br>";
echo "Translation path: " . $localePath . "<br>";
echo "Gettext domain: " . textdomain(null) . "<br>";
echo "Environment vars:<br>";
echo "LC_ALL: " . getenv("LC_ALL") . "<br>";
echo "LANGUAGE: " . getenv("LANGUAGE") . "<br>";
echo "LANG: " . getenv("LANG") . "<br>";

echo "<br>Translation Tests:<br>";
echo "Original: Inicio<br>";
echo "Translated: " . gettext("Inicio") . "<br>";
echo "Original: Panel Admin<br>";
echo "Translated: " . gettext("Panel Admin") . "<br>";
echo "Original: Pokédex<br>";
echo "Translated: " . gettext("Pokédex") . "<br>";

// Verificar si los archivos existen
echo "<br>File checks:<br>";
echo "EN .mo exists: " . (file_exists("$localePath/en/LC_MESSAGES/messages.mo") ? "Yes" : "No") . "<br>";
echo "ES .mo exists: " . (file_exists("$localePath/es/LC_MESSAGES/messages.mo") ? "Yes" : "No") . "<br>";
echo "EN .po exists: " . (file_exists("$localePath/en/LC_MESSAGES/messages.po") ? "Yes" : "No") . "<br>";
echo "ES .po exists: " . (file_exists("$localePath/es/LC_MESSAGES/messages.po") ? "Yes" : "No") . "<br>";

// Verificar permisos
echo "<br>File permissions:<br>";
if(file_exists("$localePath/en/LC_MESSAGES/messages.mo")) {
    echo "EN .mo permissions: " . substr(sprintf('%o', fileperms("$localePath/en/LC_MESSAGES/messages.mo")), -4) . "<br>";
    echo "EN .mo owner: " . fileowner("$localePath/en/LC_MESSAGES/messages.mo") . "<br>";
}
if(file_exists("$localePath/es/LC_MESSAGES/messages.mo")) {
    echo "ES .mo permissions: " . substr(sprintf('%o', fileperms("$localePath/es/LC_MESSAGES/messages.mo")), -4) . "<br>";
    echo "ES .mo owner: " . fileowner("$localePath/es/LC_MESSAGES/messages.mo") . "<br>";
}

// Verificar gettext
echo "<br>Gettext status:<br>";
echo "Gettext enabled: " . (function_exists('gettext') ? "Yes" : "No") . "<br>";

// Contenido del archivo .mo
echo "<br>MO file content preview:<br>";
if(file_exists("$localePath/en/LC_MESSAGES/messages.mo")) {
    echo "EN .mo content (hex):<br>";
    echo "<pre>" . bin2hex(file_get_contents("$localePath/en/LC_MESSAGES/messages.mo")) . "</pre>";
} 