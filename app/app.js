// Variables globales
let usuarioActual = null;
const API_URL = 'https://sistema-tareas-seguro-production.up.railway.app/api';

// ✅ Recuperar usuario desde localStorage al cargar la página
window.addEventListener('load', () => {
    const guardado = localStorage.getItem('usuarioActual');
    if (guardado) {
        try {
            usuarioActual = JSON.parse(guardado);
            if (usuarioActual && usuarioActual.id) {
                mostrarApp();
            }
        } catch (e) {
            localStorage.removeItem('usuarioActual');
        }
    }
});

// Función para cambiar entre Login y Registro
function cambiarAuthTab(tab) {
    const loginForm = document.getElementById('form-login');
    const registroForm = document.getElementById('form-registro');
    const tabs = document.querySelectorAll('.auth-tab');

    loginForm.classList.add('hidden');
    registroForm.classList.add('hidden');
    tabs.forEach(t => t.classList.remove('active'));

    if (tab === 'login') {
        loginForm.classList.remove('hidden');
        tabs[0].classList.add('active');
    } else {
        registroForm.classList.remove('hidden');
        tabs[1].classList.add('active');
    }

    document.getElementById('mensaje-auth').innerHTML = '';
}

// Funciones de autenticación
async function login() {
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    if (!email || !password) {
        mostrarMensaje('mensaje-auth', 'Email y contraseña requeridos', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include', // ✅ Enviar cookies de sesión
            body: JSON.stringify({ email, password })
        });

        // ✅ Verificar que la respuesta sea JSON válido antes de parsear
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Respuesta no es JSON:', text);
            mostrarMensaje('mensaje-auth', 'Error del servidor (respuesta inválida)', 'error');
            return;
        }

        console.log('Respuesta login:', data); // ✅ Para debug

        if (response.ok && data.usuario && data.usuario.id) {
            usuarioActual = data.usuario;
            // ✅ Guardar en localStorage para persistencia
            localStorage.setItem('usuarioActual', JSON.stringify(usuarioActual));
            mostrarMensaje('mensaje-auth', 'Login exitoso', 'exito');
            mostrarApp();
        } else {
            mostrarMensaje('mensaje-auth', data.error || 'Error al iniciar sesión', 'error');
        }
    } catch (error) {
        console.error('Error login:', error);
        mostrarMensaje('mensaje-auth', 'Error de conexión: ' + error.message, 'error');
    }
}

async function registrar() {
    const nombre = document.getElementById('registro-nombre').value;
    const email = document.getElementById('registro-email').value;
    const password = document.getElementById('registro-password').value;

    if (!nombre || !email || !password) {
        mostrarMensaje('mensaje-auth', 'Todos los campos son requeridos', 'error');
        return;
    }

    if (password.length < 6) {
        mostrarMensaje('mensaje-auth', 'La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/registro.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, email, password })
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            mostrarMensaje('mensaje-auth', 'Error del servidor (respuesta inválida)', 'error');
            return;
        }

        if (response.ok) {
            mostrarMensaje('mensaje-auth', '✅ ¡Cuenta creada! Ahora puedes iniciar sesión.', 'exito');
            document.getElementById('registro-nombre').value = '';
            document.getElementById('registro-email').value = '';
            document.getElementById('registro-password').value = '';
            setTimeout(() => {
                cambiarAuthTab('login');
                document.getElementById('login-email').value = email;
            }, 2000);
        } else {
            mostrarMensaje('mensaje-auth', data.error || 'Error al registrar', 'error');
        }
    } catch (error) {
        mostrarMensaje('mensaje-auth', 'Error de conexión: ' + error.message, 'error');
    }
}

function logout() {
    usuarioActual = null;
    // ✅ Limpiar localStorage al cerrar sesión
    localStorage.removeItem('usuarioActual');
    document.getElementById('auth-container').classList.remove('hidden');
    document.getElementById('app-container').classList.add('hidden');
}

function mostrarApp() {
    // ✅ Verificar que usuarioActual existe antes de usarlo
    if (!usuarioActual || !usuarioActual.id) {
        console.error('usuarioActual no válido:', usuarioActual);
        return;
    }
    document.getElementById('auth-container').classList.add('hidden');
    document.getElementById('app-container').classList.remove('hidden');
    document.getElementById('user-name').textContent = usuarioActual.nombre;
    cargarTareas();
}

// Funciones de tareas
async function cargarTareas() {
    // ✅ Guard: verificar que el usuario esté logueado
    if (!usuarioActual || !usuarioActual.id) {
        mostrarMensaje('mensaje-tareas', 'Sesión expirada. Por favor inicia sesión nuevamente.', 'error');
        logout();
        return;
    }

    try {
        const response = await fetch(`${API_URL}/tareas.php?usuario_id=${usuarioActual.id}`, {
            credentials: 'include'
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Respuesta tareas no es JSON:', text);
            mostrarMensaje('mensaje-tareas', 'Error del servidor al cargar tareas', 'error');
            return;
        }

        const listaTareas = document.getElementById('lista-tareas');
        listaTareas.innerHTML = '';

        if (data.tareas && data.tareas.length > 0) {
            data.tareas.forEach(tarea => {
                listaTareas.innerHTML += `
                    <div class="task-item ${tarea.prioridad}">
                        <div class="task-header">
                            <div>
                                <strong>${tarea.titulo}</strong>
                                <span class="badge ${tarea.estado}">${tarea.estado.replace('_', ' ')}</span>
                            </div>
                            <div class="task-actions" style="display: flex; flex-direction: column; gap: 5px;">
                                <button onclick="eliminarTarea(${tarea.id})" class="danger">🗑️ Eliminar</button>
                                <button onclick="editarTarea(${tarea.id}, '${tarea.titulo.replace(/'/g, "\\'")}', '${(tarea.descripcion || '').replace(/'/g, "\\'")}', '${tarea.prioridad}', '${tarea.estado}')" class="secondary">✏️ Editar</button>
                            </div>
                        </div>
                        <p>${tarea.descripcion || 'Sin descripción'}</p>
                        <small>Prioridad: ${tarea.prioridad} | Creada: ${tarea.fecha_creacion}</small>
                    </div>
                `;
            });
        } else {
            listaTareas.innerHTML = '<p>No tienes tareas aún. ¡Crea tu primera tarea!</p>';
        }
    } catch (error) {
        mostrarMensaje('mensaje-tareas', 'Error al cargar tareas: ' + error.message, 'error');
    }
}

async function crearTarea() {
    // ✅ Guard: verificar que el usuario esté logueado
    if (!usuarioActual || !usuarioActual.id) {
        mostrarMensaje('mensaje-tareas', 'Error: debes iniciar sesión primero', 'error');
        logout();
        return;
    }

    const titulo = document.getElementById('tarea-titulo').value;
    const descripcion = document.getElementById('tarea-descripcion').value;
    const prioridad = document.getElementById('tarea-prioridad').value;
    const estado = document.getElementById('tarea-estado').value;

    if (!titulo) {
        mostrarMensaje('mensaje-tareas', 'El título es requerido', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/tareas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                usuario_id: usuarioActual.id,
                titulo,
                descripcion,
                prioridad,
                estado
            })
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Respuesta crear tarea no es JSON:', text);
            mostrarMensaje('mensaje-tareas', 'Error del servidor (respuesta inválida)', 'error');
            return;
        }

        if (response.ok) {
            mostrarMensaje('mensaje-tareas', '✅ Tarea creada exitosamente', 'exito');
            document.getElementById('tarea-titulo').value = '';
            document.getElementById('tarea-descripcion').value = '';
            cargarTareas();
        } else {
            mostrarMensaje('mensaje-tareas', data.error || 'Error al crear tarea', 'error');
        }
    } catch (error) {
        mostrarMensaje('mensaje-tareas', 'Error de conexión: ' + error.message, 'error');
    }
}

async function eliminarTarea(id) {
    if (!usuarioActual || !usuarioActual.id) {
        mostrarMensaje('mensaje-tareas', 'Error: sesión expirada', 'error');
        logout();
        return;
    }

    if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;

    try {
        const response = await fetch(`${API_URL}/tareas.php`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                id: id,
                usuario_id: usuarioActual.id
            })
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            mostrarMensaje('mensaje-tareas', 'Error del servidor', 'error');
            return;
        }

        if (response.ok) {
            mostrarMensaje('mensaje-tareas', 'Tarea eliminada', 'exito');
            cargarTareas();
        } else {
            mostrarMensaje('mensaje-tareas', data.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        mostrarMensaje('mensaje-tareas', 'Error de conexión: ' + error.message, 'error');
    }
}

// Variable para guardar el ID de la tarea que se está editando
let tareaEditandoId = null;

function editarTarea(id, titulo, descripcion, prioridad, estado) {
    tareaEditandoId = id;
    document.getElementById('editar-titulo').value = titulo;
    document.getElementById('editar-descripcion').value = descripcion;
    document.getElementById('editar-prioridad').value = prioridad;
    document.getElementById('editar-estado').value = estado;
    document.getElementById('modal-editar').classList.add('show');
}

function cerrarModal() {
    document.getElementById('modal-editar').classList.remove('show');
    tareaEditandoId = null;
}

async function guardarEdicion() {
    if (!tareaEditandoId) return;

    if (!usuarioActual || !usuarioActual.id) {
        alert('Sesión expirada. Por favor inicia sesión nuevamente.');
        logout();
        return;
    }

    const titulo = document.getElementById('editar-titulo').value;
    const descripcion = document.getElementById('editar-descripcion').value;
    const prioridad = document.getElementById('editar-prioridad').value;
    const estado = document.getElementById('editar-estado').value;

    if (!titulo) {
        alert('El título es requerido');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/tareas.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                id: tareaEditandoId,
                usuario_id: usuarioActual.id,
                titulo,
                descripcion,
                prioridad,
                estado
            })
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert('Error del servidor (respuesta inválida)');
            return;
        }

        if (response.ok) {
            mostrarMensaje('mensaje-tareas', '✅ Tarea actualizada exitosamente', 'exito');
            cerrarModal();
            cargarTareas();
        } else {
            alert(data.error || 'Error al actualizar');
        }
    } catch (error) {
        alert('Error de conexión: ' + error.message);
    }
}

// Funciones de estadísticas
async function cargarEstadisticas() {
    if (!usuarioActual || !usuarioActual.id) return;

    try {
        const response = await fetch(`${API_URL}/estadisticas.php?usuario_id=${usuarioActual.id}`, {
            credentials: 'include'
        });
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error parseando estadísticas:', text);
            return;
        }

        if (response.ok) {
            const statsGrid = document.getElementById('stats-grid');
            statsGrid.innerHTML = `
                <div class="stat-card">
                    <div>Total de Tareas</div>
                    <div class="stat-value">${data.estadisticas.total_tareas}</div>
                </div>
                <div class="stat-card">
                    <div>Pendientes</div>
                    <div class="stat-value">${data.estadisticas.pendientes}</div>
                </div>
                <div class="stat-card">
                    <div>En Progreso</div>
                    <div class="stat-value">${data.estadisticas.en_progreso}</div>
                </div>
                <div class="stat-card">
                    <div>Completadas</div>
                    <div class="stat-value">${data.estadisticas.completadas}</div>
                </div>
                <div class="stat-card">
                    <div>% Completadas</div>
                    <div class="stat-value">${data.estadisticas.porcentaje_completadas}%</div>
                </div>
            `;

            const listaActividades = document.getElementById('lista-actividades');
            listaActividades.innerHTML = '';
            data.actividades_recientes.forEach(act => {
                listaActividades.innerHTML += `
                    <div class="task-item">
                        <strong>${act.accion}</strong>: ${act.detalles || 'Sin detalles'}
                        <br><small>${act.fecha}</small>
                    </div>
                `;
            });
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// Función para consultar clima
async function consultarClima() {
    const ciudad = document.getElementById('ciudad-input').value;

    if (!ciudad) {
        alert('Ingresa una ciudad');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/clima.php?ciudad=${encodeURIComponent(ciudad)}`);
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            document.getElementById('resultado-clima').innerHTML = `<div class="mensaje error">Error del servidor</div>`;
            return;
        }

        if (response.ok) {
            document.getElementById('resultado-clima').innerHTML = `
                <div class="weather-card">
                    <h3>🌍 ${data.ciudad}</h3>
                    <div class="stat-value">${data.temperatura}</div>
                    <p><strong>Descripción:</strong> ${data.descripcion}</p>
                    <p><strong>Humedad:</strong> ${data.humedad}</p>
                    <p><strong>Viento:</strong> ${data.velocidad_viento}</p>
                    <p><small>Última actualización: ${data.fecha_hora}</small></p>
                </div>
            `;
        } else {
            document.getElementById('resultado-clima').innerHTML = `<div class="mensaje error">${data.error}</div>`;
        }
    } catch (error) {
        document.getElementById('resultado-clima').innerHTML = `<div class="mensaje error">Error de conexión: ${error.message}</div>`;
    }
}

// Función para cambiar tabs
function cambiarTab(tab) {
    document.getElementById('tab-tareas').classList.add('hidden');
    document.getElementById('tab-estadisticas').classList.add('hidden');
    document.getElementById('tab-clima').classList.add('hidden');

    document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));

    document.getElementById(`tab-${tab}`).classList.remove('hidden');
    event.target.classList.add('active');

    if (tab === 'tareas') {
        cargarTareas();
    } else if (tab === 'estadisticas') {
        cargarEstadisticas();
    }
}

// Función auxiliar para mostrar mensajes
function mostrarMensaje(elementoId, mensaje, tipo) {
    const elemento = document.getElementById(elementoId);
    if (!elemento) return;
    elemento.innerHTML = `<div class="mensaje ${tipo}">${mensaje}</div>`;
    setTimeout(() => {
        elemento.innerHTML = '';
    }, 5000);
}