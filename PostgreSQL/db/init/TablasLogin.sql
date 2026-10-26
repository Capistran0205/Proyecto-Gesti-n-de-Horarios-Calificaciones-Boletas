CREATE TABLE usuario (
    id                  INTEGER      GENERATED ALWAYS AS IDENTITY,
    nombre              VARCHAR(60)  NOT NULL,
    apellido_pat        VARCHAR(60)  NOT NULL,
    apellido_mat        VARCHAR(60),
    rol                 SMALLINT     NOT NULL,
    usuario             VARCHAR(30)  NOT NULL,
    correo          TEXT NOT NULL,
    contrasena          TEXT         NOT NULL,
    estado              SMALLINT     NOT NULL DEFAULT 1,
    fecha_registro      TIMESTAMPTZ  NOT NULL DEFAULT now(),
    fecha_modificacion  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT pk_usuario                 PRIMARY KEY (id),
    CONSTRAINT uq_usuario_usuario         UNIQUE (usuario),
    CONSTRAINT uq_correo                          UNIQUE(correo),
    CONSTRAINT ck_usuario_rol             CHECK (rol IN (0, 1, 2, 3)),
    CONSTRAINT ck_usuario_estado          CHECK (estado IN (0, 1)),
    CONSTRAINT ck_usuario_usuario_formato CHECK (usuario ~ '^[a-z0-9_]{4,30}$')
);

-- Tabla hija para el superusuario
CREATE TABLE superusuario (
    id_superusuario INTEGER NOT NULL,
    CONSTRAINT pk_superusuario         PRIMARY KEY (id_superusuario),
    CONSTRAINT fk_superusuario_usuario FOREIGN KEY (id_superusuario) REFERENCES usuario (id)
);
 
-- Solo puede existir un SuperUsuario: índice único sobre una constante
CREATE UNIQUE INDEX uq_superusuario_unico ON superusuario ((TRUE));
 
-- Tabla hija para los administradores
CREATE TABLE administrador (
    id_admin INTEGER NOT NULL,
    CONSTRAINT pk_administrador         PRIMARY KEY (id_admin),
    CONSTRAINT fk_administrador_usuario FOREIGN KEY (id_admin) REFERENCES usuario (id)
);
 

-- Tabla hija para los docentes 
CREATE TABLE docente (
    id_docente     INTEGER     NOT NULL,
    curp           CHAR(18)    NOT NULL,
    rfc            VARCHAR(13) NOT NULL,
    modalidad      SMALLINT    NOT NULL,
    disponibilidad JSONB       NOT NULL DEFAULT '{}'::JSONB,
    CONSTRAINT pk_docente                PRIMARY KEY (id_docente),
    CONSTRAINT fk_docente_usuario        FOREIGN KEY (id_docente) REFERENCES usuario (id),
    CONSTRAINT uq_docente_curp           UNIQUE (curp),
    CONSTRAINT uq_docente_rfc            UNIQUE (rfc),
    CONSTRAINT ck_docente_curp           CHECK (curp ~ '^[A-Z]{4}[0-9]{6}[HMX][A-Z]{5}[A-Z0-9][0-9]$'),
    CONSTRAINT ck_docente_rfc            CHECK (rfc  ~ '^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$'),
    CONSTRAINT ck_docente_modalidad      CHECK (modalidad IN (1, 2)),
    CONSTRAINT ck_docente_disponibilidad CHECK (jsonb_typeof(disponibilidad) = 'object')
);

-- Tabla hija para los alumnos
CREATE TABLE alumno (
    id_alumno INTEGER     NOT NULL,
    curp      CHAR(18)    NOT NULL,
    nss       VARCHAR(11),                -- opcional; hacerlo NOT NULL si el PO lo exige
    modalidad SMALLINT    NOT NULL,
    semestre  SMALLINT    NOT NULL,
    id_area   INTEGER,                    -- NULL hasta que elija especialidad (5.º semestre)
    CONSTRAINT pk_alumno           PRIMARY KEY (id_alumno),
    CONSTRAINT fk_alumno_usuario   FOREIGN KEY (id_alumno) REFERENCES usuario (id),
    CONSTRAINT uq_alumno_curp      UNIQUE (curp),
    CONSTRAINT uq_alumno_nss       UNIQUE (nss),
    CONSTRAINT ck_alumno_curp      CHECK (curp ~ '^[A-Z]{4}[0-9]{6}[HMX][A-Z]{5}[A-Z0-9][0-9]$'),
    CONSTRAINT ck_alumno_nss       CHECK (nss ~ '^[0-9]{11}$'),
    CONSTRAINT ck_alumno_modalidad CHECK (modalidad IN (1, 2)),
    CONSTRAINT ck_alumno_semestre  CHECK (semestre BETWEEN 1 AND 6)
);