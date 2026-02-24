# 🚀 GUÍA DEPLOYMENT - PROYECTO SEGURO A RAILWAY

## ✅ PROBLEMAS DE SEGURIDAD RESUELTOS

Antes de subir, se corrigieron 3 problemas críticos de OWASP:

### ✅ Problema 1: Broken Access Control
- **Dónde:** `app/config/database.php`, `app/api/login.php`, `app/api/tareas.php`
- **Corrección:** Sesiones servidor, rate limiting, verificación de acceso
  
### ✅ Problema 2: Fallas Criptográficas  
- **Dónde:** `app/config/database.php`, `app/api/registro.php`, `app/api/login.php`
- **Corrección:** Hash bcrypt, sanitización, variables de entorno

### ✅ Problema 3: Diseño Inseguro
- **Dónde:** Todos los archivos API
- **Corrección:** Patrón Singleton, mensajes genéricos, logging

**📄 Ver detalles:** `REPORTE_SEGURIDAD.md`

---

## PASO 1: PREPARAR EL PROYECTO

### 1.1 Verificar archivos

Tu proyecto debería tener esta estructura:

```
proyecto-seguro/
├── docker-compose.yml
├── init.sql
├── README.md
├── REPORTE_SEGURIDAD.md
├── .gitignore
└── app/
    ├── index.html
    ├── app.js
    ├── config/
    │   └── database.php         ← MODIFICADO ✅
    └── api/
        ├── registro.php          ← MODIFICADO ✅
        ├── login.php             ← MODIFICADO ✅
        ├── tareas.php            ← MODIFICADO ✅
        ├── estadisticas.php      ← MODIFICADO ✅
        └── clima.php             ← MODIFICADO ✅
```

### 1.2 Crear `.gitignore`

```bash
# En la raíz del proyecto
cat > .gitignore << 'EOF'
# Node modules
node_modules/

# Logs
*.log

# OS Files
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/

# Environment
.env

# Docker
db_data/
EOF
```

---

## PASO 2: SUBIR A GITHUB

### 2.1 Inicializar Git

```bash
cd proyecto-seguro
git init
git add .
git commit -m "Proyecto seguro con correcciones OWASP"
```

### 2.2 Crear repositorio en GitHub

1. Ve a: https://github.com/new
2. Nombre: `sistema-tareas-seguro`
3. Descripción: "Sistema de Gestión de Tareas con seguridad OWASP"
4. Public o Private
5. NO marcar "Initialize with README"
6. Click "Create repository"

### 2.3 Push a GitHub

```bash
git remote add origin https://github.com/TuUsuario/sistema-tareas-seguro.git
git branch -M main
git push -u origin main
```

---

## PASO 3: DEPLOY EN RAILWAY

### 3.1 Crear cuenta

1. Ve a: https://railway.app
2. Click "Start a New Project"
3. Login with GitHub
4. Autoriza Railway

### 3.2 Crear proyecto

1. Click "New Project"
2. "Deploy from GitHub repo"
3. Selecciona `sistema-tareas-seguro`
4. Railway importará automáticamente

### 3.3 Agregar MySQL

1. En tu proyecto Railway, click "+ New"
2. Selecciona "Database" → "MySQL"
3. Railway creará la BD automáticamente

### 3.4 Configurar Variables de Entorno

En el servicio "web", ve a "Variables" y agrega:

```
MYSQL_HOST = ${{MySQL.MYSQLHOST}}
MYSQL_DATABASE = ${{MySQL.MYSQLDATABASE}}
MYSQL_USER = ${{MySQL.MYSQLUSER}}
MYSQL_PASSWORD = ${{MySQL.MYSQLPASSWORD}}
ALLOWED_ORIGIN = https://tu-dominio.railway.app
```

**IMPORTANTE:** Reemplaza `tu-dominio` cuando tengas el dominio de Railway.

---

## PASO 4: IMPORTAR BASE DE DATOS

Railway no ejecuta `init.sql` automáticamente.

### Opción 1: Desde Railway Dashboard

1. Click en servicio "MySQL"
2. Tab "Data"
3. Click "Query"
4. Pega el contenido de `init.sql`
5. Click "Execute"

### Opción 2: MySQL Workbench

1. En Railway MySQL, click "Connect"
2. Copia las credenciales
3. Abre MySQL Workbench
4. Nueva conexión con datos de Railway
5. File → Run SQL Script → Selecciona `init.sql`

---

## PASO 5: CONFIGURAR DOMINIO

1. En Railway, click en servicio "web"
2. Tab "Settings"
3. Sección "Domains"
4. Click "Generate Domain"

Railway te dará una URL:
```
https://sistema-tareas-production.up.railway.app
```

### Actualizar ALLOWED_ORIGIN

1. Copia tu URL de Railway
2. Ve a Variables de entorno
3. Actualiza `ALLOWED_ORIGIN` con tu URL

---

## PASO 6: INSTALAR EXTENSIONES PHP

Railway usa la imagen base de PHP, pero necesitamos PDO MySQL.

### Crear `Dockerfile`

En la raíz del proyecto:

```dockerfile
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar archivos
COPY ./app /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Exponer puerto
EXPOSE 80
```

Luego actualiza GitHub:

```bash
git add Dockerfile
git commit -m "Add Dockerfile for Railway"
git push
```

Railway detectará el Dockerfile y lo usará automáticamente.

---

## PASO 7: VERIFICAR DEPLOYMENT

### 7.1 Verificar logs

En Railway → Deployments → View logs

Deberías ver:
```
✓ Build completed
✓ Deployment live
```

### 7.2 Probar la aplicación

Abre tu URL de Railway en el navegador.

**Login de prueba:**
- Email: admin@test.com
- Password: admin123

---

## PASO 8: PROBAR SEGURIDAD

### Prueba 1: Sesiones funcionan

```bash
# Login
curl -X POST https://tu-app.railway.app/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"admin123"}' \
  -c cookies.txt

# Usar sesión para obtener tareas
curl https://tu-app.railway.app/api/tareas.php?usuario_id=1 \
  -b cookies.txt
```

### Prueba 2: Rate Limiting

```bash
# Intentar 6 veces
for i in {1..6}; do
  curl -X POST https://tu-app.railway.app/api/login.php \
    -d '{"email":"test@test.com","password":"wrong"}';
done
```

Debería bloquear en el 6to intento.

### Prueba 3: XSS Prevención

Crear tarea con script:
```
Título: <script>alert('XSS')</script>
```

Debería guardarse escapado.

---

## 🎯 CHECKLIST FINAL

- [ ] Proyecto en GitHub
- [ ] Railway conectado a GitHub
- [ ] MySQL agregado
- [ ] Variables de entorno configuradas
- [ ] Base de datos importada
- [ ] Dominio generado
- [ ] Dockerfile creado
- [ ] ALLOWED_ORIGIN actualizado
- [ ] Login funciona
- [ ] Tareas funcionan
- [ ] Sesiones funcionan
- [ ] Rate limiting funciona

---

## 🔧 TROUBLESHOOTING

### Error: "Cannot connect to database"

**Solución:**
1. Verifica variables de entorno en Railway
2. Asegúrate que MySQL esté corriendo
3. Verifica logs: Railway → MySQL → Deployments

### Error: "Class 'PDO' not found"

**Solución:**
1. Asegúrate de tener el Dockerfile
2. Dockerfile debe instalar pdo_mysql
3. Redeploy: Railway → Deployments → Redeploy

### Error: "Session not working"

**Solución:**
1. Verifica que `cookie_secure` esté en `false` para HTTP
2. En producción HTTPS, debe ser `true`
3. Actualiza en `database.php` línea 91

### La app carga pero no funciona

**Solución:**
1. Abre consola del navegador (F12)
2. Busca errores de CORS
3. Verifica `ALLOWED_ORIGIN` en variables

---

## 📊 MONITOREO

### Ver logs en tiempo real

```bash
# Railway CLI (opcional)
railway logs
```

O desde dashboard: Railway → Servicio → Logs

### Verificar uso de recursos

Railway → Servicio → Metrics

Verás:
- CPU usage
- Memory usage
- Request count

---

## 💰 COSTOS

**Plan gratuito Railway:**
- $5 USD crédito/mes
- ~500 horas uptime
- Suficiente para proyecto escolar

**Si se acaba:**
- Pausar proyecto hasta siguiente mes
- O agregar tarjeta ($5/mes adicionales)

---

## 🎓 PRESENTACIÓN A TU MAESTRA

### Demuestra que resolviste los 3 problemas:

**1. Broken Access Control:**
- Muestra que no puedes ver tareas de otros usuarios
- Muestra el rate limiting bloqueando intentos
- Muestra código en `database.php` líneas 88-172

**2. Fallas Criptográficas:**
- Muestra que passwords están hasheadas en BD
- Muestra sanitización de inputs
- Muestra código en `registro.php` línea 51

**3. Diseño Inseguro:**
- Muestra mensajes genéricos de error
- Muestra logs en servidor
- Muestra patrón Singleton en `database.php`

### URLs para compartir:

- **Aplicación:** https://tu-app.railway.app
- **Código GitHub:** https://github.com/tu-usuario/sistema-tareas-seguro
- **Reporte de Seguridad:** En tu repositorio

---

## 📝 RESUMEN EJECUTIVO

```
✅ Proyecto funcionando en: https://tu-app.railway.app
✅ Código fuente en: GitHub
✅ Base de datos: MySQL en Railway
✅ Seguridad: OWASP Top 3 resueltos
✅ Documentación: Completa
✅ Pruebas: Funcionando

Tiempo estimado de deploy: 15-20 minutos
Nivel de seguridad: 9/10
```

---

¿Algún error? Contáctame con:
- Screenshot del error
- URL de tu app
- Logs de Railway
