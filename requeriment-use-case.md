# SkyManifest - Requerimientos y Casos de Uso

**Versión:** 1.0  
**Fecha:** 19 de diciembre de 2025  
**Estado:** Prototipo  
**Nota:** Este es un prototipo desarrollado por una sola persona. Es posible que algunos de los requisitos no se cumplan al 100% debido a limitaciones de tiempo y scope.

---

## Introducción

**SkyManifest** es una plataforma web innovadora diseñada para simplificar significativamente el proceso de despliegue de sitios web estáticos. En un mundo donde la velocidad de desarrollo y la facilidad de deployment son críticos, SkyManifest abstrae la complejidad de la infraestructura tradicional, permitiendo a desarrolladores enfocarse en lo que realmente importa: el código.

Este documento especifica los requerimientos funcionales, no funcionales, casos de uso y la arquitectura técnica de la plataforma. Está dirigido a desarrolladores, arquitectos y stakeholders que participan en el proyecto.

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Descripción General del Proyecto](#descripción-general-del-proyecto)
3. [Requerimientos Funcionales](#requerimientos-funcionales)
4. [Requerimientos No Funcionales](#requerimientos-no-funcionales)
5. [Diagrama de Casos de Uso](#diagrama-de-casos-de-uso)
6. [Casos de Uso Detallados](#casos-de-uso-detallados)
7. [Diagrama de Clases UML](#diagrama-de-clases-uml)
8. [Diagrama de Secuencia - Despliegue Git](#diagrama-de-secuencia---despliegue-git)
9. [Diagrama de Secuencia - Despliegue ZIP](#diagrama-de-secuencia---despliegue-zip)
10. [Matriz RACI](#matriz-raci---responsabilidades)
11. [Estimación de Esfuerzo](#estimación-de-esfuerzo)
12. [Consideraciones Técnicas](#consideraciones-técnicas)
13. [Definición de Hecho](#definición-de-hecho-dod)

---

## Descripción General del Proyecto

**SkyManifest** es una plataforma web que simplifica el despliegue de sitios web estáticos, permitiendo a desarrolladores desplegar rápidamente proyectos desde repositorios Git o archivos ZIP, sin necesidad de gestionar infraestructura en la nube directamente.

### Objetivo Principal
Facilitar y agilizar el despliegue de sitios web estáticos mediante una interfaz centralizada que abstrae la complejidad de servidores web, SSL, gestión de dominios y almacenamiento.

---

## 2. Requerimientos Funcionales

### RF-001: Autenticación y Autorización
- El usuario debe poder registrarse con email y contraseña
- El usuario debe poder iniciar sesión
- El usuario debe poder cerrar sesión
- Solo usuarios autenticados pueden acceder a recursos privados
- Cada usuario solo puede gestionar sus propios proyectos y despliegues

### RF-002: Gestión de Proyectos
- El usuario puede crear nuevos proyectos asociando un nombre y descripción
- El usuario puede listar todos sus proyectos
- El usuario puede visualizar detalles de un proyecto específico
- El usuario puede actualizar nombre y descripción de un proyecto
- El usuario puede eliminar un proyecto (eliminando también sus dominios y despliegues asociados)

### RF-003: Configuración de Despliegue (Git)
- El usuario puede crear una configuración Git vinculando un repositorio (HTTPS/SSH URL)
- El usuario puede especificar la rama de despliegue (default: `main`)
- El usuario puede especificar el directorio de salida del build (ej: `/dist`, `/public_html`)
- **Importante:** El sistema espera que los archivos ya estén compilados en la carpeta `dist` (o la especificada) antes de iniciar el despliegue
- El usuario puede listar todas las configuraciones Git de un proyecto
- El usuario puede actualizar configuraciones Git existentes
- El usuario puede eliminar configuraciones Git

### RF-004: Despliegue desde Repositorio Git
- El usuario puede disparar un despliegue automático desde Git
- El sistema clona el repositorio en la rama especificada
- El sistema ejecuta comandos de build predefinidos (npm install, npm run build)
- El sistema extrae archivos del directorio especificado (asumiendo que ya están compilados en `dist`)
- El sistema prepara archivos estáticos para servir
- El sistema configura el servidor web (Caddy) con la nueva versión
- El usuario puede ver el progreso y logs del despliegue en tiempo real

### RF-005: Despliegue desde Archivo ZIP
- El usuario puede subir un archivo ZIP con los archivos estáticos
- El sistema valida que sea un ZIP válido
- El sistema extrae el contenido del ZIP
- El sistema maneja sincronización de archivos en Docker (con reintentos)
- El sistema prepara archivos para servir
- El sistema configura el servidor web con los nuevos archivos
- El usuario recibe confirmación de despliegue exitoso o error

### RF-006: Gestión de Dominios
- El usuario puede asociar un dominio a un proyecto
- El usuario puede configurar múltiples dominios para el mismo proyecto
- El usuario puede activar/desactivar dominios sin perder datos
- El usuario puede eliminar dominios
- El sistema gestiona SSL automáticamente para cada dominio
- El usuario puede ver el estado de SSL (pending, issued, failed)

### RF-007: Historial de Despliegues
- El usuario puede ver un registro completo de todos los despliegues
- El usuario puede filtrar despliegues por estado (pending, processing, success, failed)
- El usuario puede filtrar despliegues por fecha
- El usuario puede ver logs detallados de cada despliegue
- El usuario puede ver tiempo de duración de cada despliegue
- El usuario puede ver commit hash (despliegues Git)

### RF-008: Monitoreo y Alertas
- El sistema registra cada evento en el despliegue con timestamp
- El usuario recibe notificación de despliegue exitoso
- El usuario recibe notificación de despliegue fallido con motivo del error
- El sistema mantiene logs persistentes para auditoría

---

## 3. Requerimientos No Funcionales

### RNF-001: Rendimiento
- Despliegue de sitio estático debe completarse en menos de 5 minutos
- Respuesta a solicitudes de API debe ser menor a 500ms

### RNF-002: Disponibilidad
- El almacenamiento debe estar en volumen persistente Docker

### RNF-003: Seguridad
- Contraseñas deben estar encriptadas (bcrypt)
- Todas las conexiones deben usar HTTPS/SSL
- Tokens de autenticación deben expirar después de inactividad
- No se debe permitir acceso a directorios privados

### RNF-004: Escalabilidad
- Arquitectura basada en jobs en cola (Redis) para procesamiento asincrónico
- Soporte para múltiples usuarios simultáneamente
- Base de datos normalizada para eficiencia de consultas

### RNF-005: Mantenibilidad
- Código documentado con autodocumentación en PhpDoc
- Arquitectura en capas (Controllers, Services, Repositories)
- Separación de responsabilidades clara

---

## 5. Diagrama de Casos de Uso

```mermaid
graph TD
    A[Usuario] -->|Autenticarse| B[Sistema de Autenticación]
    A -->|Gestionar Proyectos| C[Gestión de Proyectos]
    A -->|Configurar Git| D[Configuración Git]
    A -->|Gestionar Dominios| E[Gestión de Dominios]
    A -->|Desplegar| F[Motor de Despliegue]
    A -->|Ver Historial| G[Historial de Despliegues]
    
    B -->|Crear Cuenta| H[(Base de Datos)]
    B -->|Iniciar Sesión| H
    
    C -->|CRUD| H
    D -->|CRUD| H
    E -->|CRUD| H
    
    F -->|Git| I[Repositorio Git]
    F -->|ZIP| J[Almacenamiento Local]
    F -->|Configurar SSL| K[Caddy Server]
    F -->|Encolar Job| L[Redis Queue]
    
    L -->|Ejecutar| M[Queue Jobs]
    M -->|Actualizar Estado| H
    
    G -->|Consultar| H
```

---

## 6. Casos de Uso Detallados

### UC-001: Registrar Nuevo Usuario

**Actores:** Visitante

**Precondiciones:**
- El usuario no tiene cuenta en el sistema

**Flujo Principal:**
1. El usuario accede a la página de registro
2. El usuario ingresa email, nombre y contraseña
3. El sistema valida que el email sea único
4. El sistema valida que la contraseña cumpla criterios de seguridad
5. El sistema encripta la contraseña
6. El sistema crea el usuario en la base de datos
7. El sistema envía email de confirmación (opcional)
8. El usuario es redirigido a login

**Flujo Alternativo - Email Duplicado:**
6a. El sistema rechaza el registro
6b. El sistema muestra mensaje de error

---

### UC-002: Crear Proyecto Nuevo

**Actores:** Usuario Autenticado

**Precondiciones:**
- Usuario está autenticado

**Flujo Principal:**
1. El usuario navega a "Nuevo Proyecto"
2. El usuario ingresa nombre del proyecto (requerido)
3. El usuario ingresa descripción (opcional)
4. El usuario hace clic en "Crear"
5. El sistema valida que el nombre no esté vacío
6. El sistema crea el proyecto asociado al usuario
7. El sistema redirige a la página de detalles del proyecto
8. El usuario puede ahora configurar Git, agregar dominios, o subir ZIP

**Flujo Alternativo - Nombre Vacío:**
5a. El sistema rechaza el envío
5b. El usuario recibe mensaje de validación

---

### UC-003: Desplegar desde Repositorio Git

**Actores:** Usuario Autenticado

**Precondiciones:**
- Usuario está autenticado
- Proyecto existe
- Configuración Git está configurada
- Dominio está asociado al proyecto

**Flujo Principal:**
1. El usuario navega a la página de despliegue del proyecto
2. El usuario selecciona fuente "Git"
3. El usuario confirma despliegue desde rama especificada
4. El sistema crea registro Deploy con estado `pending`
5. El sistema encola JobGitHubFetchSourceCode
6. **GitHubFetchSourceCodeJob:**
   - Clona repositorio en rama especificada
   - Ejecuta npm install
   - Ejecuta npm run build
   - Extrae archivos del directorio /dist
   - Encola PrepareStaticFilesJob
7. **PrepareStaticFilesJob:**
   - Verifica integridad de archivos
   - Establece permisos correctos
   - Encola ConfigureCaddyJob
8. **ConfigureCaddyJob:**
   - Configura Caddy con nueva ruta
   - Valida SSL
   - Reinicia servicio si es necesario
   - Encola UpdateDeployStatusJob
9. **UpdateDeployStatusJob:**
   - Actualiza estado a `success`
   - Registra duración
   - Notifica al usuario

**Flujo Alternativo - Clonación Fallida:**
6a. Job captura error
6b. Estado se actualiza a `failed`
6c. Usuario recibe notificación de error

**Flujo Alternativo - Build Fallido:**
6c. Job captura error durante npm run build
6d. Estado se actualiza a `failed`
6e. Usuario recibe logs del error

---

### UC-004: Desplegar desde Archivo ZIP

**Actores:** Usuario Autenticado

**Precondiciones:**
- Usuario está autenticado
- Proyecto existe
- Archivo ZIP está preparado
- Dominio está asociado al proyecto

**Flujo Principal:**
1. El usuario navega a "Nuevo Despliegue"
2. El usuario selecciona fuente "ZIP"
3. El usuario selecciona archivo ZIP desde su computadora
4. El sistema valida que sea un archivo ZIP válido
5. El sistema sube el archivo a almacenamiento temporal
6. El sistema crea registro Deploy con estado `pending`
7. El sistema encola ExtractZipJob
8. **ExtractZipJob (con reintentos):**
   - Valida que archivo ZIP exista (reintenta si no sincronizó en Docker)
   - Extrae contenido a directorio del proyecto
   - Elimina ZIP temporal
   - Encola PrepareStaticFilesJob
9. **PrepareStaticFilesJob:**
   - Verifica integridad de archivos
   - Establece permisos
   - Encola ConfigureCaddyJob
10. **ConfigureCaddyJob:**
    - Configura Caddy
    - Valida SSL
    - Reinicia servicio
    - Encola UpdateDeployStatusJob
11. **UpdateDeployStatusJob:**
    - Estado `success`
    - Notifica usuario
    - Registra duración

**Flujo Alternativo - ZIP Inválido:**
4a. Sistema rechaza archivo
4b. Usuario recibe mensaje de error

**Flujo Alternativo - Sincronización Docker (RETRY):**
8a. Sistema no encuentra archivo (delay en Docker)
8b. Sistema reintenta con espera de 500ms
8c. Hasta 3 intentos máximo
8d. Si falla, estado → `failed`, notificar usuario

---

### UC-005: Agregar Dominio a Proyecto

**Actores:** Usuario Autenticado

**Precondiciones:**
- Usuario está autenticado
- Proyecto existe
- Dominio no está registrado en otro proyecto activo

**Flujo Principal:**
1. El usuario navega a "Dominios" del proyecto
2. El usuario ingresa nuevo dominio (ej: `app.skymanifest.local`)
3. El usuario hace clic en "Agregar Dominio"
4. El sistema valida que dominio sea único
5. El sistema valida formato de dominio
6. El sistema crea registro Domain con `is_active=true`
7. El sistema inicia gestión de SSL automáticamente
8. El usuario es notificado con estado de SSL (`pending`)
9. El usuario puede ver progreso de SSL en listado de dominios

**Flujo Alternativo - Dominio Duplicado:**
4a. Sistema rechaza
4b. Usuario recibe mensaje de error

---

### UC-006: Ver Historial de Despliegues

**Actores:** Usuario Autenticado

**Precondiciones:**
- Usuario está autenticado
- Proyecto tiene despliegues previos

**Flujo Principal:**
1. El usuario navega a "Historial" del proyecto
2. El sistema muestra lista de todos los despliegues
3. El usuario puede ver:
   - Fecha y hora de despliegue
   - Estado (pending/processing/success/failed)
   - Tipo de fuente (git/zip)
   - Commit hash (si es Git)
   - Duración en ms
4. El usuario puede hacer clic en un despliegue para ver logs detallados
5. Los logs muestran cronología completa de eventos

**Filtros Disponibles:**
- Por estado (dropdown)
- Por fecha (rango)
- Por tipo de fuente

---

## 7. Diagrama de Clases UML

```mermaid
classDiagram
    class User {
        -id: BigInt
        -name: String
        -email: String
        -password: String
        -email_verified_at: DateTime
        +create()
        +update()
        +delete()
    }
    
    class Project {
        -id: BigInt
        -user_id: BigInt
        -name: String
        -description: Text
        +create()
        +getAll()
        +update()
        +delete()
    }
    
    class Deploy {
        -id: BigInt
        -project_id: BigInt
        -git_config_id: BigInt
        -source_type: Enum(git,zip)
        -commit_hash: String
        -status: Enum
        -path: String
        -log_messages: JSON
        -duration_ms: Int
        +create()
        +updateStatus()
        +getHistory()
    }
    
    class GitConfig {
        -id: BigInt
        -project_id: BigInt
        -repository_url: String
        -branch: String
        -base_directory: String
        +create()
        +validate()
        +update()
    }
    
    class Domain {
        -id: BigInt
        -project_id: BigInt
        -url: String
        -is_active: Boolean
        -ssl_status: String
        +create()
        +toggleActive()
        +updateSSL()
    }
    
    class DeployService {
        +deployFromGit()
        +deployFromZip()
        +orchestrateJobs()
    }
    
    class CaddyService {
        +configureRoute()
        +updateSSL()
        +restartServer()
    }
    
    class GitHubServices {
        +cloneRepository()
        +buildProject()
        +extractOutput()
    }
    
    class ExtractZipJob {
        +execute()
        +retry()
    }
    
    class PrepareStaticFilesJob {
        +execute()
    }
    
    class ConfigureCaddyJob {
        +execute()
    }
    
    User "1" -- "*" Project: owns
    Project "1" -- "*" Deploy: has
    Project "1" -- "*" GitConfig: has
    Project "1" -- "*" Domain: has
    Deploy "many" -- "0..1" GitConfig: uses
    
    DeployService -- Project: orchestrates
    DeployService -- ExtractZipJob: queues
    DeployService -- GitHubServices: uses
    
    ExtractZipJob -- Deploy: updates
    PrepareStaticFilesJob -- Deploy: updates
    ConfigureCaddyJob -- CaddyService: uses
```

---

## 8. Diagrama de Secuencia - Despliegue Git

```mermaid
sequenceDiagram
    participant User as Usuario
    participant API as API Controller
    participant DeployService as Deploy Service
    participant Queue as Redis Queue
    participant GitJob as GitHubFetch Job
    participant PrepareJob as PrepareStaticFiles
    participant CaddyJob as ConfigureCaddy
    participant DB as Database

    User->>API: POST /deploys (project_id, source=git)
    API->>DeployService: deployFromGit(project)
    DeployService->>DB: Create Deploy (status=pending)
    DB-->>DeployService: Deploy ID
    DeployService->>Queue: Enqueue GitHubFetchSourceCodeJob
    Queue-->>API: Success (202 Accepted)
    API-->>User: Deployment iniciado
    
    Note over Queue: Job Ejecutándose...
    Queue->>GitJob: execute()
    GitJob->>GitJob: Clone repository
    GitJob->>GitJob: npm install && npm run build
    GitJob->>GitJob: Extract /dist files
    GitJob->>DB: Update Deploy logs
    GitJob->>Queue: Enqueue PrepareStaticFilesJob
    
    Queue->>PrepareJob: execute()
    PrepareJob->>PrepareJob: Verify integrity
    PrepareJob->>PrepareJob: Set permissions
    PrepareJob->>DB: Update Deploy logs
    PrepareJob->>Queue: Enqueue ConfigureCaddyJob
    
    Queue->>CaddyJob: execute()
    CaddyJob->>CaddyJob: Configure Caddy route
    CaddyJob->>CaddyJob: Validate SSL
    CaddyJob->>CaddyJob: Restart service
    CaddyJob->>DB: Update Deploy (status=success)
    CaddyJob->>DB: Record duration_ms
    
    Note over User,DB: Usuario recibe notificación
```

---

## 9. Diagrama de Secuencia - Despliegue ZIP

```mermaid
sequenceDiagram
    participant User as Usuario
    participant Upload as Upload Handler
    participant API as API Controller
    participant DeployService as Deploy Service
    participant Queue as Redis Queue
    participant ExtractJob as ExtractZip Job
    participant PrepareJob as PrepareStaticFiles
    participant CaddyJob as ConfigureCaddy
    participant Storage as Storage (Docker Volume)
    participant DB as Database

    User->>Upload: Select ZIP file
    Upload->>API: POST /deploys/zip (multipart)
    API->>Storage: Save ZIP to temp location
    Storage-->>API: File saved
    API->>DeployService: deployFromZip(project, zipPath)
    DeployService->>DB: Create Deploy (status=pending, path=)
    DeployService->>Queue: Enqueue ExtractZipJob
    Queue-->>API: Success (202 Accepted)
    API-->>User: Deployment iniciado
    
    Note over Queue: ExtractZip Job con REINTENTOS...
    Queue->>ExtractJob: execute()
    ExtractJob->>Storage: Check if file exists (attempt 1)
    alt File not found (Docker sync delay)
        ExtractJob->>ExtractJob: Sleep 500ms
        ExtractJob->>Storage: Check if file exists (attempt 2)
    else File found
        ExtractJob->>Storage: Extract ZIP content
        ExtractJob->>Storage: Delete temporary ZIP
        ExtractJob->>Queue: Enqueue PrepareStaticFilesJob
    end
    
    Queue->>PrepareJob: execute()
    PrepareJob->>Storage: Verify all files
    PrepareJob->>Storage: Set permissions
    PrepareJob->>Queue: Enqueue ConfigureCaddyJob
    
    Queue->>CaddyJob: execute()
    CaddyJob->>CaddyJob: Configure Caddy
    CaddyJob->>CaddyJob: Validate SSL
    CaddyJob->>CaddyJob: Restart service
    CaddyJob->>DB: Update Deploy (status=success)
    
    Note over User,DB: Usuario recibe notificación de éxito
```

---

## 9. Matriz RACI - Responsabilidades

| Actividad | Usuario | Sistema | Base Datos | Queue |
|---|---|---|---|---|
| Crear Proyecto | Responsable | Soporta | Almacena | N/A |
| Configurar Git | Responsable | Valida | Almacena | N/A |
| Disparar Despliegue | Responsable | Orquesta | Registra | Procesa |
| Clonar Git | N/A | Ejecuta | N/A | Encola |
| Extraer ZIP | N/A | Ejecuta | N/A | Encola |
| Configurar Caddy | N/A | Ejecuta | N/A | Encola |
| Notificar Usuario | N/A | Informa | N/A | Registra |
| Auditoría/Logs | Consulta | Registra | Almacena | Registra |

---

## 10. Estimación de Esfuerzo

| Componente | Horas | Prioridad |
|---|---|---|
| Autenticación | 16 | ALTA |
| CRUD Proyectos | 12 | ALTA |
| Despliegue Git | 32 | ALTA |
| Despliegue ZIP | 24 | ALTA |
| Gestión Dominios | 20 | ALTA |
| Gestión SSL (Caddy) | 24 | ALTA |
| Historial/Logs | 16 | MEDIA |
| Notificaciones | 12 | MEDIA |
| Tests | 40 | ALTA |
| Documentación | 16 | MEDIA |
| **TOTAL** | **212 horas** | - |

---

## 11. Consideraciones Técnicas

### Retry Logic para Docker Sync
```
ExtractZipJob Workflow:
├── Attempt 1: Storage::exists(zipPath)
│   └── Si falla → Sleep 500ms
├── Attempt 2: Storage::exists(zipPath)
│   └── Si falla → Sleep 500ms
├── Attempt 3: Storage::exists(zipPath)
│   └── Si falla → Throw JobFailedException
└── Success → Extract and cleanup
```

### Job Chaining
Los despliegues utilizan `Bus::chain()` para garantizar ejecución secuencial:
```
Bus::chain([
    new GitHubFetchSourceCodeJob($deploy),
    new PrepareStaticFilesJob($deploy),
    new ConfigureCaddyJob($deploy),
])
->dispatch();
```

### Estados de Deploy
- `pending`: Creado, esperando procesamiento
- `processing`: Job en ejecución
- `success`: Completado exitosamente, sitio en línea
- `failed`: Error durante proceso

---

## 12. Definición de Hecho (DoD)

Una funcionalidad se considera completa cuando:

✅ Todos los tests unitarios pasan (>80% cobertura)  
✅ Todos los tests de integración pasan  
✅ Code review aprobado  
✅ PHPDoc comentarios completos en español  
✅ Sin warnings de linters (PHP, JavaScript)  
✅ Documentación actualizada en README.md  
✅ Casos de uso documentados  
✅ Manual testeado en ambiente local Docker  
✅ Sin quebrantos en despliegues previos  

---

**Documento generado:** 19 de diciembre de 2025  
**Siguiente revisión:** Diciembre 2025 post-desarrollo
