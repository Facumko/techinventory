$("#formularioAcceso").on("submit", function (evento) {
    evento.preventDefault();
    var login = $("#login").val();
    var clave = $("#clave").val();

    $.ajax({
        url: "../controladores/acceso.php?op=verificar",
        method: "POST",
        data: { login: login, clave: clave },
        dataType: "json"
    }).done(function () {
        window.location.href = "escritorio.php";
    }).fail(function (xhr) {
        var respuesta = xhr.responseJSON || {};
        bootbox.alert(respuesta.message || "No se pudo iniciar sesión.");
    });
});
