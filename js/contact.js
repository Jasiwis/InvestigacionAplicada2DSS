// Estado de la aplicación
const state = {
    isSubmitting: false
};

// Cuando el documento esté listo
document.addEventListener('DOMContentLoaded', function() {
    initEvents();
});

// Inicializar eventos
function initEvents() {
    document.getElementById('reservation-form').addEventListener('submit', handleSubmit);
}

// Manejar envío del formulario
async function handleSubmit(e) {
    e.preventDefault();

    if (state.isSubmitting) return;

    const formData = getFormData();

    if (!validateForm(formData)) {
        return;
    }

    try {
        state.isSubmitting = true;
        showLoading(true);

        const response = await fetch('http://localhost:8081/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Error al enviar la solicitud');
        }

        resetForm();
        showSuccess('Solicitud enviada correctamente');

    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
    } finally {
        showLoading(false);
        state.isSubmitting = false;
    }
}

// Obtener datos del formulario
function getFormData() {
    return {
        nombre: document.getElementById('nombre').value.trim(),
        correo: document.getElementById('correo').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        fecha_llegada: document.getElementById('fecha_llegada').value,
        fecha_salida: document.getElementById('fecha_salida').value,
        tipo: document.getElementById('tipo').value,
        huespedes: parseInt(document.getElementById('huespedes').value),
        mensaje: document.getElementById('mensaje').value.trim()
    };
}

// Validar formulario
function validateForm(data) {
    if (!data.nombre || !data.correo || !data.fecha_llegada || !data.fecha_salida || !data.tipo || isNaN(data.huespedes)) {
        showError('Por favor complete todos los campos obligatorios');
        return false;
    }

    if (new Date(data.fecha_llegada) > new Date(data.fecha_salida)) {
        showError('La fecha de llegada no puede ser posterior a la fecha de salida');
        return false;
    }

    return true;
}

// Resetear formulario
function resetForm() {
    document.getElementById('reservation-form').reset();
}

// Mostrar/ocultar carga
function showLoading(show) {
    let loader = document.getElementById('loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'loader';
        loader.style.position = 'fixed';
        loader.style.top = '0';
        loader.style.left = '0';
        loader.style.width = '100%';
        loader.style.height = '100%';
        loader.style.backgroundColor = 'rgba(0,0,0,0.5)';
        loader.style.display = 'none';
        loader.style.zIndex = '1000';
        loader.innerHTML = '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white;">Enviando...</div>';
        document.body.appendChild(loader);
    }
    loader.style.display = show ? 'block' : 'none';
}

// Mostrar mensaje de éxito
function showSuccess(message) {
    const notification = document.createElement('div');
    notification.className = 'notification success';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Mostrar mensaje de error
function showError(message) {
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
