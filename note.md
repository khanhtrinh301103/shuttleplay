Dưới đây là mẫu **“Getting Started”** cập nhật dành riêng cho Windows PowerShell — bạn có thể dán vào `README.md` hoặc `SETUP.md` ở root repo để mọi dev pull về chỉ việc **PowerShell**:

````markdown
## 🚀 Getting Started (Windows PowerShell)

### 1. Prerequisites
- **Node.js** ≥ 16.x & **npm** (hoặc **yarn** ≥ 1.x)
- **PHP** ≥ 8.0 & **Composer**
- **Git**
- (Tùy chọn) Docker & Docker Compose

---

### 2. Clone repo & vào thư mục
```powershell
git clone git@github.com:you/shuttleplay.git
Set-Location shuttleplay
````

---

### 3. Backend (Laravel Headless API)

```powershell
# 3.1 Vào folder backend
Set-Location .\backend

# 3.2 Cài composer và copy .env
composer install
Copy-Item .env.example .env

# 3.3 Sinh APP_KEY
php artisan key:generate

# (Tương lai) chỉnh kết nối Supabase trong .env:
# DB_CONNECTION=pgsql
# DB_HOST=<YOUR_SUPABASE_HOST>
# DB_PORT=5432
# DB_DATABASE=<YOUR_SUPABASE_DB>
# DB_USERNAME=<YOUR_SUPABASE_USER>
# DB_PASSWORD=<YOUR_SUPABASE_PASSWORD>
 
# 3.4 Chạy server
php artisan serve --host=0.0.0.0 --port=8000
# → API sẵn sàng tại http://localhost:8000/api/
```

---

### 4. Frontend (React + TS + Tailwind)

Mở tab PowerShell mới:

```powershell
# 4.1 Về lại root rồi vào frontend
Set-Location ..\frontend

# 4.2 Cài npm packages
npm install
# hoặc: yarn install

# 4.3 (Nếu cần) điều chỉnh BFF URL trong .env
# Copy-Item .env.example .env
# Edit-Item .env (hoặc mở file bằng editor)
# REACT_APP_BFF_API_URL=http://localhost:8000/api

# 4.4 Chạy dev server
npm start
# hoặc: yarn start
# → Frontend chạy tại http://localhost:3000
```

---

### 5. Quick Test

* Gõ vào trình duyệt `http://localhost:3000` — nếu thấy giao diện “Hello World!”, frontend OK.
* Gọi API `GET http://localhost:8000/api/bff` — nếu postman nhận JSON, backend OK.

---

### 6. (Tùy chọn) Docker Compose

Nếu muốn triển khai container:

```powershell
Set-Location ..  # quay về root
docker-compose up --build
# → Backend: http://localhost:8000
# → Frontend: http://localhost:3000
```

---

> **Lưu ý**:
>
> * Mọi lệnh trên đều dùng **PowerShell** (Windows).
> * Nếu gặp lỗi “php not found”, kiểm tra PATH; tương tự với `composer` và `npm`.
> * Mọi thay đổi môi trường (Supabase, ports…) đều nằm trong file `.env` tương ứng.

Chúc mọi người pull về lập tức chạy được full-stack app mà không mất công cài lại môi trường! 🎉

```
```
