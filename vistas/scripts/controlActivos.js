(function () {
    "use strict";

    var entidad = window.moduloInventario;
    var api = "../ajax/controlActivos.php";
    var catalogos = { sedes: [], empleados: [], activos: [] };
    var registrosPorId = {};
    var tabla;
    var esquemas = {
        sedes: [
            { nombre: "nombre", etiqueta: "Nombre", tipo: "text", requerido: true, max: 80 },
            { nombre: "planta", etiqueta: "Planta", tipo: "text", requerido: true, max: 20 },
            { nombre: "descripcion", etiqueta: "Descripción", tipo: "textarea", requerido: true }
        ],
        empleados: [
            { nombre: "nombre", etiqueta: "Nombre", tipo: "text", requerido: true, max: 40 },
            { nombre: "apellido", etiqueta: "Apellido", tipo: "text", requerido: true, max: 40 },
            { nombre: "DNI", etiqueta: "DNI", tipo: "text", requerido: true, max: 8 },
            { nombre: "id_sede", etiqueta: "Sede", tipo: "selectSede", requerido: true }
        ],
        activos: [
            { nombre: "marca", etiqueta: "Marca", tipo: "text", requerido: true, max: 40 },
            { nombre: "tipo", etiqueta: "Tipo de activo", tipo: "text", requerido: true, max: 40 },
            { nombre: "modelo", etiqueta: "Modelo", tipo: "text", max: 40 },
            { nombre: "numero_serie", etiqueta: "Número de serie", tipo: "text", max: 100 },
            { nombre: "estado", etiqueta: "Estado", tipo: "selectEstado", requerido: true }
        ],
        asignaciones: [
            { nombre: "id_empleado", etiqueta: "Empleado", tipo: "selectEmpleado", requerido: true },
            { nombre: "id_activo", etiqueta: "Activo disponible", tipo: "selectActivo", requerido: true },
            { nombre: "fecha_entrega", etiqueta: "Fecha de entrega", tipo: "date", requerido: true },
            { nombre: "estado_equipo", etiqueta: "Estado del equipo al entregar", tipo: "selectEstado", requerido: true },
            { nombre: "observaciones", etiqueta: "Observaciones", tipo: "textarea" }
        ]
    };

    function escapeHtml(valor) {
        return String(valor == null ? "" : valor).replace(/[&<>"']/g, function (caracter) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[caracter];
        });
    }

    function solicitar(op, datos) {
        datos = datos || {};
        datos.op = op;
        datos.entidad = entidad;
        return $.ajax({ url: api, method: "POST", data: datos, dataType: "json" });
    }

    function opciones(items, valor, texto) {
        return '<option value="">Seleccionar...</option>' + items.map(function (item) {
            return '<option value="' + escapeHtml(item[valor]) + '">' + escapeHtml(item[texto]) + '</option>';
        }).join("");
    }

    function campoHtml(campo) {
        var control;
        if (campo.tipo === "selectSede") {
            control = '<select class="form-control" name="id_sede" id="campo_id_sede" required>' + opciones(catalogos.sedes, "id", "nombre") + '</select>';
        } else if (campo.tipo === "selectEmpleado") {
            control = '<select class="form-control" name="id_empleado" required><option value="">Seleccionar...</option>' + catalogos.empleados.map(function (empleado) {
                return '<option value="' + escapeHtml(empleado.id) + '">' + escapeHtml(empleado.apellido + ", " + empleado.nombre + " · DNI " + empleado.DNI) + '</option>';
            }).join("") + '</select>';
        } else if (campo.tipo === "selectActivo") {
            control = '<select class="form-control" name="id_activo" required><option value="">Seleccionar...</option>' + catalogos.activos.map(function (activo) {
                return '<option value="' + escapeHtml(activo.id) + '">' + escapeHtml(activo.marca + " " + activo.tipo + (activo.modelo ? " " + activo.modelo : "") + (activo.numero_serie ? " · " + activo.numero_serie : "")) + '</option>';
            }).join("") + '</select>';
        } else if (campo.tipo === "selectEstado") {
            var estados = ["Nuevo", "Bueno", "Regular", "Requiere reparación"];
            control = '<select class="form-control" name="' + campo.nombre + '" ' + (campo.requerido ? "required" : "") + '>' + estados.map(function (estado) {
                return '<option value="' + estado + '">' + estado + '</option>';
            }).join("") + '</select>';
        } else if (campo.tipo === "textarea") {
            control = '<textarea class="form-control" name="' + campo.nombre + '" rows="3"></textarea>';
        } else {
            control = '<input class="form-control" type="' + campo.tipo + '" name="' + campo.nombre + '"' + (campo.max ? ' maxlength="' + campo.max + '"' : "") + (campo.requerido ? " required" : "") + '>';
        }
        return '<div class="form-group col-sm-6"><label>' + escapeHtml(campo.etiqueta) + (campo.requerido ? ' <span class="text-red">*</span>' : "") + '</label>' + control + '</div>';
    }

    function abrirFormulario(registro) {
        registro = registro || null;
        $("#formRegistro")[0].reset();
        $("#registroId").val(registro ? registro.id : "");
        $("#tituloModal").text(entidad === "asignaciones" ? "Registrar entrega de un activo" : (registro ? "Editar registro" : "Nuevo registro"));
        $("#camposRegistro").html(esquemas[entidad].map(campoHtml).join(""));
        if (entidad === "asignaciones") {
            var hoy = new Date();
            var fecha = new Date(hoy.getTime() - hoy.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
            $("input[name='fecha_entrega']").val(fecha);
        }
        if (registro) {
            Object.keys(registro).forEach(function (campo) {
                $("#formRegistro [name='" + campo + "']").val(registro[campo]);
            });
        }
        $("#modalRegistro").modal("show");
    }

    function botonEstado(registro) {
        var activo = Number(registro.activo) === 1;
        return '<button class="btn btn-warning btn-xs" data-accion="editar" data-id="' + registro.id + '" title="Editar"><i class="fa fa-pencil"></i></button> ' +
            '<button class="btn btn-' + (activo ? "danger" : "primary") + ' btn-xs" data-accion="estado" data-activo="' + (activo ? 0 : 1) + '" data-id="' + registro.id + '" title="' + (activo ? "Desactivar" : "Activar") + '"><i class="fa fa-' + (activo ? "ban" : "check") + '"></i></button>';
    }

    function botonActivo(registro) {
        return '<button class="btn btn-warning btn-xs" data-accion="editar" data-id="' + registro.id + '" title="Editar"><i class="fa fa-pencil"></i></button> ' +
            '<button class="btn btn-danger btn-xs" data-accion="eliminarActivo" data-id="' + registro.id + '" title="Eliminar"' + (Number(registro.disponible) === 1 ? "" : " disabled") + '><i class="fa fa-trash"></i></button>';
    }

    function formatear(entidad, registro) {
        if (entidad === "sedes") return [botonEstado(registro), escapeHtml(registro.nombre), escapeHtml(registro.planta), escapeHtml(registro.descripcion), Number(registro.activo) ? '<span class="label label-success">Activa</span>' : '<span class="label label-default">Inactiva</span>'];
        if (entidad === "empleados") return [botonEstado(registro), escapeHtml(registro.nombre), escapeHtml(registro.apellido), escapeHtml(registro.DNI), escapeHtml(registro.sede + " · " + registro.planta), Number(registro.activo) ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>'];
        if (entidad === "activos") return [botonActivo(registro), escapeHtml(registro.marca), escapeHtml(registro.tipo), escapeHtml(registro.modelo || "—"), escapeHtml(registro.numero_serie || "—"), escapeHtml(registro.estado), Number(registro.disponible) ? '<span class="label label-success">Disponible</span>' : '<span class="label label-warning">Asignado</span>'];
        return [registro.fecha_devolucion ? "" : '<button class="btn btn-primary btn-xs" data-accion="devolver" data-id="' + registro.id + '"><i class="fa fa-undo"></i> Registrar devolución</button>', escapeHtml(registro.empleado + " · " + registro.DNI), escapeHtml(registro.sede), escapeHtml(registro.activo + (registro.numero_serie ? " · " + registro.numero_serie : "")), escapeHtml(registro.fecha_entrega), registro.fecha_devolucion ? escapeHtml(registro.fecha_devolucion) : '<span class="label label-warning">Pendiente</span>', escapeHtml(registro.estado_equipo), escapeHtml(registro.observaciones || "—")];
    }

    function cargarTabla() {
        solicitar("listar").done(function (respuesta) {
            if (!respuesta.success) return bootbox.alert(escapeHtml(respuesta.message));
            var filas = respuesta.data.map(function (registro) { return formatear(entidad, registro); });
            if (tabla) tabla.destroy();
            $("#tablaInventario tbody").empty();
            registrosPorId = {};
            respuesta.data.forEach(function (registro) { registrosPorId[registro.id] = registro; });
            tabla = $("#tablaInventario").DataTable({
                data: filas,
                pageLength: 10,
                order: [[1, "asc"]],
                dom: "Bfrtip",
                buttons: ["copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"],
                language: { emptyTable: "No hay registros para mostrar", info: "Mostrando _START_ a _END_ de _TOTAL_ registros", infoEmpty: "Mostrando 0 registros", infoFiltered: "(filtrado de _MAX_ registros)", lengthMenu: "Mostrar _MENU_ registros", loadingRecords: "Cargando...", processing: "Procesando...", search: "Buscar:", zeroRecords: "No se encontraron coincidencias", paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }, buttons: { copy: "Copiar", excel: "Excel", csv: "CSV", pdf: "PDF" } }
            });
        }).fail(function (xhr) {
            var respuesta = xhr.responseJSON || {};
            bootbox.alert(escapeHtml(respuesta.message || "No se pudieron cargar los registros."));
        });
    }

    function cargarCatalogos() {
        return solicitar("catalogos").then(function (respuesta) {
            if (!respuesta.success) throw new Error(respuesta.message);
            catalogos = respuesta.data;
        });
    }

    $("#btnAgregar").on("click", function () {
        cargarCatalogos().then(function () { abrirFormulario(); }).catch(function (error) { bootbox.alert(escapeHtml(error.message)); });
    });

    $("#formRegistro").on("submit", function (evento) {
        evento.preventDefault();
        var operacion = entidad === "asignaciones" ? "asignar" : "guardar";
        var datos = $(this).serializeArray().reduce(function (resultado, campo) { resultado[campo.name] = campo.value; return resultado; }, {});
        $("#btnGuardar").prop("disabled", true);
        solicitar(operacion, datos).done(function (respuesta) {
            if (!respuesta.success) return bootbox.alert(escapeHtml(respuesta.message));
            $("#modalRegistro").modal("hide");
            cargarTabla();
            bootbox.alert(escapeHtml(respuesta.message));
        }).fail(function (xhr) {
            var respuesta = xhr.responseJSON || {};
            bootbox.alert(escapeHtml(respuesta.message || "No se pudo guardar el registro."));
        }).always(function () { $("#btnGuardar").prop("disabled", false); });
    });

    $("#tablaInventario").on("click", "[data-accion]", function () {
        var boton = $(this);
        var accion = boton.data("accion");
        var id = boton.data("id");
        var registro = registrosPorId[id];
        if (accion === "editar") {
            cargarCatalogos().then(function () { abrirFormulario(registro); }).catch(function (error) { bootbox.alert(escapeHtml(error.message)); });
        } else if (accion === "devolver") {
            bootbox.confirm("¿Registrar la devolución de este activo con fecha de hoy?", function (confirmado) {
                if (!confirmado) return;
                solicitar("devolver", { id: id }).done(function (respuesta) { bootbox.alert(escapeHtml(respuesta.message)); cargarTabla(); }).fail(function (xhr) { bootbox.alert(escapeHtml((xhr.responseJSON || {}).message || "No se pudo registrar la devolución.")); });
            });
        } else {
            var pregunta = accion === "eliminarActivo" ? "¿Eliminar este activo? Esta acción solo está disponible si no tiene historial de asignaciones." : "¿Cambiar el estado de este registro?";
            bootbox.confirm(pregunta, function (confirmado) {
                if (!confirmado) return;
                var peticion = accion === "estado" ? solicitar("estado", { id: id, activo: boton.data("activo") }) : solicitar("eliminarActivo", { id: id });
                peticion.done(function (respuesta) { bootbox.alert(escapeHtml(respuesta.message)); cargarTabla(); }).fail(function (xhr) { bootbox.alert(escapeHtml((xhr.responseJSON || {}).message || "No se pudo completar la operación.")); });
            });
        }
    });

    cargarTabla();
}());
