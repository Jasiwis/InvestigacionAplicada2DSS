// Estado de la aplicación
const state = {
    isEditing: false,
    currentId: null
};

// Cuando el documento esté listo
document.addEventListener('DOMContentLoaded', function() {
    initEvents();
    loadAlojamientos();
});

// Inicializar eventos
function initEvents() {
    document.getElementById('alojamientoForm').addEventListener('submit', handleSubmit);
    document.getElementById('cancelar').addEventListener('click', resetForm);
    document.getElementById('imagen').addEventListener('input', function() {
        showImagePreview(this.value);
    });
}

// Mostrar previsualización de imagen
function showImagePreview(imageUrl) {
    const preview = document.getElementById('imagenPreview');
    if (!imageUrl) {
        preview.innerHTML = '';
        return;
    }
    
    // Verificar si es una URL válida
    try {
        new URL(imageUrl);
        preview.innerHTML = `<img src="${imageUrl}" style="max-width: 200px; max-height: 150px;" onerror="this.parentElement.innerHTML='<span class=error>No se pudo cargar la imagen</span>'">`;
    } catch {
        preview.innerHTML = '<span class="error">URL no válida</span>';
    }
}

// Cargar alojamientos
async function loadAlojamientos() {
try {
showLoading(true);
const response = await fetch('http://localhost:8081/api/alojamientos');

if (!response.ok) {
    throw new Error('Error al cargar datos');
}

const data = await response.json();
renderCards(data); 

} catch (error) {
console.error('Error:', error);
showError('Error al cargar los alojamientos');
} finally {
showLoading(false);
}
}

// Mostrar cards 
function renderCards(alojamientos) {
    const container = document.getElementById('alojamientosContainer');
    container.innerHTML = '';

    if (alojamientos.length === 0) {
        container.innerHTML = '<p class="no-data">No hay alojamientos registrados</p>';
        return;
    }

    alojamientos.forEach(alojamiento => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <div class="card-header">
                <h3>${alojamiento.nombre}</h3>
                ${alojamiento.imagen ? `<img src="${alojamiento.imagen}" alt="${alojamiento.nombre}" class="card-image" onerror="this.style.display='none'">` : ''}
            </div>
            <div class="card-body">
                <div class="card-details">
                    <p><strong>Tipo:</strong> ${alojamiento.tipo}</p>
                    <p><strong>Capacidad:</strong> ${alojamiento.capacidad} personas</p>
                    <p><strong>Precio por noche:</strong> $${(alojamiento.precio_por_noche || 0).toFixed(2)}</p>
                    <p class="${alojamiento.disponible ? 'available' : 'not-available'}">
                        <strong>Disponibilidad:</strong> ${alojamiento.disponible ? 'Disponible' : 'No disponible'}
                    </p>
                </div>
            </div>
            <div class="card-actions">
                <button onclick="editAlojamiento('${alojamiento.id}')" class="btn btn-edit">
                    Editar
                </button>
                <button onclick="deleteAlojamiento('${alojamiento.id}')" class="btn btn-delete">
                    Eliminar
                </button>
            </div>
        `;
        container.appendChild(card);
    });
}

// Manejar envío del formulario
async function handleSubmit(e) {
    e.preventDefault();
    
    const formData = getFormData();
    
    if (!validateForm(formData)) {
        return;
    }
    
    try {
        showLoading(true);
        const url = state.isEditing 
            ? `http://localhost:8081/api/alojamientos/${state.currentId}`
            : 'http://localhost:8081/api/alojamientos';
            
        const method = state.isEditing ? 'PUT' : 'POST';
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Error al guardar');
        }
        
        resetForm();
        await loadAlojamientos();
        showSuccess(state.isEditing ? 'Alojamiento actualizado correctamente' : 'Alojamiento creado correctamente');
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
    } finally {
        showLoading(false);
    }
}

// Editar alojamiento
async function editAlojamiento(id) {
    try {
        showLoading(true);
        const response = await fetch(`http://localhost:8081/api/alojamientos/${id}`);
        
        if (!response.ok) {
            throw new Error('Error al cargar datos para edición');
        }
        
        const alojamiento = await response.json();
        
        // Llenar el formulario con los datos
        document.getElementById('alojamientoId').value = alojamiento.id;
        document.getElementById('tipo').value = alojamiento.tipo;
        document.getElementById('nombre').value = alojamiento.nombre;
        document.getElementById('capacidad').value = alojamiento.capacidad;
        document.getElementById('precio').value = alojamiento.precio_por_noche;
        document.getElementById('disponible').checked = alojamiento.disponible;
        document.getElementById('imagen').value = alojamiento.imagen || '';
        showImagePreview(alojamiento.imagen);
        
        // Actualizar estado
        state.isEditing = true;
        state.currentId = id;
        
        // Hacer scroll al formulario
        document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
        
    } catch (error) {
        console.error('Error:', error);
        showError('Error al cargar el alojamiento para edición');
    } finally {
        showLoading(false);
    }
}

// Eliminar alojamiento
async function deleteAlojamiento(id) {
    if (!confirm('¿Está seguro que desea eliminar este alojamiento?')) {
        return;
    }
    
    try {
        showLoading(true);
        const response = await fetch(`http://localhost:8081/api/alojamientos/${id}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) {
            throw new Error('Error al eliminar');
        }
        
        await loadAlojamientos();
        showSuccess('Alojamiento eliminado correctamente');
        
    } catch (error) {
        console.error('Error:', error);
        showError('Error al eliminar el alojamiento');
    } finally {
        showLoading(false);
    }
}

// Obtener datos del formulario
function getFormData() {
    return {
        id: state.isEditing ? state.currentId : undefined,
        tipo: document.getElementById('tipo').value,
        nombre: document.getElementById('nombre').value,
        capacidad: parseInt(document.getElementById('capacidad').value),
        precio_por_noche: parseFloat(document.getElementById('precio').value),
        disponible: document.getElementById('disponible').checked,
        imagen: document.getElementById('imagen').value || null
    };
}

// Validar formulario
function validateForm(data) {
    if (!data.tipo || !data.nombre || isNaN(data.capacidad) || isNaN(data.precio_por_noche)) {
        showError('Por favor complete todos los campos correctamente');
        return false;
    }
    return true;
}

// Resetear formulario
function resetForm() {
    document.getElementById('alojamientoForm').reset();
    document.getElementById('imagenPreview').innerHTML = ''; 
    state.isEditing = false;
    state.currentId = null;
}

// Mostrar/ocultar carga
function showLoading(show) {
    // Implementar lógica de loading
    const loader = document.getElementById('loader') || createLoader();
    loader.style.display = show ? 'block' : 'none';
    
    function createLoader() {
        const loader = document.createElement('div');
        loader.id = 'loader';
        loader.style.position = 'fixed';
        loader.style.top = '0';
        loader.style.left = '0';
        loader.style.width = '100%';
        loader.style.height = '100%';
        loader.style.backgroundColor = 'rgba(0,0,0,0.5)';
        loader.style.display = 'none';
        loader.style.zIndex = '1000';
        loader.innerHTML = '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white;">Cargando...</div>';
        document.body.appendChild(loader);
        return loader;
    }
}

// Mostrar mensaje de éxito
function showSuccess(message) {
    // Reemplazar con un sistema de notificaciones mejor
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
    // Reemplazar con un sistema de notificaciones mejor
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// funciones globales
window.editAlojamiento = editAlojamiento;
window.deleteAlojamiento = deleteAlojamiento;