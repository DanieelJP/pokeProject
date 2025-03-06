class Auth {
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

    static logout() {
        localStorage.removeItem('jwt_token');
        window.location.href = '/login';
    }
} 