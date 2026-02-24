# 🚀 Sistema de Gestión de Tareas

## ⚡ INICIO RÁPIDO (3 pasos)

### 1️⃣ Instalar Docker Desktop
Descarga e instala desde: https://www.docker.com/products/docker-desktop

### 2️⃣ Iniciar el proyecto
Abre la terminal/cmd en esta carpeta y ejecuta:
```bash
docker-compose up -d
```

### 3️⃣ Abrir en el navegador
Ve a: http://localhost:8080

**Usuario de prueba:**
- Email: admin@test.com
- Password: admin123

---

## 📋 ¿Qué hace esta aplicación?

✅ **Registro y Login** de usuarios
✅ **CRUD de Tareas** (Crear, Leer, Actualizar, Eliminar)
✅ **Consulta de Clima** (API externa en tiempo real)
✅ **Estadísticas** y reportes de productividad

---

## 📚 Más información

Lee el archivo `GUIA_INSTALACION.txt` para instrucciones detalladas.

---

## 🛑 Detener el proyecto

```bash
docker-compose stop
```

## ▶️ Iniciar de nuevo

```bash
docker-compose start
```

---

## 📱 URLs útiles

- **Aplicación**: http://localhost:8080
- **PhpMyAdmin**: http://localhost:8081
  - Usuario: root
  - Password: root123

---

## 🎯 Estructura del proyecto

```
proyecto-web-completo/
├── docker-compose.yml       # Configuración de Docker
├── init.sql                 # Base de datos inicial
├── README.md               # Este archivo
├── GUIA_INSTALACION.txt    # Guía detallada
└── app/
    ├── index.html          # Interfaz principal
    ├── app.js              # Lógica frontend
    ├── config/
    │   └── database.php    # Configuración BD
    └── api/
        ├── registro.php    # API Registro
        ├── login.php       # API Login
        ├── tareas.php      # API CRUD Tareas
        ├── estadisticas.php # API Estadísticas
        └── clima.php       # API Clima
```

---

## ✨ Características técnicas

- **Backend**: PHP 8.2 con PDO
- **Base de datos**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **API REST**: Métodos GET, POST, PUT, DELETE
- **Contenedores**: Docker Compose
- **Gestión BD**: PhpMyAdmin incluido

---

¡Listo para usar! 🎉
