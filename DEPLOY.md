# DEPLOY A SERVIDOR DE PRODUCCION

## 📋 CHECKLIST DE ARCHIVOS A SUBIR

```
✓ Carpeta: app/
✓ Carpeta: routes/
✓ Carpeta: resources/
✓ Carpeta: config/
✓ Carpeta: database/migrations
✓ Carpeta: public/build/          (generado por npm run build)
✓ Carpeta: bootstrap/ssr/         (generado por npm run build)
✓ Carpeta: bootstrap/app.php
✓ Carpeta: bootstrap/providers.php
✗ NO subir: node_modules/
✗ NO subir: vendor/               (se instala con composer install --no-dev)
✗ NO subir: .env (local)
✗ NO subir: storage/logs/*
```

## 🚀 PASOS DE INSTALACION EN SERVIDOR

### 1. Conectar y subir archivos via FTP/SFTP

Sube la carpeta `Carrito/` manteniendo esta estructura:

```
/tu-dominio.com/public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── build/          ← Generado por npm run build
│   ├── storage/
│   └── index.php
├── resources/
├── routes/
├── .env.production     ← Renombra a .env
├── artisan
├── composer.json
├── composer.lock
└── ...otros archivos
```

### 2. Configurar archivo .env

En el servidor, edita `.env` con tus datos:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=carrito_produccion
DB_USERNAME=tu_usuario_mysql
DB_PASSWORD=tu_contrasena_mysql

MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_USERNAME=tu_email@dominio.com
MAIL_PASSWORD=tu_contrasena_mail
```

### 3. Instalar dependencias PHP

```bash
# En el servidor, por SSH
cd /ruta/a/public_html

composerinstall --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan storage:link
```

### 4. Importar base de datos

Sube `carrito_db_backup.sql` al servidor.

**Opcion A: Via phpMyAdmin**

- Accede a phpMyAdmin de tu proveedor
- Crea BD: `carrito_produccion`
- Importa archivo `carrito_db_backup.sql`

**Opcion B: Via terminal SSH**

```bash
mysql -u tu_usuario -p tu_contrasena carrito_produccion < carrito_db_backup.sql
```

### 5. Configurar permisos

```bash
chmod -R 755 /ruta/a/public_html
chmod -R 775 /ruta/a/public_html/storage
chmod -R 775 /ruta/a/public_html/bootstrap/cache
```

### 6. Optimizar e ir a produccion

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 7. Generar nueva APP_KEY

```bash
# En el archivo .env reemplaza:
APP_KEY=base64:Tu_base64_clave_generada_aqui

# O ejecuta:
php artisan key:generate
```

## 📊 DATOS DE LA BD EXPORTADA

- **Archivo**: carrito_db_backup.sql
- **Tamaño**: 0.07 MB (muy pequeño, sin problemas)
- **Contiene**:
    - ✓ Tablas de catalogo (productos, categorias, banners)
    - ✓ Usuarios de prueba (test@example.com, cliente@example.com)
    - ✓ Cupones de demo
    - ✓ Configuracion de tienda

## 🔐 SEGURIDAD POST-DEPLOY

1. Cambiar contraseñas de usuarios admin en BD
2. Configurar HTTPS en certificado SSL
3. Cambiar APP_KEY en `.env` (ya se hace con php artisan key:generate)
4. Rotar CSRF_TOKEN si es necesario
5. Verificar permisos de carpetas (storage, bootstrap/cache deben ser 775)

## ✅ VERIFICACION FINAL

Accede a tu dominio y verifica:

- [ ] Pagina principal carga sin errores
- [ ] Admin es accesible en `/admin/analitica`
- [ ] Carrito funciona
- [ ] Logout te redirige a inicio
- [ ] Imagenes cargan correctamente
- [ ] Sin errores en logs (verificar storage/logs/laravel.log)

## 🆘 TROUBLESHOOTING

**Error 500 pero no ves logs:**

```bash
php artisan tinker
echo storage_path('logs');
# Ve a esa ruta y revisa laravel.log
```

**Permiso denegado en storage:**

```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/  # si es Linux con Apache
```

**Base de datos no conecta:**

- Verifica credenciales en `.env`
- Confirma que la BD existe: `mysql -u usuario -p -e "SHOW DATABASES;"`
- Verifica conectividad: `mysql -h localhost -u usuario -p carrito_produccion`

---

**Tamaño del skip:** El proyecto completo es ~200 MB sin vendor y node_modules.
Con ambas carpetas compiladas: ~800 MB total.
