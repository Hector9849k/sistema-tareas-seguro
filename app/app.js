// Variables globales
let usuarioActual = null;
const API_URL = '/api';

// ==================== AUTENTICACIÓN ====================

function cambiarAuthTab(tab) {
    const loginForm = document.getElementById('form-login');
    const registroForm = document.getElementById('form-registro');
    const tabs = document.querySelectorAll('.auth-tab');

    // Ocultar ambos formularios
    loginForm.classList.add('hidden');
    registroForm.classList.add('hidden');

    // Remover clase active
    tabs.forEach(t => t.classList.remove('active'));

    // Mostrar el correcto
    if (tab === 'login') {
        loginForm.classList.remove('hidden');
        tabs[0].classList.add('active');
    } else {
        registroForm.classList.remove('hidden');
        tabs[1].classList.add('active');
    }

    // Limpiar mensajes
    document.getElementById('mensaje-auth').innerHTML = '';
}

async function login(e) {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const response = await fetch(`${API_URL}/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            usuarioActual = data;
            localStorage.setItem('usuario', JSON.stringify(data));
            mostrarMensaje('mensaje-auth', '✅ Login exitoso', 'exito');
            setTimeout(() => mostrarApp(), 1500);
        } else {
            mostrarMensaje('mensaje-auth', data.error || 'Error al iniciar sesión', 'error');
        }
    } catch (error) {
        mostrarMensaje('mensaje-auth', 'Error de conexión: ' + error.message, 'error');
    }
}

async function registrar(e) {
    e.preventDefault();
    
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

        const data = await response.json();

        if (response.ok) {
            mostrarMensaje('mensaje-auth', '✅ ¡Cuenta creada! Ahora puedes iniciar sesión.', 'exito');
            // Limpiar campos
            document.getElementById('registro-nombre').value = '';
            document.getElementById('registro-email').value = '';
            document.getElementById('registro-password').value = '';
            
            // Cambiar a login después de 2 segundos
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
    localStorage.removeItem('usuario');
    document.getElementById('auth-container').classList.remove('hidden');
    document.getElementById('app-container').classList.add('hidden');
    document.getElementById('login-email').value = '';
    document.getElementById('login-password').value = '';
}

function mostrarApp() {
    document.getElementById('auth-container').classList.add('hidden');
    document.getElementById('app-container').classList.remove('hidden');
    document.getElementById('user-name').textContent = usuarioActual.nombre || usuarioActual.email;
    cargarTareas();
}

// ==================== TAREAS ====================

async function cargarTareas() {
    try {
        const response = await fetch(`${API_URL}/tareas.php?usuario_id=${usuarioActual.usuario_id}`);
        const tareas = await response.json();

        const listaTareas = document.getElementById('lista-tareas');
        listaTareas.innerHTML = '';

        if (Array.isArray(tareas) && tareas.length > 0) {
            tareas.forEach(tarea => {
                listaTareas.innerHTML += `
                    <div class="task-item">
                        <div class="task-header">
                            <div>
                                <strong>${tarea.titulo}</strong>
                                <p>${tarea.descripcion || 'Sin descripción'}</p>
                                <small>Creada: ${tarea.fecha_creacion}</small>
                            </div>
                            <div class="task-actions">
                                <button onclick="eliminarTarea(${tarea.id})" class="btn-primary" style="width: 100px;">🗑️ Eliminar</button>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            listaTareas.innerHTML = '<p style="text-align: center; color: #999;">No tienes tareas aún. ¡Crea tu primera tarea!</p>';
        }
    } catch (error) {
        console.error('Error al cargar tareas:', error);
        document.getElementById('lista-tareas').innerHTML = '<p style="color: red;">Error al cargar tareas</p>';
    }
}

async function crearTarea() {
    const titulo = document.getElementById('tarea-titulo').value;
    const descripcion = document.getElementById('tarea-descripcion').value;

    if (!titulo) {
        mostrarMensaje('mensaje-tareas', 'El título es requerido', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/tareas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: usuarioActual.usuario_id,
                titulo: titulo,
                descripcion: descripcion
            })
        });

        const data = await response.json();

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
    if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;

    try {
        const response = await fetch(`${API_URL}/tareas.php?id=${id}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (response.ok) {
            mostrarMensaje('mensaje-tareas', '✅ Tarea eliminada', 'exito');
            cargarTareas();
        } else {
            mostrarMensaje('mensaje-tareas', data.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        mostrarMensaje('mensaje-tareas', 'Error de conexión: ' + error.message, 'error');
    }
}

// ==================== ESTADÍSTICAS ====================

async function cargarEstadisticas() {
    try {
        const response = await fetch(`${API_URL}/tareas.php?usuario_id=${usuarioActual.usuario_id}`);
        const tareas = await response.json();

        if (!Array.isArray(tareas)) tareas = [];

        const total = tareas.length;
        const completadas = tareas.filter(t => t.completada == 1).length;
        const porcentaje = total > 0 ? Math.round((completadas / total) * 100) : 0;

        const statsGrid = document.getElementById('stats-grid');
        statsGrid.innerHTML = `
            <div class="stat-card">
                <div>Total de Tareas</div>
                <div class="stat-value">${total}</div>
            </div>
            <div class="stat-card">
                <div>Completadas</div>
                <div class="stat-value">${completadas}</div>
            </div>
            <div class="stat-card">
                <div>Pendientes</div>
                <div class="stat-value">${total - completadas}</div>
            </div>
            <div class="stat-card">
                <div>% Completadas</div>
                <div class="stat-value">${porcentaje}%</div>
            </div>
        `;
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// ==================== TABS ====================

function cambiarTab(tab) {
    // Ocultar todos
    document.getElementById('tab-tareas').classList.add('hidden');
    document.getElementById('tab-estadisticas').classList.add('hidden');

    // Desactivar botones
    document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));

    // Mostrar seleccionado
    document.getElementById(`tab-${tab}`).classList.remove('hidden');
    
    if (event && event.target) {
        event.target.classList.add('active');
    }

    // Cargar datos
    if (tab === 'tareas') {
        cargarTareas();
    } else if (tab === 'estadisticas') {
        cargarEstadisticas();
    }
}

// ==================== UTILIDADES ====================

function mostrarMensaje(elementoId, mensaje, tipo) {
    const elemento = document.getElementById(elementoId);
    elemento.innerHTML = `<div class="mensaje ${tipo}">${mensaje}</div>`;
    setTimeout(() => {
        elemento.innerHTML = '';
    }, 5000);
}

// ==================== INICIALIZACIÓN ====================

document.addEventListener('DOMContentLoaded', function() {
    const usuarioGuardado = localStorage.getItem('usuario');
    if (usuarioGuardado) {
        try {
            usuarioActual = JSON.parse(usuarioGuardado);
            mostrarApp();
        } catch (error) {
            console.error('Error al recuperar usuario:', error);
            localStorage.removeItem('usuario');
        }
    }
});
