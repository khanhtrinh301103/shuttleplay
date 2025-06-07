ShuttlePlay/
├── backend/                                --> Laravel API server (Headless API Only)
│   ├── app/
│   │   ├── Http/                          
│   │   │   ├── Controllers/                --> Chứa toàn bộ các API Controller
│   │   │   │   ├── Api/
│   │   │   │   │   ├── BffController.php          --> Xử lý Universal API (/api/bff)
│   │   │   │   │   ├── AuthController.php         --> Xử lý Login/Register → trả token
│   │   │   │   │   ├── CheckoutController.php     --> Xử lý luồng Checkout → tạo đơn hàng
│   │   │   │   │   ├── MessageController.php      --> Xử lý Messaging → gửi/nhận message
│   │   │   │   │   ├── UserController.php         --> Admin quản lý User (CRUD user)
│   │   │   ├── Middleware/                 
│   │   │   │   ├── RoleCheckMiddleware.php        --> Middleware kiểm tra quyền Role khi call /api/bff
│   │   │   ├── Requests/                   --> Chứa Form Validation cho các API (LoginRequest, CheckoutRequest, ...)
│   │   │   ├── Resources/                  --> Chuẩn hóa API response (OrderResource, ProductResource, ...)
│   │   ├── Models/                         --> Chứa các model chính (User.php, Product.php, Order.php, Message.php, ...)
│   │   ├── Services/                       --> Tầng xử lý Business Logic riêng → tránh fat controller
│   │   │   ├── OrderService.php                  --> Xử lý nghiệp vụ đặt hàng
│   │   │   ├── PaymentService.php                --> Xử lý nghiệp vụ thanh toán (nếu có)
│   ├── config/                             --> Cấu hình Laravel + package
│   ├── database/
│   │   ├── migrations/                     --> Migration tạo DB schema
│   │   ├── seeders/                        --> Seeder để tạo sẵn dữ liệu mẫu
│   ├── routes/
│   │   ├── api.php                         --> Define các API route (Universal API + Transactional API)
│   ├── tests/                              --> Unit tests với PHPUnit
│   ├── public/                             --> Public entry Laravel (chứa index.php + asset public nếu có)
│   ├── .env                                --> Environment variable cho Laravel API
│   ├── composer.json                       --> Manage Laravel packages (dependency)
│
└── frontend/                               --> React SPA project (Headless UI, gọi API riêng)
    ├── public/                             --> Static file public (favicon, index.html)
    ├── src/
    │   ├── api/                            --> Nơi tập trung các file call API
    │   │   ├── bffApi.js                        --> Gọi Universal API /api/bff
    │   │   ├── authApi.js                       --> Gọi Login/Register API
    │   │   ├── messageApi.js                    --> Gọi Messaging API
    │   │   ├── checkoutApi.js                   --> Gọi Checkout API
    │   ├── components/                     --> Các UI component tái sử dụng
    │   │   ├── common/                          --> Các component chung (Button, Card, Modal, Header, Footer)
    │   │   ├── layout/                          --> Các Layout cho từng Role (MainLayout, AdminLayout, SellerLayout, CustomerLayout)
    │   ├── pages/                          --> Các Page của App (theo Route)
    │   │   ├── admin/                           --> Các Page cho Admin role
    │   │   │   ├── AdminDashboard.jsx
    │   │   │   ├── UserManagement.jsx
    │   │   │   ├── OrderManagement.jsx
    │   │   ├── seller/                          --> Các Page cho Seller role
    │   │   │   ├── SellerDashboard.jsx
    │   │   │   ├── ProductManagement.jsx
    │   │   │   ├── SellerOrders.jsx
    │   │   ├── customer/                        --> Các Page cho Customer role
    │   │   │   ├── CustomerDashboard.jsx
    │   │   │   ├── ProductList.jsx
    │   │   │   ├── ProductDetail.jsx
    │   │   │   ├── CartPage.jsx
    │   │   │   ├── OrderHistory.jsx
    │   │   ├── auth/                            --> Page Login, Register
    │   │   │   ├── Login.jsx
    │   │   │   ├── Register.jsx
    │   │   ├── HomePage.jsx                     --> Public HomePage (không cần Login)
    │   ├── hooks/                           --> Custom React hooks → tách logic ra dễ dùng
    │   │   ├── useAuth.js                       --> Hook quản lý auth state (user info, token)
    │   │   ├── useCart.js                       --> Hook quản lý Cart state
    │   │   ├── useBffQuery.js                   --> Hook gọi Universal API /api/bff
    │   │   ├── useRoleGuard.js                  --> Hook để bảo vệ route theo Role
    │   ├── contexts/                        --> React Context → Global State toàn App
    │   │   ├── AuthContext.jsx                  --> Lưu User Info + Token toàn App
    │   │   ├── CartContext.jsx                  --> Lưu Cart state toàn App
    │   ├── services/                        --> Business Logic (tầng dịch vụ riêng)
    │   │   ├── CartService.js                   --> Xử lý logic Cart (ex: tính tổng tiền, validate cart)
    │   │   ├── OrderService.js                  --> Xử lý logic Đơn hàng
    │   ├── utils/                           --> Các function tiện ích dùng chung
    │   │   ├── formatDate.js
    │   │   ├── formatCurrency.js
    │   │   ├── validators.js                   --> Validate form input
    │   ├── constants/                       --> Define constants dùng chung toàn app (giúp tránh hardcode)
    │   │   ├── roles.js                         --> Define Role trong app (admin, seller, customer)
    │   │   ├── orderStatus.js                   --> Define Status của Order (pending, shipped, paid, ...)
    │   ├── router/                          --> React Router config + Route Guard
    │   │   ├── PrivateRoute.jsx                 --> Route guard cho page cần login
    │   │   ├── RoleBasedRoute.jsx               --> Route guard theo Role (Admin, Seller, Customer)
    │   │   ├── AppRouter.jsx                    --> Central Router setup toàn App
    │   ├── App.jsx                          --> App entry (React Router + Provider context đặt ở đây)
    │   ├── index.js                        --> Entry file React (render React App)
    ├── package.json                       --> Quản lý dependency cho React App
    ├── .env                               --> Config URL API (REACT_APP_API_URL)
    ├── README.md                          --> README Project
