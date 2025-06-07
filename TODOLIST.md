## 🗂 BACKEND (Laravel API)

### Setup cơ bản:

* [ ] Setup Laravel Project
* [ ] Cài đặt Laravel Sanctum cho Token Authentication
* [ ] Cấu hình Middleware `RoleCheckMiddleware.php`
* [ ] Cấu hình `app/Models/` đầy đủ: User, Product, Order, Message, ProductImage
* [ ] Tạo migration + seeder DB

Bạn nói rất chính xác — trong `TODOLIST.md` hiện tại của bạn  phần **liệt kê các frameworks / libraries cần install** đang bị thiếu.
Đúng ra nên có một mục kiểu **Dependencies / Required Libraries** → để team biết ngay từ đầu **cần cài gì** cho Backend + Frontend.

→ Nhất là khi bạn dùng Docker, team sẽ cần build image → càng phải ghi rõ **cần các package nào**.

---

# 🚀 Mình sẽ giúp bạn viết thêm **mục "Dependencies" đầy đủ**, dạng giống như `package.json` để bạn dễ copy.

Bạn có thể thêm mục này **trước phần "Tổng kết flow làm việc"** trong `TODOLIST.md`.

---

## 🛠️ DEPENDENCIES / REQUIRED LIBRARIES

---

### 🗂 BACKEND (Laravel API)

**Framework:**

* `laravel/framework`: ^11.x

**Auth / Token:**

* `laravel/sanctum`: ^4.x

**Database:**

postgresql` (supabase)

**Helper:**

* `fruitcake/laravel-cors` (optional, CORS for API)

**Testing:**

* `phpunit/phpunit`

**Composer install example:**

```bash
composer require laravel/sanctum
composer require fruitcake/laravel-cors
composer install
```

---

### 🗂 FRONTEND (React SPA)

**Framework:**

* `react`: ^18.x
* `react-dom`: ^18.x
* `react-router-dom`: ^6.x (React Router v6)
* `react-scripts` (nếu dùng CRA)
* hoặc `vite` (nếu dùng Vite)

**API call:**

* `axios`: ^1.x

**State management & API caching:**

* `@tanstack/react-query`: ^5.x (React Query mới nhất)

**Form validation (optional, khuyên dùng):**

* `react-hook-form`: ^7.x

**UI styling:**

* `tailwindcss`: ^3.x
* `postcss`, `autoprefixer`

**Utilities:**

* `classnames` (optional, để quản lý class tailwind dễ hơn)
* `date-fns` (optional, format date đẹp)

**Testing:**

* `@testing-library/react`
* `@testing-library/jest-dom`
* `jest`

**Example package.json phần dependencies (React SPA):**

```json
"dependencies": {
  "react": "^18.x",
  "react-dom": "^18.x",
  "react-router-dom": "^6.x",
  "axios": "^1.x",
  "@tanstack/react-query": "^5.x",
  "react-hook-form": "^7.x",
  "tailwindcss": "^3.x",
  "classnames": "^2.x",
  "date-fns": "^2.x"
},
"devDependencies": {
  "@testing-library/react": "^14.x",
  "@testing-library/jest-dom": "^6.x",
  "jest": "^29.x",
  "postcss": "^8.x",
  "autoprefixer": "^10.x"
}
```

---

### 🗂 DOCKER RELATED

**Images cần:**

* `php:8.2-fpm` → Laravel backend
* `postgres:14` → Database
* `node:18-alpine` → React frontend


### API Core:

* [ ] API `/api/login` → trả token
* [ ] API `/api/register` → trả token
* [ ] API `/api/bff` → xử lý Universal API (RoleAware)

  * [ ] Page `HomePage`
  * [ ] Page `ProductDetail`
  * [ ] Page `UserDashboard` (Customer)
  * [ ] Page `SellerDashboard`
  * [ ] Page `AdminDashboard`
* [ ] API `/api/checkout` → tạo đơn hàng
* [ ] API `/api/messages` → gửi/nhận message

### Middleware + Validation:

* [ ] Viết `RoleCheckMiddleware.php`
* [ ] Viết `Requests/` Validation:

  * [ ] LoginRequest
  * [ ] RegisterRequest
  * [ ] CheckoutRequest
  * [ ] MessageRequest
* [ ] Viết `Resources/` format API response:

  * [ ] OrderResource
  * [ ] ProductResource
  * [ ] MessageResource

### Business Logic (Services/):

* [ ] OrderService → handle order business logic
* [ ] PaymentService → handle payment logic (nếu có)

### Testing:

* [ ] Unit Test Auth
* [ ] Unit Test BFF Controller
* [ ] Unit Test Checkout
* [ ] Unit Test Message

---

## 🗂 FRONTEND (React SPA)

### Setup cơ bản:

* [ ] Setup React Project (Vite / CRA)
* [ ] Cài đặt React Router v6
* [ ] Cài đặt React Query + Axios
* [ ] Setup Folder Structure theo Project Structure mới

### Auth:

* [ ] `src/api/authApi.js` → login/register API call
* [ ] `src/hooks/useAuth.js` → manage auth state
* [ ] `src/contexts/AuthContext.jsx` → global auth context
* [ ] `src/router/PrivateRoute.jsx` → chặn page cần login
* [ ] `src/router/RoleBasedRoute.jsx` → chặn page theo role

### Universal API:

* [ ] `src/api/bffApi.js` → chuẩn hóa Universal API call
* [ ] `src/hooks/useBffQuery.js` → custom hook gọi BFF API

### UI Components:

* [ ] Build components/common:

  * [ ] Button
  * [ ] Card
  * [ ] Modal
  * [ ] Header
  * [ ] Footer
* [ ] Build layout per role:

  * [ ] MainLayout
  * [ ] AdminLayout
  * [ ] SellerLayout
  * [ ] CustomerLayout

### Pages per role:

* [ ] `HomePage.jsx`
* [ ] `auth/Login.jsx`, `auth/Register.jsx`

#### Admin:

* [ ] AdminDashboard.jsx
* [ ] UserManagement.jsx
* [ ] OrderManagement.jsx

#### Seller:

* [ ] SellerDashboard.jsx
* [ ] ProductManagement.jsx
* [ ] SellerOrders.jsx

#### Customer:

* [ ] CustomerDashboard.jsx
* [ ] ProductList.jsx
* [ ] ProductDetail.jsx
* [ ] CartPage.jsx
* [ ] OrderHistory.jsx

### Business Logic (Services/):

* [ ] `CartService.js`
* [ ] `OrderService.js`

### Utility:

* [ ] `utils/formatDate.js`
* [ ] `utils/formatCurrency.js`
* [ ] `utils/validators.js`

### Testing:

* [ ] Test API calls (BFF, Checkout, Auth)
* [ ] Test RoleBasedRoute
* [ ] Test Context (AuthContext, CartContext)

---

## 🗂 DEPLOYMENT

### Docker:

* [ ] Viết Dockerfile cho Backend
* [ ] Viết Dockerfile cho Frontend
* [ ] Viết docker-compose.yml → chạy toàn bộ project
* [ ] Test deploy local bằng Docker Compose
* [ ] Setup môi trường staging / production

---

# 🚩 Tổng kết flow làm việc:

1️⃣ Setup Backend → API core → Middleware + Validation
2️⃣ Setup Frontend → Auth → BFF API → UI
3️⃣ Viết từng page per role
4️⃣ Viết test
5️⃣ Setup Docker để deploy dễ dàng
6️⃣ Final review + deploy staging


