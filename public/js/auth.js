/**
 * Clase Auth
 * 
 * Maneja la autenticación del lado del cliente mediante JWT
 */
class Auth {
    /**
     * Realiza el login del usuario
     * 
     * @param {string} username Nombre de usuario
     * @param {string} password Contraseña
     * @returns {Promise<boolean>} True si el login es exitoso
     * @throws {Error} Si hay un error en el proceso de login
     */
    static async login(username, password) {
        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            
            if (response.ok) {
                // Guardar token en localStorage
                localStorage.setItem('jwt_token', data.token);
                return true;
            }
            
            throw new Error(data.message);
        } catch (error) {
            console.error('Error en login:', error);
            throw error;
        }
    }

    /**
     * Valida si el token JWT almacenado es válido
     * 
     * @returns {Promise<boolean>} True si el token es válido
     */
    static async validateToken() {
        try {
            const token = localStorage.getItem('jwt_token');
            if (!token) return false;

            const response = await fetch('/api/validate-token', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            return response.ok;
        } catch (error) {
            console.error('Error validando token:', error);
            return false;
        }
    }

    /**
     * Cierra la sesión del usuario
     * 
     * Elimina el token JWT del localStorage y redirige a la página de login
     */
    static logout() {
        localStorage.removeItem('jwt_token');
        window.location.href = '/login';
    }
} 