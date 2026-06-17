// Funciones para los modales
function abrirModal(id_modal) {
    const modal = document.getElementById(id_modal);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function cerrarModal(id_modal) {
    const modal = document.getElementById(id_modal);
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const botones_abrir = document.querySelectorAll('[data-abrir-modal]');
    const botones_cerrar = document.querySelectorAll('[data-cerrar-modal]');

    botones_abrir.forEach(function(boton) {
        boton.addEventListener('click', function() {
            abrirModal(boton.getAttribute('data-abrir-modal'));
        });
    });

    botones_cerrar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            cerrarModal(boton.getAttribute('data-cerrar-modal'));
        });
    });

    window.onclick = function(evento) {
        if (evento.target.classList.contains('modal')) {
            evento.target.style.display = 'none';
        }
    };
});
