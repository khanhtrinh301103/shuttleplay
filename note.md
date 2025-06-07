Dưới đây là đoạn **“Getting Started”** bạn có thể thêm vào README (hoặc một file `SETUP.md`) ở root repo. Các dev chỉ cần clone về, chạy theo các bước này là có đủ môi trường backend + frontend.

````markdown
## 🚀 Getting Started

### 1. Prerequisites
- **Node.js** ≥ 16.x, **npm** (hoặc **yarn** ≥ 1.x)
- **PHP** ≥ 8.0, **Composer**
- **Git**
- (Tùy chọn) Docker & Docker Compose nếu muốn chạy container

### 2. Clone repository
```bash
git clone git@github.com:you/shuttleplay.git
cd shuttleplay
````

---

### 3. Cài đặt & chạy Backend (Laravel Headless API)

1. Di chuyển vào thư mục backend:

   ```bash
   cd backend
   ```

2. Sao chép file môi trường và sinh APP\_KEY:

   ```bash
   cp .env.example .env
   composer install
   php artisan key:generate
   ```

3. (Tương lai) Cấu hình kết nối database Supabase trong `.env`:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=<SUPABASE_HOST>
   DB_PORT=5432
   DB_DATABASE=<SUPABASE_DB>
   DB_USERNAME=<SUPABASE_USER>
   DB_PASSWORD=<SUPABASE_PASSWORD>
   ```

4. Chạy server:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

   → Backend API sẵn sàng tại **[http://localhost:8000/api/](http://localhost:8000/api/)**

---

### 4. Cài đặt & chạy Frontend (React + TS + Tailwind)

1. Mở terminal mới, về thư mục gốc và chuyển vào frontend:

   ```bash
   cd ../frontend
   ```

2. Cài dependencies:

   ```bash
   npm install
   # hoặc yarn install
   ```

3. (Nếu có thay đổi BFF URL) Tùy chỉnh `.env`:

   ```env
   REACT_APP_BFF_API_URL=http://localhost:8000/api
   ```

4. Chạy dev server:

   ```bash
   npm start
   # hoặc yarn start
   ```

   → Frontend sẵn sàng tại **[http://localhost:3000](http://localhost:3000)**

---

### 5. Thử nghiệm nhanh

* Mở trình duyệt tới **[http://localhost:3000](http://localhost:3000)** để kiểm tra UI
* Gọi API thử tại **[http://localhost:8000/api/bff](http://localhost:8000/api/bff)** (GET) → nên nhận JSON response

---

### 6. Docker (Tùy chọn)

Nếu muốn chạy bằng Docker Compose:

1. Tạo file `.env.docker` hoặc bổ sung biến môi trường trong `docker-compose.yml`
2. Chạy:

   ```bash
   docker-compose up --build
   ```
3. Truy cập:

   * Backend: [http://localhost:8000](http://localhost:8000)
   * Frontend: [http://localhost:3000](http://localhost:3000)

---

### 7. FAQs

* **Lỗi “APP\_KEY not set”?** Chạy `php artisan key:generate`
* **Không thấy Tailwind chạy?** Kiểm tra `postcss.config.js` & `tailwind.config.js` trong `frontend/`
* **Muốn chạy test?** Chưa có test suite, dev sẽ tự thêm sau này.

---

> **Chúc các bạn dev môi trường suôn sẻ!** 🚀

```
```
