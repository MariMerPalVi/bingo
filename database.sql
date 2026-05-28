CREATE DATABASE IF NOT EXISTS sistema_bingo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_bingo;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS partidas_bingo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_partida VARCHAR(140) NOT NULL,
  fecha DATE NOT NULL,
  estado ENUM('en_curso', 'pausada', 'finalizada') NOT NULL DEFAULT 'en_curso',
  usuario_creador INT NOT NULL,
  fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_fin DATETIME NULL,
  CONSTRAINT fk_partidas_usuario FOREIGN KEY (usuario_creador) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS numeros_marcados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  partida_id INT NOT NULL,
  numero TINYINT UNSIGNED NOT NULL,
  letra CHAR(1) NOT NULL,
  codigo_bingo VARCHAR(6) NOT NULL,
  orden_salida TINYINT UNSIGNED NOT NULL,
  usuario_id INT NOT NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_numeros_partida FOREIGN KEY (partida_id) REFERENCES partidas_bingo(id) ON DELETE CASCADE,
  CONSTRAINT fk_numeros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT uq_numero_partida UNIQUE (partida_id, numero),
  CONSTRAINT uq_orden_partida UNIQUE (partida_id, orden_salida),
  CONSTRAINT chk_numero_rango CHECK (numero BETWEEN 1 AND 75)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historial_acciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  partida_id INT NULL,
  accion VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_historial_partida FOREIGN KEY (partida_id) REFERENCES partidas_bingo(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id, nombre) VALUES
  (1, 'operador'),
  (2, 'visualizador');

INSERT IGNORE INTO usuarios (id, nombre, usuario, contrasena, rol_id, estado) VALUES
  (1, 'Operador Principal', 'operador', '$2y$10$yqbcVz3siywz8aofN0AFs.DL2sOyRl.9SUI.rx85m2TeRZVkypMzO', 1, 'activo'),
  (2, 'Pantalla Visualizador', 'visualizador', '$2y$10$6VgKO4mI.KH6sgOP/t3J2eTyvjRv7NeLznun9BsBDtYefS5wIVDL6', 2, 'activo');
