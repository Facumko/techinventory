(function () {
    "use strict";

    var nombreModulo = window.moduloInventario;
    var entidadServidor = nombreModulo === "enuso" ? "asignaciones" : nombreModulo;
    var urlControlador = "../controladores/inventario.php";
    var tokenCsrf = $('meta[name="csrf-token"]').attr("content");
    var catalogos = { sedes: [], empleados: [], activos: [], tipos: [], estados: [] };
    var registrosPorId = {};
    var tablaDatos = null;

    var camposPorModulo = {
        sedes: [
            { nombre: "nombre", etiqueta: "Nombre", tipo: "text", requerido: true, maximo: 80 },
            { nombre: "planta", etiqueta: "Planta", tipo: "text", requerido: true, maximo: 20 },
            { nombre: "descripcion", etiqueta: "Descripción", tipo: "textarea", requerido: true, maximo: 1000, ancho: 12 }
        ],
        empleados: [
            { nombre: "nombre", etiqueta: "Nombre", tipo: "text", requerido: true, maximo: 40 },
            { nombre: "apellido", etiqueta: "Apellido", tipo: "text", requerido: true, maximo: 40 },
            { nombre: "dni", etiqueta: "DNI (sin puntos)", tipo: "text", requerido: true, maximo: 8, patron: "[0-9]{7,8}" },
            { nombre: "id_sede", etiqueta: "Sede", tipo: "selectSede", requerido: true }
        ],
        activos: [
            { nombre: "marca", etiqueta: "Marca", tipo: "text", requerido: true, maximo: 40 },
            { nombre: "tipo", etiqueta: "Tipo de activo", tipo: "selectTipo", requerido: true },
            { nombre: "modelo", etiqueta: "Modelo", tipo: "text", maximo: 40 },
            { nombre: "numero_serie", etiqueta: "Número de serie", tipo: "text", maximo: 100 },
            { nombre: "estado", etiqueta: "Estado físico", tipo: "selectEstado", requerido: true },
            { nombre: "id_sede", etiqueta: "Ubicación (sede)", tipo: "selectSede", requerido: true }
        ],
        asignaciones: [
            { nombre: "id_empleado", etiqueta: "Empleado", tipo: "selectEmpleado", requerido: true },
            { nombre: "id_activo", etiqueta: "Activo disponible", tipo: "selectActivo", requerido: true },
            { nombre: "fecha_entrega", etiqueta: "Fecha de entrega", tipo: "date", requerido: true },
            { nombre: "estado_equipo", etiqueta: "Estado del equipo al entregar", tipo: "selectEstado", requerido: true },
            { nombre: "observaciones", etiqueta: "Observaciones", tipo: "textarea", maximo: 1000, ancho: 12 }
        ]
    };

    function escaparHtml(valor) {
        return String(valor === null || valor === undefined ? "" : valor).replace(/[&<>"']/g, function (caracter) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[caracter];
        });
    }

    function fechaDeHoy() {
        var ahora = new Date();
        return new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    }

    function mensajeDeError(xhr, mensajePorDefecto) {
        var respuesta = xhr.responseJSON || {};
        return respuesta.message || mensajePorDefecto;
    }

    // Todas las llamadas al servidor son POST y llevan el token CSRF en la cabecera
    function solicitar(operacion, datos) {
        var parametros = $.extend({}, datos, { op: operacion, entidad: entidadServidor });
        return $.ajax({
            url: urlControlador,
            method: "POST",
            data: parametros,
            dataType: "json",
            headers: { "X-CSRF-Token": tokenCsrf }
        });
    }

    function opcionesHtml(items, campoValor, funcionTexto) {
        return '<option value="">Seleccionar...</option>' + items.map(function (item) {
            return '<option value="' + escaparHtml(item[campoValor]) + '">' + escaparHtml(funcionTexto(item)) + "</option>";
        }).join("");
    }

    function opcionesDeLista(lista) {
        return lista.map(function (valor) {
            return '<option value="' + escaparHtml(valor) + '">' + escaparHtml(valor) + "</option>";
        }).join("");
    }

    function textoSede(sede) { return sede.nombre + " · " + sede.planta; }
    function textoEmpleado(empleado) { return empleado.apellido + ", " + empleado.nombre + " · DNI " + empleado.dni; }
    function textoActivo(activo) {
        return activo.tipo + " " + activo.marca + (activo.modelo ? " " + activo.modelo : "") + (activo.numero_serie ? " · " + activo.numero_serie : "");
    }

    function controlDelCampo(campo) {
        var nombre = escaparHtml(campo.nombre);
        var requerido = campo.requerido ? " required" : "";
        switch (campo.tipo) {
            case "selectSede":
                return '<select class="form-control" name="' + nombre + '"' + requerido + ">" + opcionesHtml(catalogos.sedes, "id", textoSede) + "</select>";
            case "selectEmpleado":
                return '<select class="form-control" name="' + nombre + '"' + requerido + ">" + opcionesHtml(catalogos.empleados, "id", textoEmpleado) + "</select>";
            case "selectActivo":
                return '<select class="form-control" name="' + nombre + '"' + requerido + ">" + opcionesHtml(catalogos.activos, "id", textoActivo) + "</select>";
            case "selectTipo":
                return '<select class="form-control" name="' + nombre + '"' + requerido + ">" + opcionesDeLista(catalogos.tipos) + "</select>";
            case "selectEstado":
                return '<select class="form-control" name="' + nombre + '"' + requerido + ">" + opcionesDeLista(catalogos.estados) + "</select>";
            case "textarea":
                return '<textarea class="form-control" name="' + nombre + '" rows="3"' + (campo.maximo ? ' maxlength="' + campo.maximo + '"' : "") + requerido + "></textarea>";
            default:
                return '<input class="form-control" type="' + escaparHtml(campo.tipo) + '" name="' + nombre + '"' +
                    (campo.maximo ? ' maxlength="' + campo.maximo + '"' : "") +
                    (campo.patron ? ' pattern="' + campo.patron + '"' : "") + requerido + ">";
        }
    }

    function htmlDelCampo(campo) {
        var ancho = campo.ancho || 6;
        var marcaObligatorio = campo.requerido ? ' <span class="text-red">*</span>' : "";
        return '<div class="form-group col-sm-' + ancho + '"><label>' + escaparHtml(campo.etiqueta) + marcaObligatorio + "</label>" + controlDelCampo(campo) + "</div>";
    }

    function abrirFormulario(registro) {
        var formulario = $("#formularioRegistro");
        formulario[0].reset();
        $("#idRegistro").val(registro ? registro.id : "");
        var titulos = { sedes: "sede", empleados: "empleado", activos: "activo" };
        $("#tituloModal").text(entidadServidor === "asignaciones"
            ? "Registrar entrega de un activo"
            : (registro ? "Editar " : "Nuevo ") + titulos[entidadServidor]);
        $("#camposFormulario").html(camposPorModulo[entidadServidor].map(htmlDelCampo).join(""));
        if (entidadServidor === "asignaciones") {
            formulario.find("[name='fecha_entrega']").val(fechaDeHoy());
        }
        if (registro) {
            camposPorModulo[entidadServidor].forEach(function (campo) {
                formulario.find("[name='" + campo.nombre + "']").val(registro[campo.nombre]);
            });
        }
        $("#modalRegistro").modal("show");
    }

    function etiquetaEstado(activo, textoSi, textoNo) {
        return Number(activo) === 1
            ? '<span class="label label-success">' + textoSi + "</span>"
            : '<span class="label label-default">' + textoNo + "</span>";
    }

    function botonEditar(registro) {
        return '<button class="btn btn-warning btn-xs" data-accion="editar" data-id="' + registro.id + '" title="Editar"><i class="fa fa-pencil"></i></button> ';
    }

    function botonCambiarEstado(registro) {
        var estaActivo = Number(registro.activo) === 1;
        return '<button class="btn btn-' + (estaActivo ? "danger" : "primary") + ' btn-xs" data-accion="estado" data-activo="' + (estaActivo ? 0 : 1) +
            '" data-id="' + registro.id + '" title="' + (estaActivo ? "Desactivar" : "Activar") + '"><i class="fa fa-' + (estaActivo ? "ban" : "check") + '"></i></button>';
    }

    function botonVerActivosACargo(registro) {
        return ' <button class="btn btn-info btn-xs" data-accion="verACargo" data-id="' + registro.id + '" title="Ver qué activos tiene a cargo"><i class="fa fa-list"></i></button>';
    }

    function botonEliminarActivo(registro) {
        var puedeEliminar = Number(registro.disponible) === 1;
        return '<button class="btn btn-danger btn-xs" data-accion="eliminarActivo" data-id="' + registro.id + '" title="Eliminar"' + (puedeEliminar ? "" : " disabled") + '><i class="fa fa-trash"></i></button>';
    }

    function filaDeAsignacion(registro) {
        var botonDevolver = registro.fecha_devolucion
            ? ""
            : '<button class="btn btn-primary btn-xs" data-accion="devolver" data-id="' + registro.id + '"><i class="fa fa-undo"></i> Devolver</button>';
        return [
            botonDevolver,
            escaparHtml(registro.empleado + " · DNI " + registro.dni),
            escaparHtml(registro.sede + " · " + registro.planta),
            escaparHtml(registro.activo + (registro.numero_serie ? " · " + registro.numero_serie : "")),
            escaparHtml(registro.fecha_entrega),
            registro.fecha_devolucion ? escaparHtml(registro.fecha_devolucion) : '<span class="label label-warning">Pendiente</span>',
            escaparHtml(registro.estado_equipo),
            escaparHtml(registro.estado_devolucion || "—"),
            escaparHtml(registro.observaciones || "—")
        ];
    }

    function filaDeTabla(registro) {
        switch (entidadServidor) {
            case "sedes":
                return [botonEditar(registro) + botonCambiarEstado(registro), escaparHtml(registro.nombre), escaparHtml(registro.planta), escaparHtml(registro.descripcion), etiquetaEstado(registro.activo, "Activa", "Inactiva")];
            case "empleados":
                var cantidad = Number(registro.activos_a_cargo);
                return [botonEditar(registro) + botonCambiarEstado(registro) + botonVerActivosACargo(registro), escaparHtml(registro.nombre), escaparHtml(registro.apellido), escaparHtml(registro.dni),
                    escaparHtml(registro.sede + " · " + registro.planta),
                    cantidad > 0 ? '<span class="label label-warning">' + cantidad + "</span>" : "0",
                    etiquetaEstado(registro.activo, "Activo", "Inactivo")];
            case "activos":
                return [botonEditar(registro) + botonEliminarActivo(registro), escaparHtml(registro.marca), escaparHtml(registro.tipo), escaparHtml(registro.modelo || "—"), escaparHtml(registro.numero_serie || "—"),
                    escaparHtml(registro.sede + " · " + registro.planta), escaparHtml(registro.estado),
                    Number(registro.disponible) === 1 ? '<span class="label label-success">Disponible</span>' : '<span class="label label-warning">Asignado</span>'];
            default:
                return filaDeAsignacion(registro);
        }
    }

    var traduccionTabla = {
        emptyTable: "No hay registros para mostrar", info: "Mostrando _START_ a _END_ de _TOTAL_ registros", infoEmpty: "Mostrando 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros)", lengthMenu: "Mostrar _MENU_ registros", loadingRecords: "Cargando...", search: "Buscar:",
        zeroRecords: "No se encontraron coincidencias", paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
    };

    function cargarTabla() {
        solicitar("listar").done(function (respuesta) {
            var registros = respuesta.data;
            if (nombreModulo === "enuso") {
                registros = registros.filter(function (registro) { return !registro.fecha_devolucion; });
            }
            registrosPorId = {};
            registros.forEach(function (registro) { registrosPorId[registro.id] = registro; });
            if (tablaDatos) { tablaDatos.destroy(); }
            $("#tablaInventario tbody").empty();
            tablaDatos = $("#tablaInventario").DataTable({
                data: registros.map(filaDeTabla),
                pageLength: 10,
                order: [[1, "asc"]],
                columnDefs: [{ orderable: false, targets: 0 }],
                dom: "Bfrtip",
                buttons: ["copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"],
                language: traduccionTabla
            });
        }).fail(function (xhr) {
            bootbox.alert(escaparHtml(mensajeDeError(xhr, "No se pudieron cargar los registros.")));
        });
    }

    function cargarCatalogos() {
        return solicitar("catalogos").then(function (respuesta) { catalogos = respuesta.data; });
    }

    function avisarYRecargar(respuesta) {
        bootbox.alert(escaparHtml(respuesta.message));
        cargarTabla();
    }

    function mostrarActivosACargo(idEmpleado) {
        var empleado = registrosPorId[idEmpleado];
        solicitar("pendientesEmpleado", { id: idEmpleado }).done(function (respuesta) {
            var titulo = escaparHtml(empleado.apellido + ", " + empleado.nombre);
            if (!respuesta.data.length) {
                return bootbox.alert("<strong>" + titulo + "</strong> no tiene activos pendientes de devolución.");
            }
            var lineas = respuesta.data.map(function (activo) {
                return "<li>" + escaparHtml(activo.tipo + " " + activo.marca + (activo.modelo ? " " + activo.modelo : "") +
                    (activo.numero_serie ? " (" + activo.numero_serie + ")" : "") + " — entregado el " + activo.fecha_entrega + ", estado: " + activo.estado_equipo) + "</li>";
            }).join("");
            bootbox.alert("<strong>" + titulo + "</strong> debe devolver:<ul>" + lineas + "</ul>");
        }).fail(function (xhr) {
            bootbox.alert(escaparHtml(mensajeDeError(xhr, "No se pudo consultar.")));
        });
    }

    function abrirDevolucion(asignacion) {
        $("#formularioDevolucion")[0].reset();
        $("#idAsignacionDevolucion").val(asignacion.id);
        $("#detalleDevolucion").text(asignacion.activo + " — a cargo de " + asignacion.empleado);
        $("#fechaDevolucion").val(fechaDeHoy());
        $("#estadoDevolucion").html(opcionesDeLista(catalogos.estados)).val(asignacion.estado_equipo);
        $("#modalDevolucion").modal("show");
    }

    function confirmarYEjecutar(pregunta, operacion, datos, mensajeError) {
        bootbox.confirm(pregunta, function (confirmado) {
            if (!confirmado) { return; }
            solicitar(operacion, datos).done(avisarYRecargar).fail(function (xhr) {
                bootbox.alert(escaparHtml(mensajeDeError(xhr, mensajeError)));
            });
        });
    }

    $("#botonAgregar").on("click", function () {
        cargarCatalogos().then(function () { abrirFormulario(null); }).fail(function (xhr) {
            bootbox.alert(escaparHtml(mensajeDeError(xhr, "No se pudieron cargar las opciones.")));
        });
    });

    $("#formularioRegistro").on("submit", function (evento) {
        evento.preventDefault();
        var operacion = entidadServidor === "asignaciones" ? "asignar" : "guardar";
        $("#botonGuardar").prop("disabled", true);
        solicitar(operacion, $(this).serializeArray().reduce(function (datos, campo) { datos[campo.name] = campo.value; return datos; }, {}))
            .done(function (respuesta) {
                $("#modalRegistro").modal("hide");
                avisarYRecargar(respuesta);
            }).fail(function (xhr) {
                bootbox.alert(escaparHtml(mensajeDeError(xhr, "No se pudo guardar el registro.")));
            }).always(function () { $("#botonGuardar").prop("disabled", false); });
    });

    $("#formularioDevolucion").on("submit", function (evento) {
        evento.preventDefault();
        solicitar("devolver", $(this).serializeArray().reduce(function (datos, campo) { datos[campo.name] = campo.value; return datos; }, {}))
            .done(function (respuesta) {
                $("#modalDevolucion").modal("hide");
                avisarYRecargar(respuesta);
            }).fail(function (xhr) {
                bootbox.alert(escaparHtml(mensajeDeError(xhr, "No se pudo registrar la devolución.")));
            });
    });

    $("#tablaInventario").on("click", "[data-accion]", function () {
        var boton = $(this);
        var accion = boton.data("accion");
        var id = boton.data("id");
        var registro = registrosPorId[id];

        if (accion === "editar") {
            cargarCatalogos().then(function () { abrirFormulario(registro); });
        } else if (accion === "verACargo") {
            mostrarActivosACargo(id);
        } else if (accion === "devolver") {
            cargarCatalogos().then(function () { abrirDevolucion(registro); });
        } else if (accion === "estado") {
            confirmarYEjecutar("¿Cambiar el estado de este registro?", "estado", { id: id, activo: boton.data("activo") }, "No se pudo cambiar el estado.");
        } else if (accion === "eliminarActivo") {
            confirmarYEjecutar("¿Eliminar este activo? Solo es posible si nunca fue asignado.", "eliminarActivo", { id: id }, "No se pudo eliminar el activo.");
        }
    });

    cargarTabla();
}());
