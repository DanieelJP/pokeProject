function changeLanguage(lang) {
    document.cookie = `lang=${lang};path=/;max-age=31536000`;  // Cookie válida por 1 año
    window.location.reload();
} 