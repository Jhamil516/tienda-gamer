// Funcion para mostrar/ocultar contrasena
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Funcion para mostrar alerta de confirmacion personalizada
function confirmarAccion(mensaje) {
    return confirm(mensaje);
}

// Función para eliminar con confirmación
function confirmarEliminar(id, tipo) {
    if (confirm(`¿Estás seguro de que quieres eliminar este ${tipo}?`)) {
        window.location.href = `?delete=${id}`;
    }
}

// Validar formulario de login
function validarLogin() {
    const correo = document.getElementById('correo').value.trim();
    const contrasena = document.getElementById('contrasena').value;

    if (correo === '' || contrasena === '') {
        alert('Por favor completa todos los campos');
        return false;
    }

    if (!validarCorreo(correo)) {
        alert('Ingresa un correo válido');
        return false;
    }

    return true;
}

// Validar correo
function validarCorreo(correo) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(correo);
}

// Validar formulario de registro
function validarRegistro() {
    const nombre = document.getElementById('nombre').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const contrasena = document.getElementById('contrasena').value;
    const confirmar = document.getElementById('confirmar_contrasena').value;

    if (nombre === '' || correo === '' || contrasena === '' || confirmar === '') {
        alert('Por favor completa todos los campos');
        return false;
    }

    if (nombre.length < 3) {
        alert('El nombre debe tener al menos 3 caracteres');
        return false;
    }

    if (!validarCorreo(correo)) {
        alert('Ingresa un correo válido');
        return false;
    }

    if (contrasena.length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }

    if (contrasena !== confirmar) {
        alert('Las contraseñas no coinciden');
        return false;
    }

    return true;
}

// Incrementar cantidad
function incrementarCantidad(id) {
    const input = document.getElementById(`cantidad_${id}`);
    if (input) {
        input.value = parseInt(input.value) + 1;
        actualizarTotal();
    }
}

// Decrementar cantidad
function decrementarCantidad(id) {
    const input = document.getElementById(`cantidad_${id}`);
    if (input && parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        actualizarTotal();
    }
}

// Actualizar total del carrito (opcional, se ejecuta en cliente)
function actualizarTotal() {
    console.log('Total actualizado');
}

// Mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.role = 'alert';
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alerta, container.firstChild);

    setTimeout(() => {
        alerta.remove();
    }, 3000);
}

// Toast notifications
function mostrarToast(mensaje, tipo = 'success', duracion = 4000) {
    const container = document.querySelector('.toast-container') || crear_toast_container();

    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;

    const iconos = {
        'success': 'bi-check-circle',
        'error': 'bi-exclamation-circle',
        'warning': 'bi-exclamation-triangle',
        'info': 'bi-info-circle'
    };

    toast.innerHTML = `
        <div class="toast-header">
            <div class="toast-icon">
                <i class="bi ${iconos[tipo]}"></i>
            </div>
        </div>
        <div class="toast-body">
            <p>${mensaje}</p>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duracion);
}

function crear_toast_container() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// Mostrar alertas como toasts
document.addEventListener('DOMContentLoaded', function() {
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        const texto = alerta.innerText.trim();
        if (texto) {
            let tipo = 'info';
            if (alerta.classList.contains('alert-success')) tipo = 'success';
            else if (alerta.classList.contains('alert-danger')) tipo = 'error';
            else if (alerta.classList.contains('alert-warning')) tipo = 'warning';

            mostrarToast(texto, tipo, 5000);
        }
    });
});


// Mostrar preview de múltiples imágenes
function mostrarPreviewMultiple(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('preview-imagenes');

    if (!previewContainer) return;

    previewContainer.innerHTML = '';

    if (files.length === 0) {
        previewContainer.innerHTML = '<p class="text-muted">No hay imágenes seleccionadas</p>';
        return;
    }

    // Contar imágenes existentes
    const imagenesExistentes = document.querySelectorAll('.card-img-top').length;
    const totalImagenes = imagenesExistentes + files.length;

    if (files.length > 5) {
        previewContainer.innerHTML = '<div class="alert alert-danger">⚠️ Máximo 5 imágenes permitidas en esta tanda. Has seleccionado ' + files.length + '</div>';
        return;
    }

    if (totalImagenes > 5 && imagenesExistentes > 0) {
        previewContainer.innerHTML = '<div class="alert alert-warning">⚠️ Imágenes existentes: ' + imagenesExistentes + '. Máximo total: 5. Puedes agregar ' + (5 - imagenesExistentes) + ' más.</div>';
    }

    previewContainer.innerHTML = '<div class="row g-2"><p class="text-success">✅ ' + files.length + ' imagen(es) nueva(s):</p></div><div class="row g-2" id="preview-row"></div>';
    const previewRow = document.getElementById('preview-row');

    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        // Validar tipo
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!tiposPermitidos.includes(file.type)) {
            const error = document.createElement('div');
            error.className = 'alert alert-warning';
            error.innerHTML = '⚠️ ' + file.name + ' no es un formato válido (JPG, PNG, GIF, WEBP)';
            previewContainer.appendChild(error);
            continue;
        }

        // Validar tamaño
        if (file.size > 5 * 1024 * 1024) {
            const error = document.createElement('div');
            error.className = 'alert alert-warning';
            error.innerHTML = '⚠️ ' + file.name + ' es mayor a 5MB';
            previewContainer.appendChild(error);
            continue;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-4 col-6';
            col.innerHTML = '<div class="card">' +
                '<img src="' + e.target.result + '" class="card-img-top" style="height: 120px; object-fit: cover;" alt="Preview">' +
                '<div class="card-body p-2">' +
                '<small class="text-muted d-block text-truncate">' + file.name + '</small>' +
                '<small class="text-muted">' + (file.size / 1024 / 1024).toFixed(2) + ' MB</small>' +
                (i === 0 ? '<div class="badge bg-primary mt-1">Principal</div>' : '') +
                '</div>' +
                '</div>';
            previewRow.appendChild(col);
        };

        reader.readAsDataURL(file);
    }
}
