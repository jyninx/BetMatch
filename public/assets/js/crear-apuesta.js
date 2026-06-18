document.addEventListener('DOMContentLoaded', function() {
    const selector_liga = document.getElementById('liga');
    const selector_evento = document.getElementById('evento');
    const selector_ganador = document.getElementById('ganador_esperado');

    function cargarEventos() {
        const liga = selector_liga.value;

        selector_ganador.innerHTML = '<option value="">▼ GANADOR ESPERADO ▼</option><option value="" disabled>Selecciona un evento primero</option>';
        selector_ganador.disabled = true;

        if (!liga) {
            selector_evento.innerHTML = '<option value="">▼ EVENTO ▼</option>';
            selector_evento.disabled = true;
            return;
        }

        fetch('get-eventos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'liga=' + encodeURIComponent(liga)
        })
        .then(respuesta => respuesta.json())
        .then(datos => {
            selector_evento.innerHTML = '<option value="">▼ EVENTO ▼</option>';

            if (datos.length > 0) {
                datos.forEach(function(partido) {
                    selector_evento.innerHTML += `<option value="${partido.id}" data-nombre="${partido.nombre_evento}">${partido.nombre_evento}</option>`;
                });

                selector_evento.disabled = false;
            } else {
                selector_evento.innerHTML = '<option value="">▼ EVENTO ▼</option><option value="" disabled>No hay eventos disponibles</option>';
                selector_evento.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            selector_evento.innerHTML = '<option value="">▼ EVENTO ▼</option><option value="" disabled>Error al cargar eventos</option>';
            selector_evento.disabled = true;
        });
    }

    function actualizarGanador() {
        const opcion_seleccionada = selector_evento.options[selector_evento.selectedIndex];
        const nombre_evento = opcion_seleccionada.getAttribute('data-nombre');

        if (!nombre_evento) {
            selector_ganador.innerHTML = '<option value="">▼ GANADOR ESPERADO ▼</option><option value="" disabled>Selecciona un evento primero</option>';
            selector_ganador.disabled = true;
            return;
        }

        const partes = nombre_evento.split(' vs ');

        if (partes.length === 2) {
            const equipo_local = partes[0].trim();
            const equipo_visitante = partes[1].trim();

            selector_ganador.innerHTML = `
                <option value="">▼ GANADOR ESPERADO ▼</option>
                <option value="${equipo_local}">${equipo_local}</option>
                <option value="${equipo_visitante}">${equipo_visitante}</option>
            `;
            selector_ganador.disabled = false;
        } else {
            selector_ganador.innerHTML = '<option value="">▼ GANADOR ESPERADO ▼</option><option value="" disabled>No se pudieron cargar los equipos</option>';
            selector_ganador.disabled = true;
        }
    }

    function mostrarResultadoSincronizacion(mensaje, tipo) {
        const resultado = document.getElementById('resultadoSincronizacion');
        if (!resultado) return;

        resultado.style.display = 'block';
        resultado.className = tipo === 'error' ? 'alert error' : 'alert success';
        resultado.textContent = mensaje;
    }

    function sincronizarEventos() {
        const boton = document.getElementById('btnSincronizarEventos');
        if (!boton) return;

        boton.disabled = true;
        boton.textContent = 'Sincronizando...';
        mostrarResultadoSincronizacion('Sincronizando eventos, espera un momento...', 'success');

        fetch('sync-events.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'fecha_inicio=' + encodeURIComponent(new Date().toISOString().slice(0, 10)) + '&dias=1'
        })
        .then(respuesta => {
            if (!respuesta.ok) {
                return respuesta.text().then(texto => {
                    throw new Error(texto || 'La respuesta no es JSON');
                });
            }
            return respuesta.json();
        }).then(datos => {

    console.log('RESPUESTA SYNC:', datos);

    boton.disabled = false;
    boton.textContent = 'Sincronizar eventos';

    if (datos.ok) {
        
        mostrarResultadoSincronizacion(
            datos.mensaje || 'Sincronización completada',
            'success'
        );

        if (selector_liga) {
            cargarEventos();
        }

    } else {

        mostrarResultadoSincronizacion(
            datos.error || datos.mensaje || 'No se pudieron sincronizar los eventos.',
            'error'
        );
    }
})
      /*  .then(datos => {
            boton.disabled = false;
            boton.textContent = 'Sincronizar eventos';

            if (datos.ok) {
                mostrarResultadoSincronizacion(datos.mensaje, 'success');
                if (selector_liga) cargarEventos();
            } else {
                mostrarResultadoSincronizacion(datos.mensaje || 'No se pudieron sincronizar los eventos.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            boton.disabled = false;
            boton.textContent = 'Sincronizar eventos';
            mostrarResultadoSincronizacion('Error al conectar con el servidor.', 'error');
        });*/
    }

    if (selector_liga) {
        selector_liga.addEventListener('change', cargarEventos);
    }

    if (selector_evento) {
        selector_evento.addEventListener('change', actualizarGanador);
    }

    const boton_sincronizar = document.getElementById('btnSincronizarEventos');
    if (boton_sincronizar) {
        boton_sincronizar.addEventListener('click', sincronizarEventos);
    }

    if (selector_liga && selector_liga.value) {
        cargarEventos();
        setTimeout(() => {
            if (selector_evento.value) {
                actualizarGanador();
            }
        }, 500);
    }
});
