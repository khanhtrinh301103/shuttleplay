# 📄 SHUTTLEPLAY - BADMINTON E-COMMERCE

**PROJECT DOCUMENT (FINAL DETAILED VERSION — REACT FRONTEND + LARAVEL API BACKEND)**

---

## 1️⃣ PROJECT OVERVIEW

**Project Name:**
ShuttlePlay - Badminton E-commerce

**Project Goal:**
Develop a full-stack e-commerce web application that allows users to buy and sell badminton-related products with multi-role access control (Customer, Seller, Admin).
The system will provide:

* Responsive UI (SPA — Single Page Application)
* Role-based features
* Secure authentication
* Universal API architecture (Single API endpoint powering all data-driven pages)
* Unit tests
* Full deployment to cloud
* Messaging feature between users
* Image storage & serving (multi-image per product, hosted on Cloud Storage)

**Architecture:**

* Frontend: React (SPA) standalone project
* Backend: Laravel headless API server
* Communication: REST API (`/api/bff` + Transactional API)

**Expected Outcomes:**

* A complete full-stack project to showcase on GitHub and in CV
* Experience in building decoupled architecture (Frontend & Backend separately deployed)
* Experience in role-based access control (RBAC)
* Implement Universal API architecture in Laravel
* Implement unit testing with PHPUnit
* Integrate Cloud Storage service for product images
* Practice Agile workflow through Kanban process
* Deploy React frontend & Laravel backend to production cloud environment

---

## 2️⃣ KEY FEATURES

### Authentication & Authorization:

* User Registration / Login / Logout (via API)
* Role-based access control (RBAC):

  * Customer
  * Seller
  * Admin

### Customer Features:

* Browse product catalog
* View product details (multi-image display)
* Add products to cart
* Checkout & place orders
* View order history
* Send message to Seller about a product

### Seller Features:

* Manage own product catalog (CRUD)
* Upload and manage product images (multi-image)
* View orders that include Seller’s products
* Receive and reply to Customer messages

### Admin Features:

* Manage users (CRUD)
* Manage all products (CRUD)
* Approve/publish product listings
* Manage all orders (CRUD)
* View system statistics in Admin Dashboard
* Monitor user messages (optional moderation)

### Messaging Feature:

* Simple 1-1 messaging system:

  * Customer ↔ Seller (per product basis)
  * Store message history
  * Read/unread message status
  * Admin audit access (optional)

### API Architecture

#### Universal API

Single API endpoint powering all data-driven pages:

```
POST /api/bff
```

**Request payload:**

```json
{
    "page": "homePage" | "productDetail" | "userDashboard" | "adminDashboard" | "cartPage" | ...,
    "params": {
        // Optional parameters for each page
    }
}
```

**Response format:**

```json
{
    "data": {
        // Page-specific data
    }
}
```

**Example usage:**

```json
POST /api/bff

{
    "page": "productDetail",
    "params": {
        "productId": 123
    }
}
```

→ Returns product details data including images, related products, etc.

#### Transactional API

Separate API endpoints for critical flows:

* `POST /api/checkout` — Place order (authenticated)
* `POST /api/login`, `POST /api/register` — User authentication
* `POST /api/messages` — Send message

### Image Storage & Serving:

* Multi-image support per product
* Images stored on Cloud Storage (Cloudinary or Firebase Storage)
* Image URLs stored in database
* Seller can upload / delete / mark image as main

---

## 3️⃣ TECH STACK

### Backend:

* Laravel 11.x (headless API server — no Blade)
* PHP 8.2+

### Database:

* PostgreSQL (Supabase)

### Frontend:

* React (Vite / Create React App) SPA
* React Query (preferred) or Axios for API communication
* React Hook Form (optional — for forms)
* React Router (SPA navigation)
* Tailwind CSS 3.x

### Testing:

* Backend: PHPUnit
* Frontend: React Testing Library (optional)
* Postman
* Swagger (Optional — API documentation)

### Cloud Storage:

* Cloudinary (preferred, free quota generous)
* Firebase Storage (alternative)

### Deployment:

* Frontend: Vercel / Netlify / Render
* Backend: Render.com / DigitalOcean App Platform

### Version Control:

* Git + GitHub

### Project Management:

* Notion (Project Document + Planning + Timeline)
* Trello (Kanban Board: Todo → Doing → Done)

---

## 4️⃣ DEVELOPMENT METHODOLOGIES

### Agile Workflow:

* Work in 1-week sprints
* Plan tasks weekly using Trello Kanban
* Weekly personal review and iteration

### Development Principles:

* API Strategy:

  * Universal API Pattern (`/api/bff`) for all data-driven pages
  * Transactional REST API for specific flows (Authentication, Checkout, Messaging)
* Frontend-Backend fully decoupled architecture
* Test-driven development (TDD) for critical flows
* MVC architecture on backend
* Clean Code principles
* Responsive design (mobile-first)
* Image upload flow integrated into Cloud Storage

---

## 5️⃣ PROJECT TIMELINE

| Week   | Tasks                                                                                                                                       |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Week 1 | Setup Laravel API backend, Supabase (postgreSQL), Auth (Register/Login), RBAC, DB Schema                                                                    |
| Week 2 | Setup React project with Tailwind, Implement Universal API (`/api/bff`) basic flow, Product CRUD, Product Image Upload Flow, Product Browse |
| Week 3 | Cart, Checkout Flow, Orders, User Dashboard, Messaging Feature                                                                              |
| Week 4 | Admin Dashboard, User Management, Complete Universal API logic, Unit Testing, Deployment (FE & BE), Documentation Polish                    |

---

## 6️⃣ DATABASE DESIGN (ERD)

(no change — như bạn đang có → giữ nguyên ERD hiện tại là tốt)

---

## 7️⃣ TOOLS & ENVIRONMENT SETUP

### Backend Development:

* PHPStorm or VS Code
* PostgreSQL (Supabase)
* Postman
* Cloudinary Account (Free)
* Firebase Account (Alternative)

### Frontend Development:

* VS Code
* React + Vite / CRA
* React Query or Axios
* React Testing Library (optional)
* Postman

### Project Management:

* Notion (for Project Document + Planning Timeline)
* Trello (Kanban board)

### Deployment:

* Frontend: Vercel / Netlify / Render
* Backend: Render.com / DigitalOcean App Platform

---

## 8️⃣ RISKS & MITIGATION

| Risk                         | Mitigation                                                               |
| ---------------------------- | ------------------------------------------------------------------------ |
| Complex Cart logic           | Start with session-based cart, test with React Testing Library & PHPUnit |
| Role-based access mistakes   | Implement Laravel Policies + Middleware                                  |
| Messaging real-time too hard | Start with basic CRUD messaging first                                    |
| Image upload flow            | Use Cloudinary SDK with Laravel package                                  |
| Deployment Laravel tricky    | Use Render with Laravel guide                                            |
| Deployment React SPA tricky  | Use Vercel / Netlify with React router fallback                          |
| API synchronization issues   | Strict API contract, test Universal API with Postman                     |

---

## 9️⃣ SUCCESS METRICS

| Success Factor             | Criteria                                                           |
| -------------------------- | ------------------------------------------------------------------ |
| Functional App             | All key user flows working end-to-end                              |
| Code Quality               | Unit tests for critical flows                                      |
| UI/UX                      | Responsive and clear user interface                                |
| Documentation              | Complete README + API doc (Swagger / Postman collection)           |
| Deployment                 | Live React SPA and Laravel API server                              |
| Portfolio value            | Public GitHub repos for both FE & BE with Project Document & Board |
| Optimized API architecture | Universal API `/api/bff` powers all major pages                    |

---

## 🔐 SECURITY WITH TOKEN AUTHENTICATION (Laravel Sanctum)

To ensure secure and role-based access to the ShuttlePlay API endpoints, we'll implement token-based authentication using Laravel Sanctum.

### Why Token Authentication?

* To securely identify the authenticated user making API requests.
* To prevent unauthorized access, IDOR (Insecure Direct Object Reference), and role-based bypassing.
* To maintain secure and controlled interactions between the React frontend and Laravel backend.

### Laravel Sanctum Overview:

Laravel Sanctum provides a lightweight, easy-to-use solution for issuing and managing API tokens:

* User logs in → Laravel returns a unique token.
* React stores the token securely (e.g., in-memory storage or HTTP-only cookies).
* Every API request includes the token as a Bearer Token in the HTTP headers.
* Laravel verifies the token and retrieves the authenticated user.

### Implementation Steps:

#### Backend (Laravel)

**Step 1: Install Sanctum**

```shell
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**Step 2: Middleware Setup**

In `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ],
];
```

**Step 3: User Model Configuration**

Update `app/Models/User.php`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    // existing code...
}
```

**Step 4: Authentication API (Login)**

In `AuthController.php`:

```php
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response(['token' => $token], 200);
}
```

#### Frontend (React)

**Step 1: Store Token Securely**

```js
const login = async (email, password) => {
  const response = await axios.post('/api/login', { email, password });
  localStorage.setItem('authToken', response.data.token);
};
```

**Step 2: Include Token in API Requests**

```js
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('authToken')}`;
```

## 🐳 DOCKER FOR DEPLOYMENT

Docker enables easy and consistent deployment across environments (development, staging, production).

### Docker Setup

Create two Dockerfiles, one for backend and one for frontend.

### Backend Dockerfile (`backend/Dockerfile`)

```dockerfile
FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    && docker-php-ext-install

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-interaction --prefer-dist

RUN chown -R www-data:www-data /var/www/storage

EXPOSE 9000
```

### Frontend Dockerfile (`frontend/Dockerfile`)

```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package.json yarn.lock ./

RUN yarn install

COPY . ./

RUN yarn build

RUN yarn global add serve

EXPOSE 3000

CMD ["serve", "-s", "dist", "-l", "3000"]
```

### Docker Compose

version: '3.8'
services:
  backend:
    build:
      context: ./backend
    ports:
      - "8000:9000"
    environment:
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=laravel
      - DB_USERNAME=postgres
      - DB_PASSWORD=secret
    depends_on:
      - db

  frontend:
    build:
      context: ./frontend
    ports:
      - "3000:3000"

  db:
    image: postgres:16
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: laravel
    ports:
      - "5432:5432"
    volumes:
      - dbdata:/var/lib/postgresql/data

volumes:
  dbdata:


### Run Project with Docker

To build and run:

```shell
docker-compose build
docker-compose up
```

Now, frontend will be available at `http://localhost:3000` and backend at `http://localhost:8000`.

---

By integrating these detailed setups into our project document, the team can clearly understand how authentication, security, and deployment strategies work, significantly enhancing overall project security and maintainability.


---

## 🔤 ABBREVIATIONS

| Abbreviation / Term | Full form / Definition                                    |
| ------------------- | --------------------------------------------------------- |
| RBAC                | Role-Based Access Control                                 |
| CRUD                | Create, Read, Update, Delete                              |
| TDD                 | Test-Driven Development                                   |
| UI                  | User Interface                                            |
| UX                  | User Experience                                           |
| ERD                 | Entity Relationship Diagram                               |
| PK                  | Primary Key                                               |
| FK                  | Foreign Key                                               |
| CI/CD               | Continuous Integration / Continuous Deployment            |
| MVC                 | Model-View-Controller Architecture                        |
| Universal API       | Single `/api/bff` endpoint powering all data-driven pages |
| Transactional API   | Separate REST API endpoints for critical flows            |
| SPA                 | Single Page Application                                   |

