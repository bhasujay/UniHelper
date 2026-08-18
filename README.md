# UniHelper
### *A Custom-Built MVC Framework and Real-World Platform, Engineered from First Principles*

> **Status:** Real-World Solution · University Context · Pending Production Deployment

---

UniHelper is a centralized higher-education platform engineered around the full student lifecycle: "From Application to Graduation". It unifies three distinct stakeholder groups into a single digital ecosystem: providing intelligent Z-score matching, ML cutoff predictions, and Unicode generation for university applicants; cross-campus peer learning ("Kuppi" sessions), mentorship, and reputation-driven Q&A forums for undergraduates; and direct event broadcasting and academic promotion channels for university administrators.

The platform provides a complete real-world solution, but what this document focuses on is the **engineering underneath it**: a fully hand-rolled, SOA-inspired MVC framework written in raw PHP, conceived and built by a single architect without touching any external web framework. No Laravel. No Symfony. No scaffolding.

Nobody builds a custom router, a hand-written ORM base, and a polyglot Python/PHP inference bridge just to pass a module. This system was designed with deliberate architectural intent, and it shows. What follows is a breakdown of exactly how it was built.

---

## System Architecture

![UniHelper System Architecture](doc\architecture%20diagram.jpeg)

The diagram above is the authoritative map of the entire system. Every box is a real layer in the codebase, every arrow is a real call path, and the distinction between **solid lines** (server-rendered page channel) and **dashed lines** (stateless JSON/API channel) is the single most important architectural decision in the system. What follows is a layer-by-layer breakdown, reading the diagram from top to bottom, left to right.

---

### Layer Breakdown (Mapped to Diagram)

#### 1. Client (Green Box — Top Right)

The browser. It initiates every interaction via HTTP and receives responses through one of two channels:

- **Page Channel (solid arrow from Core → Client):** Full HTML responses. The Core renders a complete server-side page (login form, registration, or the Dashboard shell with an embedded component) and echoes it back.
- **API Channel (dashed arrow from API Gateway → Client):** Raw JSON responses. The client-side JavaScript makes `fetch()` calls to `/api?controller=X&action=Y`, receives JSON, and updates the DOM without a full page reload.

This dual-channel design means the system behaves like a traditional multi-page app for navigation (login → dashboard → profile), but like a single-page app for data operations (voting, posting, loading feeds) — all without a JavaScript framework.

#### 2. Core (Yellow Box — Top Left)

The framework kernel. Contains 5 files, zero external dependencies:

| File | Responsibility |
|---|---|
| `Application.php` | Composition root — wires `Request` + `Router`, calls `run()` (~22 lines) |
| `Router.php` | Three-mode dispatch engine (static, dynamic, view shortcut) |
| `Request.php` | Unified HTTP input normalizer across all 4 verbs (GET/POST/PUT/DELETE) |
| `Database.php` | Singleton PDO wrapper — one connection per request lifecycle |
| `mailer.php` | PHPMailer SMTP wrapper for transactional emails (OTP, session reminders) |

The **Router** (highlighted in gold in the diagram) is the system's traffic cop. Every inbound request — whether it's a page load or an API call — enters through the Router's `resolve()` method. The Router decides:

1. **Is it a static route?** → O(1) hash lookup in `$routes[$method][$path]`, dispatch to the controller.
2. **Is it a dynamic route?** → Regex match against `:param` patterns (e.g., `/:component`, `/view/profile/:id`), extract captures, dispatch.
3. **Is it a plain view string?** → Render the `.html` or `.php` file directly via output buffering.

The solid arrows from **Core → Controllers** in the diagram represent this dispatch: the Router instantiates the target controller, uses `ReflectionMethod` to inspect arity, and invokes the method with or without the `Request` object.

#### 3. Controllers (Orange Box — Middle Left)

The application logic layer. 20 controllers, split into two categories visible in the diagram:

**Page Controllers** (solid arrows to/from Router):
- `DashboardController` — the Dashboard shell orchestrator (routes `/:component` to view packages)
- `AuthController` — registration, login, logout, OTP-verified password changes
- Other domain controllers (`FeedController`, `SessionController`, `ZScoreController`, etc.)

**API Gateway** (highlighted in gold/orange, dashed arrows):
- `apiGateway.php` — the internal service bus. A single `/api` endpoint that multiplexes to *any* controller via `?controller=X&action=Y` query parameters.

The **bidirectional solid arrows** between Controllers and the Router in the diagram reflect that controllers both receive dispatched requests *and* register their routes in the Router's static table. The API Gateway's **dashed bidirectional arrow** to the Models layer shows how API operations bypass view rendering entirely — they query/mutate data through Models and return JSON directly to the Client.

**Built-in Auth Middleware** sits inside the API Gateway. Before any dispatch, it checks `$request->session('user_id')`. Public routes (OTP generation, feedback reads, auth checks) are allow-listed; everything else gets a `401 JSON` response. No redirect, no page reload — a clean API contract.

#### 4. Models (Blue Box — Middle Right)

The data abstraction layer. 25 models, all extending `BaseModel`:

- `BaseModel` (abstract) provides table-driven generic CRUD: `create()`, `find()`, `findAll()`, `update()`, `delete()`, `exists()`, `count()` — all assembled dynamically from data array keys and bound via PDO named placeholders.
- Domain models (`User`, `FeedPost`, `Session_model`, `qa`, `connection`, `moderation`, `search`, `DegreeProgram`, `ZScore_model`, etc.) extend the base and add complex multi-join queries.

The **arrow from Models → Database** (diagram bottom-right) represents every model obtaining its PDO connection from the Singleton `Database::getInstance()`. The **dashed arrow from Controllers ↔ Models** shows the API data path: controllers call model methods, models query the database, results flow back up as associative arrays.

#### 5. Views (Grey Box — Bottom Left)

The presentation layer, structured as a **component-based dashboard**:

- `dashboard.php` — the outer shell (sidebar, header, navigation chrome)
- `views/components/` — **19 view packages** (the "View Package 1 ... View Package n" in the diagram)
- `views/profile/` — 4 profile sub-components

The **curved solid arrow from Dashboard Controller → Views (Dashboard)** in the diagram represents the component loading pattern: `DashboardController::renderComponent()` receives a `:component` URL parameter, resolves it to a `.php` partial in `views/components/`, loads it with `ob_start()`/`ob_get_clean()` output buffering, and composes it into the Dashboard shell.

**Role-based routing** drives which components each user sees. The `config/sidebar_config.php` manifest maps roles to component arrays:

| Role | Available Components |
|---|---|
| **Applicant** | Q&A Forum, Z-Score Checker, Degree Programs, Unicode Generator, Connections, Feed |
| **Undergraduate** | Q&A Forum, Peer Learning, Connections, Feed, Moderation |
| **Profile** | Q&A Forum, Publish Events, Connections, Feed |
| **Admin** | System Overview, User Management, Moderation, Feed, Feedbacks, Q&A, Connections, Degree Management |

#### 6. UniHelper Database (Purple Cylinder — Bottom Right)

MySQL 8 with a single schema (`unihelper.sql`). The connection is configured for maximum safety:
- `ERRMODE_EXCEPTION` — all errors surface as catchable exceptions
- `FETCH_ASSOC` — clean associative arrays, no numeric indexing
- `EMULATE_PREPARES = false` — native prepared statements only (SQL injection protection)

---

### The Two Data Channels (Reading the Arrows)

The architecture diagram's most important detail is the **arrow style distinction**:

```
┌─────────────────────────────────────────────────────────────────────┐
│  SOLID ARROWS  ═══  Page-Rendering Channel (HTML)                  │
│                                                                     │
│  Client ──→ Core(Router) ──→ Controller ──→ Views ──→ Client       │
│                                                                     │
│  Full server-rendered page lifecycle. Used for navigation:          │
│  login, register, dashboard load, profile view, component switch.  │
├─────────────────────────────────────────────────────────────────────┤
│  DASHED ARROWS  - - -  API/JSON Channel (Data)                     │
│                                                                     │
│  Client ─ ─→ Core(Router) ─ ─→ API Gateway ─ ─→ Models ─ ─→ DB   │
│                                              ←─ ─ JSON ← ─ ─      │
│                                                                     │
│  Stateless data operations. Used for: voting, posting questions,   │
│  loading feeds, CRUD operations, connection requests, search.       │
└─────────────────────────────────────────────────────────────────────┘
```

Both channels share the same `Router` and `Request` primitives — the Router dispatches `/api` to the API Gateway for all four HTTP verbs (GET, POST, PUT, DELETE), while page routes dispatch to specific controller methods that render views.

---

### Architectural Philosophy

> *"The goal was total ownership of the request lifecycle: every layer designed deliberately, nothing inherited blindly."*

- **No magic.** No ORM magic, no annotation-based routing, no dependency injection containers. The Router uses explicit hash tables and `ReflectionMethod` for arity-aware dispatch.
- **Separation of concerns by contract.** The `core/` directory is framework-level code (5 files, ~350 lines). `controllers/`, `models/`, and `views/` are application-level code. They interact only through the `Request` object, `BaseModel` inheritance, and the `render_view()` output buffer.
- **Two-channel dispatch.** The Router handles page-level navigation (solid arrows). The API Gateway handles all data operations (dashed arrows). Both channels funnel through the same `Router.resolve()` entry point.
- **Composable data layer.** `BaseModel` provides generic CRUD driven by `$table` and `$primaryKey` properties. Domain models extend it to add multi-join domain queries.
- **API Gateway as a service multiplexer.** Instead of registering hundreds of API routes, a single `/api` endpoint dynamically dispatches to any controller/action pair — adding a new API operation means writing the method, nothing else.

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Runtime** | PHP 8.x (no web framework) |
| **Web Server** | Apache 2.4 + mod_rewrite (`.htaccess`) |
| **Database** | MySQL 8 via PDO (Singleton pattern) |
| **ML / Data Pipeline** | Python 3 (scikit-learn ensemble model, mysql-connector) |
| **Mailing Service** | PHPMailer + Gmail SMTP (via `.env` config) |
| **Env Management** | vlucas/phpdotenv |
| **Dependency Manager** | Composer (only 2 runtime deps) |
| **Namespace Convention** | PSR-4 (`app\core`, `app\controllers`, `app\models`) |

---

## Core Engine Mechanics

### 1. The Bootstrap & Request Lifecycle (`public/index.php` → `core/`)

Every request, regardless of path, method, or payload type, enters through a single front-controller: `public/index.php`. Apache's `mod_rewrite` rules (`.htaccess`) canonicalize all incoming URLs and funnel them here before any application code runs.

```
[Client HTTP Request]
        │
        ▼
[Apache mod_rewrite]  ←  .htaccess: RewriteRule ^(.*)$ public/index.php/$1
        │
        ▼
[public/index.php]  →  bootstraps Application, loads core, fires $app->run()
        │
        ▼
[core/Application.php]  →  instantiates Request + Router, calls router->resolve()
        │
        ▼
[core/Router.php]  →  dispatches to Controller or View
```

The `Application` class is intentionally minimal as a pure composition root. Its only job is to wire the `Request` and `Router` together and call `run()`. This makes the bootstrap sequence completely explicit and auditable in ~20 lines of code.

---

### 2. The Router (`core/Router.php`)

The custom `Router` is the most architecturally significant piece of the core. It supports three dispatch modes, resolved in priority order:

**A. Static Route Matching** (O(1) hash lookup on `[$method][$path]`):

```php
protected array $routes = [
    'GET'    => [ '/dashboard' => ['DashboardController', 'index'], ... ],
    'POST'   => [ '/login'     => ['AuthController', 'login'], ... ],
    'PUT'    => [ '/api'       => ['apiGateway', 'handleRequest'], ... ],
    'DELETE' => [ '/api'       => ['apiGateway', 'handleRequest'], ... ],
];
```

**B. Dynamic Route Matching** (Named-capture regex for parameterized paths):

```php
'DYNAMIC' => [
    '/:component'        => ['DashboardController', 'renderComponent'],
    '/view/profile/:id'  => ['DashboardController', 'viewProfile'],
]
```

The router converts `:param` tokens into named regex capture groups (`(?P<param>[^/]+)`) at match time, extracts the captures into a clean `$params` associative array, and forwards them to the controller method, all without any external routing library.

**C. View Shortcut**: If a route callback is a plain string instead of a `[Controller, method]` pair, the router directly renders the corresponding view file using output buffering (`ob_start` / `ob_get_clean`).

**PHP Reflection for Arity-Aware Dispatch:** Before invoking any controller method, the router uses `ReflectionMethod` to inspect the method's parameter count. Controllers that need the request context receive the `Request` object automatically; those that don't are called with no arguments. This eliminates any need for a dependency injection framework while preserving clean method signatures.

---

### 3. The Request Abstraction (`core/Request.php`)

The `Request` class is a unified HTTP input normalizer. It abstracts over all PHP superglobals (`$_GET`, `$_POST`, `$_FILES`, `$_SESSION`) and handles all four HTTP verbs:

- **GET:** Sanitized via `FILTER_SANITIZE_SPECIAL_CHARS`
- **POST:** Handles `multipart/form-data` (file uploads via `$_FILES`) and `application/x-www-form-urlencoded`. Also processes any query-string params appended to POST requests.
- **PUT / DELETE:** Reads and parses the raw `php://input` stream, detecting form-encoded vs. JSON payloads automatically.

A lazy-initialized body cache (`$reqBody`) ensures the input stream is only parsed once per request. The `session()` helper exposes `$_SESSION` access through the same consistent interface.

---

### 4. The API Gateway (`controllers/apiGateway.php`)

The API Gateway is the system's internal service bus. All `GET /api`, `POST /api`, `PUT /api`, and `DELETE /api` calls are routed here first. It then performs dynamic dispatch to any registered controller using the `?controller=` and `?action=` query parameters.

**Why this matters architecturally:** Instead of registering hundreds of individual API routes in the router's static table, a single `/api` endpoint acts as a multiplexer. Adding a new API operation means writing the controller method; the gateway dispatches to it automatically.

**Built-in Auth Middleware:** The gateway enforces session-based authentication on every request before dispatch, with a configurable allow-list for public-facing endpoints (OTP generation, feedback reads, auth existence checks):

```php
private function isPublicRoute(string $controller, string $action): bool {
    $publicRoutes = [
        'authcontroller'    => ['checkexistsaction'],
        'otpcontroller'     => ['generateotpaction', 'validateotpaction'],
        'feedbackcontroller'=> ['getfeedback'],
        'session_mail_check'=> ['send_mails_for_session'],
    ];
    // ...
}
```

Unauthenticated requests to protected routes receive a `401` with a JSON error body (no page reload, no redirect, just a clean API contract).

**Safe Dynamic Loading:** Before instantiating any controller class, the gateway verifies file existence, class existence (`class_exists`), and method existence (`method_exists`) before calling anything, making the dispatch chain fully hardened against invalid inputs.

---

### 5. The Data Layer: Singleton Database + Abstract BaseModel

**`core/Database.php`: The Singleton PDO Wrapper**

The database connection is managed via a classic Singleton pattern, ensuring exactly one PDO connection object is created per request lifecycle, regardless of how many models are instantiated. The connection is configured with:
- `ERRMODE_EXCEPTION`: all database errors surface as catchable exceptions
- `FETCH_ASSOC`: results are returned as clean associative arrays
- `EMULATE_PREPARES => false`: native prepared statements only, maximizing SQL injection protection

**`models/base-model.php`: The Hand-Written ORM Foundation**

All domain models extend `BaseModel`, which provides a generic CRUD interface driven purely by the model's `$table` and `$primaryKey` properties:

```
BaseModel
├── create($data)       → dynamic INSERT with named placeholders
├── find($id)           → SELECT by primary key
├── findAll($conditions, $limit, $offset) → parameterized SELECT with WHERE builder
├── update($id, $data)  → dynamic SET clause builder
├── delete($id)         → DELETE by primary key
├── exists($id)         → COUNT-based existence check
└── count($conditions)  → conditional COUNT query
```

All SQL is assembled dynamically from the data array keys and bound via PDO named placeholders (no string interpolation of user data anywhere in the base layer). Domain models (`User`, `FeedPost`, `Session_model`, `connection`, `moderation`, etc.) extend this base and add their own complex, multi-join queries where the generic CRUD is not sufficient.

---

### 6. The Component-Based Dashboard (`controllers/DashboardController.php`)

The dashboard is not a single monolithic page; it is a shell that dynamically loads "view packages" (components) based on the URL. This maps directly to the **Views** layer in the architecture diagram.

- The `/:component` dynamic route dispatches to `renderComponent()`, which loads the correct `.php` partial from `views/components/`.
- Each role (Applicant, Undergraduate, Profile, Admin) gets a completely different component set, driven by `config/sidebar_config.php`, a data-driven sidebar manifest that maps role strings to component arrays.
- Components are loaded with `ob_start()` / `ob_get_clean()` output buffering, allowing them to produce HTML that gets composed into the outer dashboard shell.
- Query-string params are forwarded into component scope via `extract()`, giving components access to URL parameters as native PHP variables.

This achieves a SPA-like component routing model on top of pure server-rendered PHP, with zero JavaScript framework involvement.

---

### 7. The Python ML Sub-System: Z-Score Eligibility & Prediction Engine

The most unconventional part of the architecture: a Python sub-process invoked by PHP at runtime to run ML inference.

**Runtime Flow:**
1. PHP controller (`ZScoreController`) collects the user's Z-score, stream, district, and subjects.
2. It constructs a hardened `shell_exec` command with `escapeshellarg` on every argument, invoking the Python script (`python/eligibility.py`) as a child process.
3. Python connects directly to MySQL, executes multi-join eligibility queries, and calculates a probability score using a custom piecewise linear model.
4. Results are serialized as a JSON array to stdout. PHP captures stdout, JSON-decodes, and returns the result via the API gateway.

**The Offline ML Pipeline** (`python/zscore_prediction/`) runs independently as a batch job. It fetches 4 years of historical Z-score cutoff data, trains an ensemble prediction model, generates the next year's cutoff predictions, and upserts them into a `cutoff_summary` lookup table, which the live eligibility engine then queries at runtime.

The separation of the training/prediction pipeline from the runtime query path is a deliberate architectural decision: ML inference is pushed offline, and the hot path stays fast.

---

## Feature Surface (What the Architecture Powers)

| Domain | Capabilities |
|---|---|
| **Auth** | Registration, login, OTP-verified password change, soft-delete account restore |
| **Z-Score Engine** | Eligibility check (Python subprocess), degree program matching, ML cutoff prediction |
| **Q&A Forum** | Questions, answers, voting, tagging, reporting |
| **Social Graph** | Connection requests, peer-to-peer connections, profile visibility controls |
| **Feed / Announcements** | University/institution post publishing and feed browsing |
| **Peer Learning Sessions** | Session scheduling, booking, auto status lifecycle management |
| **Moderation** | Report queue, flag/ignore/escalate workflow for undergrad moderators |
| **Admin Panel** | User management, system overview, degree program CRUD, feedback review |
| **Notifications** | In-app notification system with typed event categories |
| **Mailer** | SMTP transactional email for OTP delivery and session reminders |

---

## Project Structure

```
UniHelper/
├── core/                   # The framework: Router, Application, Request, Database, Mailer
├── controllers/            # 20 controllers: page controllers + API handlers
├── models/                 # 25 models: BaseModel + domain-specific query logic
├── views/                  # PHP templates + 19 component view packages
├── config/                 # Role-based sidebar manifest
├── python/                 # Eligibility engine + offline ML prediction pipeline
│   └── zscore_prediction/  # Fetch → Predict → Save batch pipeline
├── database_schema/        # MySQL schema (unihelper.sql)
├── public/                 # Front-controller (index.php) + static assets
└── .htaccess               # mod_rewrite: all traffic → public/index.php
```

---

## Running Locally

> This project is designed for a local LAMP/WAMP environment. There is no Docker setup and no hosted deployment.

**Prerequisites:** PHP 8.x, Apache with `mod_rewrite`, MySQL 8, Composer, Python 3 with `mysql-connector-python`

```bash
# 1. Clone the repo into your web server's document root
git clone https://github.com/<your-username>/UniHelper.git

# 2. Install PHP dependencies
composer install

# 3. Import the database schema
mysql -u root -p < database_schema/unihelper.sql

# 4. Configure environment
cp .env.example .env
# Fill in GMAIL_USERNAME, GMAIL_PASSWORD

# 5. (Optional) Run the ML prediction pipeline
cd python/zscore_prediction
pip install -r requirements.txt
python main.py

# 6. Point Apache vhost / document root at UniHelper/ and enable mod_rewrite
```

---

## Lead Architect & Core Developer

**Bhasujay**: Designed and implemented the core framework (`core/`), routing engine, API gateway, database layer, `BaseModel` abstraction, the Python/PHP inter-process communication bridge, and the overall system architecture.

---

*UniHelper is a functional, real-world platform built during university. The system is architecturally sound and production deployment is a planned next step.*
