console.log('Admin JS cargado correctamente');

// Funciones de eliminación
function deletePokemon(pokemonId) {
    if (confirm('¿Estás seguro de que quieres eliminar este Pokémon?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/pokemon/delete/${pokemonId}`;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteMove(moveId) {
    if (confirm('¿Estás seguro de que quieres eliminar este movimiento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/move/delete/${moveId}`;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteRaid(raidId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta raid?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/raid/delete/${raidId}`;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteForm(formId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta forma?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/form/delete/${formId}`;
        document.body.appendChild(form);
        form.submit();
    }
}