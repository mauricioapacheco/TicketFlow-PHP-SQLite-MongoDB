PRAGMA foreign_keys = ON;

CREATE TABLE Tipos_de_Servico (
    tipo_servico_id INTEGER PRIMARY KEY,
    nome TEXT NOT NULL UNIQUE
);

CREATE TABLE Data (
    data_id INTEGER PRIMARY KEY AUTOINCREMENT,
    data TEXT NOT NULL
);

CREATE TABLE Tickets (
    ticket_id INTEGER PRIMARY KEY,
    titulo TEXT NOT NULL,
    descricao TEXT,
    data_criacao_id INTEGER NOT NULL,
    tipo_servico_id INTEGER NOT NULL,
    prioridade TEXT NOT NULL,
    status_chamado TEXT NOT NULL,
    FOREIGN KEY (data_criacao_id) REFERENCES Data(data_id),
    FOREIGN KEY (tipo_servico_id) REFERENCES Tipos_de_Servico(tipo_servico_id)
);

CREATE INDEX idx_data_dia ON Data(data);
CREATE INDEX idx_tickets_data ON Tickets(data_criacao_id);
CREATE INDEX idx_tickets_tipo ON Tickets(tipo_servico_id);
