// Configuración de la API
const API_URL = '/api';

// Estado global
let usuarioActual = null;
let tareasActuales = [];

// Elementos del DOM
const authContainer = document.getElementById('auth-container');
const appContainer = document.getElementById('app-container');
const tabRegistro = document.getElementById('tab-registro');
const tabLogin = document.getElementById('tab-login');
const formRegistro = document.getElementById('form-registro');
const formLogin = document.getElementById('form-login');
const btnLogout = document.getElementById('btn-logout');
const lblUsuario = document.getElementById('lbl-usuario');
const inputNombreRegistro = document.getElementById('nombre-registro');
const inputEmailRegistro = document.getElementById('email-registro');
const inputPasswordRegistro = document.getElementById('password-registro');
const inputEmailLogin = document.getElementById('email-login');
const inputPasswordLogin = document.getElementById('password-login');

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners para cambiar tabs
    if (tabRegistro) tabRegistro.addEventListener('click', cambiarARegistro);
    if (tabLogin) tabLogin.addEventListener('click', cambiarALogin);
    
    // Event listeners para formularios
    if (formRegistro) formRegistro.addEventListener('submit', registrarUsuario);
    if (formLogin) formLogin.addEventListener('submit', loginUsuario);
    
    // Event listener para logout
    if (btnLogout) btnLogout.addEventListener('click', logout);
    
    // Verificar si hay sesión activa
    verificarSesion();
});

// Cambiar a tab de registro
function cambiarARegistro(e) {
    e.preventDefault();
    document.getElementById('panel-registro').style.display = 'block';
    document.getElementById('panel-login').style.display = 'none';
    tabRegistro.classList.add('active');
    tabLogin.classList.remove('active');
}

// Cambiar a tab de login
function cambiarALogin(e) {
    e.preventDefault();
    document.getElementById('panel-login').style.display = 'block';
    document.getElementById('panel-registro').style.display = 'none';
    tabLogin.classList.add('active');
    tabRegistro.classList.remove('active');
}

// Registrar usuario
async function registrarUsuario(e) {
    e.preventDefault();
    
    const nombre = inputNombreRegistro?.value;
    const email = inputEmailRegistro?.value;
    const password = inputPasswordRegistro?.value;
    
    if (!nombre || !email || !password) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/registro.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nombre: nombre,
                email: email,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            alert('¡Registro exitoso! Ahora inicia sesión');
            formRegistro.reset();
            cambiarALogin({ preventDefault: () => {} });
        } else {
            alert('Error: ' + (data.error || 'No se pudo registrar'));
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        alert('Error de conexión: ' + error.message);
    }
}

// Login de usuario
async function loginUsuario(e) {
    e.preventDefault();
    
    const email = inputEmailLogin?.value;
    const password = inputPasswordLogin?.value;
    
    if (!email || !password) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Guardar datos de sesión
            localStorage.setItem('usuario', JSON.stringify(data));
            usuarioActual = data;
            
            // Cambiar interfaz
            mostrarApp();
            formLogin.reset();
        } else {
            alert('Error: ' + (data.error || 'Credenciales inválidas'));
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        alert('Error de conexión: ' + error.message);
    }
}

// Logout
function logout() {
    localStorage.removeItem('usuario');
    usuarioActual = null;
    tareasActuales = [];
    mostrarAuth();
}

// Verificar sesión activa
function verificarSesion() {
    const usuarioGuardado = localStorage.getItem('usuario');
    if (usuarioGuardado) {
        try {
            usuarioActual = JSON.parse(usuarioGuardado);
            mostrarApp();
        } catch (error) {
            console.error('Error al parsear sesión:', error);
            mostrarAuth();
        }
    } else {
        mostrarAuth();
    }
}

// Mostrar panel de autenticación
function mostrarAuth() {
    if (authContainer) authContainer.style.display = 'block';
    if (appContainer) appContainer.style.display = 'none';
}

// Mostrar panel de aplicación
function mostrarApp() {
    if (authContainer) authContainer.style.display = 'none';
    if (appContainer) appContainer.style.display = 'block';
    if (lblUsuario && usuarioActual) {
        lblUsuario.textContent = usuarioActual.nombre || usuarioActual.email;
    }
    cargarTareas();
}

// Cargar tareas
async function cargarTareas() {
    if (!usuarioActual) return;
    
    try {
        const response = await fetch(`${API_URL}/tareas.php?usuario_id=${usuarioActual.usuario_id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            tareasActuales = await response.json();
            mostrarTareas();
        }
    } catch (error) {
        console.error('Error al cargar tareas:', error);
    }
}

// Mostrar tareas en la interfaz
function mostrarTareas() {
    const listaTareas = document.getElementById('lista-tareas');
    if (!listaTareas) return;
    
    if (tareasActuales.length === 0) {
        listaTareas.innerHTML = '<p>No hay tareas. ¡Crea una nueva!</p>';
        return;
    }
    
    listaTareas.innerHTML = tareasActuales.map(tarea => `
        <div class="tarea">
            <input type="checkbox" ${tarea.completada ? 'checked' : ''} 
                   onchange="completarTarea(${tarea.id})">
            <span>${tarea.titulo}</span>
            <button onclick="eliminarTarea(${tarea.id})">Eliminar</button>
        </div>
    `).join('');
}

// Agregar nueva tarea
async function agregarTarea() {
    const inputTarea = document.getElementById('input-tarea');
    if (!inputTarea || !inputTarea.value) return;
    
    const titulo = inputTarea.value;
    
    try {
        const response = await fetch(`${API_URL}/tareas.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                usuario_id: usuarioActual.usuario_id,
                titulo: titulo
            })
        });
        
        if (response.ok) {
            inputTarea.value = '';
            cargarTareas();
        }
    } catch (error) {
        console.error('Error al agregar tarea:', error);
    }
}

// Completar tarea
async function completarTarea(id) {
    try {
        const response = await fetch(`${API_URL}/tareas.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                completada: true
            })
        });
        
        if (response.ok) {
            cargarTareas();
        }
    } catch (error) {
        console.error('Error al completar tarea:', error);
    }
}

// Eliminar tarea
async function eliminarTarea(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta tarea?')) return;
    
    try {
        const response = await fetch(`${API_URL}/tareas.php?id=${id}`, {
            method: 'DELETE'
        });
        
        if (response.ok) {
            cargarTareas();
        }
    } catch (error) {
        console.error('Error al eliminar tarea:', error);
    }
}