# Sistema Bingo Cooperativa

Sistema web interno en PHP, MySQL, HTML, CSS y JavaScript para registrar manualmente los numeros que salen de una tombola fisica y mostrarlos en una pantalla de visualizacion en tiempo real.

## Requisitos

- XAMPP con Apache y MySQL activos.
- PHP 8.x recomendado.
- Navegador moderno.

## Instalacion local

1. Copia este proyecto en `C:\xampp\htdocs\sistema_bingo`.
2. Crea la base de datos importando `database.sql` desde phpMyAdmin o MySQL.
3. Revisa los datos de conexion en `config/database.php`.
4. Abre `http://localhost/sistema_bingo/`.

## Usuarios iniciales

- Operador: `operador` / `admin123`
- Visualizador: `visualizador` / `visor123`

Puedes crear mas usuarios desde SQL usando `password_hash` de PHP para la contrasena.

## Uso durante el evento

1. Inicia sesion como operador y crea una partida.
2. Abre otra pantalla, proyector o navegador con el usuario visualizador.
3. El operador marca solo los numeros que salen de la tombola fisica.
4. El visualizador se actualiza automaticamente cada 2 segundos.
5. Antes del evento, prueba red local, energia, navegador y permisos de usuarios.

## Arquitectura

- `login.php`: autenticacion.
- `operator.php`: panel del operador.
- `viewer.php`: pantalla publica de visualizacion.
- `history.php`: historial de partidas.
- `api.php`: endpoints AJAX para marcar, corregir, pausar, finalizar y consultar estado.
- `config/`: conexion a base de datos y helpers.
- `assets/`: CSS y JavaScript.

## Modelo de datos

- `roles`: operador y visualizador.
- `usuarios`: credenciales, estado y rol.
- `partidas_bingo`: partidas con estado en curso, pausada o finalizada.
- `numeros_marcados`: numeros salidos, letra, codigo, orden, usuario y hora.
- `historial_acciones`: auditoria de acciones importantes.
