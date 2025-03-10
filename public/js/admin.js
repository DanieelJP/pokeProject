console.log('Admin JS cargado correctamente');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, configurando event listeners');
    
    // Configurar listeners para botones de eliminar
    document.querySelectorAll('.delete-pokemon').forEach(button => {
        button.addEventListener('click', function() {
            const pokemonId = this.getAttribute('data-pokemon-id');
            console.log('Click en botón eliminar para Pokémon:', pokemonId);
            deletePokemon(pokemonId);
        });
    });
});

function deletePokemon(id) {
    console.log('Intentando eliminar Pokémon con ID:', id);
    if (confirm('¿Estás seguro de que quieres eliminar este Pokémon? Esta acción no se puede deshacer.')) {
        console.log('Confirmación aceptada, procediendo con la eliminación');
        
        // Obtener el token CSRF si existe
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch(`/admin/pokemon/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            credentials: 'same-origin' // Incluir cookies en la petición
        })
        .then(response => {
            console.log('Respuesta recibida:', response);
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('Pokémon eliminado correctamente');
                location.reload();
            } else {
                alert(data.error || 'Error al eliminar el Pokémon');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el Pokémon: ' + error.message);
        });
    }
}

// Funciones similares para moves, raids y forms...