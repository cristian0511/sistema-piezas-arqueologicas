# Sistema de Gestión de Piezas Arqueológicas

Aplicación web ligera (CRUD completo) para el registro, consulta, edición y eliminación de piezas arqueológicas y coordenadas geográficas para la Dirección Nacional de Patrimonio Cultural.

## 🛠️ Tecnologías y Arquitectura
- **Orquestación**: Docker & Docker Compose
- **Servidor Web / Backend**: PHP 8.2 (Apache) con extensión PDO MySQL
- **Base de Datos**: MySQL 8.0
- **Control de Versiones**: Git

## 📁 Estructura del Proyecto
```text
sistema-piezas-arqueologicas/
├── .gitignore
├── README.md
├── Dockerfile
├── docker-compose.yml
├── init.sql
└── src/
    └── index.php