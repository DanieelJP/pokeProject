/**
 * Script para el panel de administración
 * 
 * Contiene funciones para gestionar las operaciones CRUD en el panel de administración
 */
console.log('Admin JS cargado correctamente');

/**
 * Elimina un Pokémon después de confirmar con el usuario
 * 
 * @param {string} pokemonId ID del Pokémon a eliminar
 */
function deletePokemon(pokemonId) {
    if (confirm('¿Estás seguro de que quieres eliminar este Pokémon?')) {
        // Crear un formulario dinámicamente para enviar la solicitud POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/pokemon/delete/${pokemonId}`;
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Elimina un movimiento después de confirmar con el usuario
 * 
 * @param {string} moveId ID del movimiento a eliminar
 */
function deleteMove(moveId) {
    if (confirm('¿Estás seguro de que quieres eliminar este movimiento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/move/delete/${moveId}`;
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Elimina una raid después de confirmar con el usuario
 * 
 * @param {string} raidId ID de la raid a eliminar
 */
function deleteRaid(raidId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta raid?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/raid/delete/${raidId}`;
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Elimina una forma después de confirmar con el usuario
 * 
 * @param {string} formId ID de la forma a eliminar
 */
function deleteForm(formId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta forma?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/form/delete/${formId}`;
        document.body.appendChild(form);
        form.submit();
    }
}