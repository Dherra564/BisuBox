# BisuBox
Proyecto de Paradigmas de Programación

localhost/BisuBox

Vendedor
Correo: vendedor.bisubox@gmail.com
Contraseña: Vendedor2026

Cliente
Correo: cliente.bisubox@gmail.com
Contraseña: Cliente2026

## Instalación en Linux (Ubuntu o Debian)

1. Instalar Apache, PHP, MariaDB y Composer:

```bash
sudo apt update
sudo apt install apache2 mariadb-server php libapache2-mod-php php-mysql php-mbstring composer
```

2. Activar las direcciones limpias (las usa el archivo .htaccess):

```bash
sudo a2enmod rewrite
```

En `/etc/apache2/apache2.conf`, dentro de `<Directory /var/www/>`, cambiar `AllowOverride None` por `AllowOverride All`. Luego:

```bash
sudo systemctl restart apache2
```

3. Poner el proyecto en `/var/www/html/BisuBox` e instalar las dependencias:

```bash
cd /var/www/html
sudo git clone https://github.com/Dherra564/BisuBox.git
cd BisuBox
sudo composer install
sudo cp .env.ejemplo .env
```

4. Crear la base de datos. En Linux el usuario root de MariaDB no entra con contraseña vacía desde PHP, por eso se crea un usuario para el proyecto:

```bash
sudo mysql < BaseDatos/ScriptsSQL/bdbisubox.sql
sudo mysql -e "CREATE USER 'bisubox'@'localhost' IDENTIFIED BY 'Bisubox2026'; GRANT ALL ON bdbisubox.* TO 'bisubox'@'localhost';"
```

En `.env` poner `bdUsuario=bisubox` y `bdContrasena=Bisubox2026`.

5. Dar permiso de escritura a la carpeta de fotos y registros (Apache corre como `www-data`):

```bash
sudo chown -R www-data:www-data Almacenamiento
sudo chmod -R 775 Almacenamiento
```

6. Abrir `http://localhost/BisuBox`.

Si una imagen no se guarda, el motivo queda anotado en `Almacenamiento/Registros/errores.log`.

## Cuando se agrega una clase nueva

El proyecto usa el autoload optimizado de Composer. Después de crear un archivo PHP con una clase nueva hay que ejecutar:

```bash
composer dump-autoload
```

Si no, aparece el error `Class "..." not found`.

## Cuidado con mayúsculas y minúsculas

Linux distingue mayúsculas de minúsculas en los nombres de archivos y carpetas, Windows no. `Vistas/Tienda/miTienda.php` y `vistas/tienda/mitienda.php` son archivos distintos en Linux. El nombre del archivo tiene que ser igual al de la clase y al que se usa en el `require`.