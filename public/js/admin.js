function deletePokemon(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este Pokémon? Esta acción no se puede deshacer.')) {
        fetch(`/admin/pokemon/delete/${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
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
            alert('Error al eliminar el Pokémon');
        });
    }
}

// Funciones similares para moves, raids y forms...