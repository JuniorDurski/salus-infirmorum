CREATE TABLE designaciones (
    id_designacion INT AUTO_INCREMENT PRIMARY KEY,
    designacion VARCHAR(150) NOT NULL,
    periodo VARCHAR(100) NOT NULL
);

CREATE TABLE postulantes (
    id_postulante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(3) NULL,
    ci VARCHAR(20) NOT NULL,
    grupo_sanguineo varchar(100) DEFAULT NULL,  
    edad INT NOT NULL,
    sexo VARCHAR(10) NOT NULL,
    fecha   datetime  NOT NULL,
    id_designacion INT NOT NULL,
    FOREIGN KEY (id_designacion) REFERENCES designaciones (id_designacion)
);

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(250) NOT NULL,
    ci VARCHAR(20) NOT NULL,
    clave varchar(250) not null,
    cargo varchar(100) NOT NULL,
    fuente_claro INT(1) DEFAULT 1
);

CREATE TABLE examen (
    id_examen INT AUTO_INCREMENT PRIMARY KEY,
    id_postulante INT NOT NULL,
    examen TEXT NOT NULL,
    resultado TEXT NOT NULL,
    FOREIGN KEY (id_postulante) REFERENCES postulantes (id_postulante)
);