# SkyManifest ☁️

> **Your private cloud, manifested.**

## 1. Idea General del Proyecto

**SkyManifest** es una plataforma de infraestructura autohospedada (*Self-Hosted*) diseñada para democratizar el despliegue de sitios web estáticos. El proyecto permite a cualquier desarrollador construir su propia "nube privada", ofreciendo una alternativa a servicios comerciales como Vercel o Netlify.

Los usuarios pueden desplegar sus aplicaciones web simplemente arrastrando un archivo `.zip` o vinculando un repositorio de Git. El núcleo del sistema orquesta la recepción del código, su sanitización y la configuración automática de servidores web seguros, todo bajo el control total del administrador de la instancia.

## 2. Explicación del Nombre

El nombre **SkyManifest** encapsula la visión de infraestructura y control del proyecto:

* **Sky (Cielo / Nube):** Representa el entorno o ecosistema que el usuario está creando. Al no depender de nubes públicas de terceros, el usuario es dueño de su propio "cielo" digital, un espacio ilimitado y privado donde viven sus aplicaciones.
* **Manifest (Manifiesto):** Es el registro detallado y la declaración de existencia de cada aplicación. Cada vez que un usuario sube código, está creando un "manifiesto" de carga que el sistema procesa, registra y hace visible al mundo.

## 3. Explicación Detallada y Flujo

El objetivo es reducir la fricción entre el desarrollo local y la producción. Aunque la arquitectura es un monolito (Frontend y Backend en el mismo repo), se ha diseñado bajo un enfoque **API-First**. Esto garantiza que el Backend de Laravel funcione como un motor independiente que expone una API RESTful, consumida por el Frontend para la gestión de la interfaz.

### El flujo de vida de un despliegue:

1. **Input (Carga):** El usuario envía sus archivos (`.zip`) o la URL de su repositorio a través del dashboard.
2. **Procesamiento (Service Layer):** Laravel recibe la solicitud y delega la tarea a un servicio especializado (`DeploymentService`), liberando al controlador.
3. **Construcción de la Nube:** El sistema descomprime o clona el proyecto en un volumen compartido de Docker. Se ejecuta un proceso de limpieza estricto (sanitización), eliminando archivos de backend (.php, .env) o configuraciones del sistema para garantizar seguridad.
4. **Enrutamiento Dinámico (Caddy Layer):** Laravel se comunica internamente con la API de **Caddy Web Server**. Le instruye crear una nueva ruta de tráfico apuntando al dominio elegido y a la carpeta del despliegue.
5. **Despliegue (Live):** La web está en línea al instante (*Zero Downtime*) con certificados SSL automáticos gestionados por la infraestructura.

## 4. Arquitectura de Software

El proyecto utiliza **Laravel** como framework base, implementando un patrón **MVC (Modelo-Vista-Controlador)** robustecido con capas de **Repository** y **Service** para una separación de responsabilidades limpia y escalable.

### A. Estructura de Directorios y Capas

El proyecto respeta la estructura moderna de Laravel, añadiendo capas específicas de dominio:

* **📂 app/Http/Controllers:**
* Puntos de entrada ligeros. Solo validan la petición HTTP y devuelven respuestas JSON estandarizadas. No contienen lógica de negocio.


* **📂 app/Services:**
* El cerebro de la aplicación.
* **DeploymentService:** Maneja la lógica de archivos, descompresión, Git y limpieza.
* **CaddyService:** Abstrae la complejidad de la API de Caddy, construyendo los JSON de configuración necesarios.


* **📂 app/Repositories:**
* Capa de acceso a datos. Aísla las consultas de Eloquent, permitiendo que los servicios pidan datos ("Dame los últimos 5 deploys") sin saber cómo se obtienen.


* **📂 app/Jobs:**
* Manejo de colas (Queues). Tareas pesadas como "Clonar Repo" o "Descomprimir Zip" se envían aquí para no bloquear la interfaz del usuario.


* **📂 database/migrations:**
* Control de versiones del esquema de base de datos.


* **📂 resources/views:**
* Contiene el "App Shell", la vista principal que carga la aplicación SPA/Dashboard.



### B. Definición de Rutas

* **Ubicación:** `routes/api.php`
* **Estrategia:** Todas las interacciones de datos ocurren aquí. Se definen endpoints RESTful agrupados, protegidos por middleware (Sanctum) y con límites de peticiones (Rate Limiting) para proteger la infraestructura.

## 5. Modelos de Datos (Entidades)

A continuación se detalla la estructura de la base de datos relacional.

### Diagrama de Relaciones

```mermaid
erDiagram
    Users ||--o{ Projects : "crea"
    Projects ||--o{ Deploys : "registra historial"
    Projects ||--o{ Domains : "se expone en"

```

### Tablas y Estructuras

#### 1. Tabla: `users`

Representa al arquitecto o dueño de los proyectos en la nube privada.

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `id` | BIGINT (PK) | Identificador único. |
| `name` | STRING | Nombre completo. |
| `email` | STRING | Correo electrónico (Unique). |
| `password` | STRING | Contraseña encriptada. |
| `created_at` | TIMESTAMP | Fecha de registro. |

#### 2. Tabla: `projects`

La unidad lógica que agrupa los despliegues de una aplicación específica.

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `id` | BIGINT (PK) | Identificador único. |
| `user_id` | BIGINT (FK) | Relación con la tabla `users`. |
| `name` | STRING | Nombre del proyecto (ej: "Landing Page V1"). |
| `description` | TEXT | Descripción opcional (Null). |
| `created_at` | TIMESTAMP | Fecha de creación. |

#### 3. Tabla: `deploys`

El registro inmutable de cada versión subida a la nube.

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `id` | BIGINT (PK) | Identificador único. |
| `project_id` | BIGINT (FK) | Relación con la tabla `projects`. |
| `status` | ENUM | Estado: `pending`, `processing`, `success`, `failed`. |
| `log_messages` | JSON / TEXT | Bitácora de eventos del proceso (errores, éxito). |
| `path` | STRING | Ruta física del almacenamiento en el volumen Docker. |
| `duration_ms` | INTEGER | Tiempo de procesamiento en milisegundos. |
| `created_at` | TIMESTAMP | Fecha del despliegue. |

#### 4. Tabla: `domains`

La puerta de enlace pública para acceder a los proyectos.

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `id` | BIGINT (PK) | Identificador único. |
| `project_id` | BIGINT (FK) | Relación con la tabla `projects`. |
| `url` | STRING | El dominio o subdominio asignado (ej: `app.skymanifest.cloud`). |
| `is_active` | BOOLEAN | Switch para activar/desactivar el tráfico. |
| `ssl_status` | STRING | Estado del certificado TLS (ej: `issued`). |
| `created_at` | TIMESTAMP | Fecha de vinculación. |
