document.addEventListener('DOMContentLoaded', function() {
    const boton_filtro = document.getElementById('boton_filtro');
    const desplegable_filtros = document.getElementById('desplegable_filtros');
    const lista_notificaciones = document.getElementById('lista_notificaciones');

    function aplicarFiltro(filtro_valor) {
        if (!lista_notificaciones) return;

        const tarjetas = lista_notificaciones.querySelectorAll('.notificacion-card');

        tarjetas.forEach(function(tarjeta) {
            const titulo = tarjeta.querySelector('.notificacion-titulo').textContent.toLowerCase();

            if (filtro_valor === 'todas') {
                tarjeta.style.display = 'flex';
            } else if (filtro_valor === 'no_leidas') {
                const esta_leida = tarjeta.classList.contains('leida');
                tarjeta.style.display = esta_leida ? 'none' : 'flex';
            } else if (filtro_valor === 'apuestas') {
                tarjeta.style.display = titulo.indexOf('apuesta') >= 0 ? 'flex' : 'none';
            }
        });
    }

    if (boton_filtro && desplegable_filtros) {
        boton_filtro.addEventListener('click', function(evento) {
            evento.stopPropagation();

            if (desplegable_filtros.style.display === 'block') {
                desplegable_filtros.style.display = 'none';
            } else {
                desplegable_filtros.style.display = 'block';
            }
        });

        const enlaces_filtro = desplegable_filtros.querySelectorAll('a');

        enlaces_filtro.forEach(function(enlace_filtro) {
            enlace_filtro.addEventListener('click', function(evento) {
                evento.preventDefault();

                const filtro_valor = enlace_filtro.getAttribute('data-filtro');
                aplicarFiltro(filtro_valor);

                desplegable_filtros.style.display = 'none';
            });
        });
    }

    document.addEventListener('click', function(evento) {
        if (desplegable_filtros && !desplegable_filtros.contains(evento.target)) {
            desplegable_filtros.style.display = 'none';
        }
    });
});
