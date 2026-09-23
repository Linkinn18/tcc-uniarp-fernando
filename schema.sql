CREATE TABLE IF NOT EXISTS medicamentos (
    id TEXT PRIMARY KEY,
    nome TEXT NOT NULL,
    lote TEXT NOT NULL,
    criado_em TEXT NOT NULL,
    assinatura TEXT NOT NULL,
    status INTEGER NOT NULL DEFAULT 0,
    data_validacao TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_medicamentos_nome ON medicamentos(nome);
CREATE INDEX IF NOT EXISTS idx_medicamentos_lote ON medicamentos(lote);

CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS validacoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    medicamento_id TEXT,
    resultado TEXT NOT NULL,
    data TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    user_agent TEXT
);
