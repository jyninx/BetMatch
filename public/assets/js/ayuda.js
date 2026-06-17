/*
  JavaScript para la página de ayuda.
  Controla el acordeón de preguntas frecuentes.
*/

document.addEventListener('DOMContentLoaded', () => {
  const preguntas = document.querySelectorAll('.faq-pregunta');

  preguntas.forEach(elemento => {
    elemento.addEventListener('click', () => {
      const contenedor = elemento.parentElement;
      contenedor.classList.toggle('active');
    });
  });
});
