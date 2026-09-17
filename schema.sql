CREATE DATABASE IF NOT EXISTS tcc_medicamentos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tcc_medicamentos;

CREATE TABLE IF NOT EXISTS medicamentos (
    id VARCHAR(36) PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    lote VARCHAR(50) NOT NULL,
    data_fabricacao DATETIME NOT NULL,
    assinatura TEXT NOT NULL,
    status TINYINT(1) DEFAULT 0 COMMENT '0 = Nao Validado, 1 = Validado',
    data_validacao DATETIME NULL
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
