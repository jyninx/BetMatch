// Ajusta el margen superior del contenido para compensar el encabezado fijo
(function(){
    function aplicarAjusteEncabezado(){
        var cabecera = document.querySelector('header');
        if(!cabecera) return;

        var altura_cabecera = cabecera.offsetHeight;
        var contenido = document.querySelector('main');

        document.body.style.paddingTop = '0px';

        if(contenido) {
            contenido.style.marginTop = altura_cabecera + 'px';
        } else {
            var primer_elemento = document.body.children[1];
            if(primer_elemento) primer_elemento.style.marginTop = altura_cabecera + 'px';
        }
    }

    window.addEventListener('load', aplicarAjusteEncabezado);
    window.addEventListener('resize', aplicarAjusteEncabezado);
    setTimeout(aplicarAjusteEncabezado, 300);
})();
