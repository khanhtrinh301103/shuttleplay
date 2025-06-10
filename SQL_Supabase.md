-- Xóa ENUM cũ nếu đã tồn tại để tránh lỗi khi tạo lại
DROP TYPE IF EXISTS user_role;

-- 1. Định nghĩa kiểu ENUM cho vai trò người dùng
-- Tạo kiểu dữ liệu user_role với các giá trị hợp lệ: customer, seller, admin
CREATE TYPE user_role AS ENUM ('customer', 'seller', 'admin');

-- 2. Bảng users: lưu thông tin người dùng hệ thống
CREATE TABLE users (
  id              SERIAL         PRIMARY KEY,            -- Khóa chính tự động tăng
  name            VARCHAR(255),                            -- Tên người dùng
  email           VARCHAR(255)    UNIQUE NOT NULL,         -- Email, bắt buộc và duy nhất
  password        VARCHAR(255)    NOT NULL,                -- Mật khẩu (đã mã hóa)
  role            user_role       NOT NULL DEFAULT 'customer', -- Vai trò, mặc định là customer
  phone           VARCHAR(50),                             -- Số điện thoại
  address         TEXT,                                     -- Địa chỉ thường trú
  created_at      TIMESTAMP WITH TIME ZONE DEFAULT now(),   -- Thời điểm tạo bản ghi
  updated_at      TIMESTAMP WITH TIME ZONE DEFAULT now()    -- Thời điểm cập nhật cuối cùng
);

-- 3. Bảng categories: nhóm sản phẩm
CREATE TABLE categories (
  id         SERIAL       PRIMARY KEY,                   -- Khóa chính
  name       VARCHAR(255) NOT NULL,                      -- Tên danh mục
  slug       VARCHAR(255) UNIQUE NOT NULL,               -- Đường dẫn thân thiện, duy nhất
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),      -- Ngày tạo
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()       -- Ngày cập nhật
);

-- 4. Bảng products: chi tiết sản phẩm
CREATE TABLE products (
  id              SERIAL       PRIMARY KEY,             -- Khóa chính
  name            VARCHAR(255) NOT NULL,                 -- Tên sản phẩm
  description     TEXT,                                   -- Mô tả chi tiết
  price           DECIMAL(10,2) NOT NULL CHECK (price >= 0), -- Giá, không âm
  stock_qty       INTEGER       NOT NULL CHECK (stock_qty >= 0), -- Số lượng tồn kho, không âm
  category_id     INTEGER       NOT NULL REFERENCES categories(id) ON DELETE RESTRICT, -- Khóa ngoại tới categories
  seller_id       INTEGER       NOT NULL REFERENCES users(id)      ON DELETE CASCADE, -- Người bán, xóa sản phẩm khi người bán bị xóa
  published       BOOLEAN       NOT NULL DEFAULT false,   -- Trạng thái đã công bố
  created_at      TIMESTAMP WITH TIME ZONE DEFAULT now(), -- Ngày tạo
  updated_at      TIMESTAMP WITH TIME ZONE DEFAULT now()  -- Ngày cập nhật
);

-- 5. Bảng product_images: hình ảnh sản phẩm
CREATE TABLE product_images (
  id         SERIAL       PRIMARY KEY,                -- Khóa chính
  product_id INTEGER       NOT NULL REFERENCES products(id) ON DELETE CASCADE, -- Sản phẩm liên quan
  image_url  VARCHAR(255)  NOT NULL,                   -- URL hình ảnh
  is_main    BOOLEAN       NOT NULL DEFAULT false,     -- Đánh dấu ảnh chính
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),   -- Ngày thêm ảnh
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()    -- Ngày cập nhật
);

-- 6. Bảng shopping_carts và cart_items: giỏ hàng và chi tiết giỏ hàng
CREATE TABLE shopping_carts (
  id          SERIAL PRIMARY KEY,                     -- Khóa chính giỏ hàng
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Chủ giỏ hàng
  created_at  TIMESTAMP WITH TIME ZONE DEFAULT now(), -- Ngày tạo giỏ hàng
  updated_at  TIMESTAMP WITH TIME ZONE DEFAULT now()  -- Ngày cập nhật
);

CREATE TABLE cart_items (
  id         SERIAL       PRIMARY KEY,                -- Khóa chính mục giỏ hàng
  cart_id    INTEGER      NOT NULL REFERENCES shopping_carts(id) ON DELETE CASCADE, -- Giỏ hàng chứa mục
  product_id INTEGER      NOT NULL REFERENCES products(id) ON DELETE RESTRICT, -- Sản phẩm được thêm
  quantity   INTEGER      NOT NULL CHECK (quantity > 0), -- Số lượng (>0)
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now()   -- Ngày thêm mục
);

-- 7. Bảng user_addresses: lưu nhiều địa chỉ cho user
CREATE TABLE user_addresses (
  id          SERIAL       PRIMARY KEY,               -- Khóa chính địa chỉ
  user_id     INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Thuộc về user nào
  label       VARCHAR(50),                          -- Nhãn (nhà riêng, cơ quan...)
  address     TEXT         NOT NULL,                 -- Địa chỉ chi tiết
  is_default  BOOLEAN      NOT NULL DEFAULT false,    -- Địa chỉ mặc định
  created_at  TIMESTAMP WITH TIME ZONE DEFAULT now(), -- Ngày tạo
  updated_at  TIMESTAMP WITH TIME ZONE DEFAULT now()  -- Ngày cập nhật
);

-- 8. Bảng shipping_options: phương án vận chuyển
CREATE TABLE shipping_options (
  id                      SERIAL       PRIMARY KEY,   -- Khóa chính
  name                    VARCHAR(100) NOT NULL,        -- Tên phương án (Giao tiêu chuẩn...)
  fee                     DECIMAL(8,2) NOT NULL CHECK (fee >= 0), -- Phí vận chuyển
  estimated_delivery_days SMALLINT     NOT NULL         -- Thời gian giao dự kiến (ngày)
);

-- 9. Bảng orders: đơn hàng
CREATE TABLE orders (
  id                  SERIAL       PRIMARY KEY,      -- Khóa chính đơn hàng
  user_id             INTEGER      NOT NULL REFERENCES users(id) ON DELETE SET NULL, -- Người mua, set NULL nếu user bị xóa
  total_amount        DECIMAL(12,2) NOT NULL CHECK (total_amount >= 0), -- Tổng tiền
  status              VARCHAR(50)   NOT NULL DEFAULT 'pending', -- Trạng thái (pending, paid, shipped...)
  order_address_id    INTEGER      REFERENCES user_addresses(id) ON DELETE SET NULL, -- Địa chỉ giao
  shipping_option_id  INTEGER      REFERENCES shipping_options(id), -- Phương án vận chuyển
  discount_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,  -- Giảm giá (nếu có)
  created_at          TIMESTAMP WITH TIME ZONE DEFAULT now(), -- Ngày tạo
  updated_at          TIMESTAMP WITH TIME ZONE DEFAULT now()  -- Ngày cập nhật
);

-- 10. Bảng order_items: chi tiết sản phẩm trong đơn hàng
CREATE TABLE order_items (
  id                  SERIAL       PRIMARY KEY,     -- Khóa chính
  order_id            INTEGER      NOT NULL REFERENCES orders(id) ON DELETE CASCADE, -- Đơn hàng chứa mục
  product_id          INTEGER      NOT NULL REFERENCES products(id) ON DELETE RESTRICT, -- Sản phẩm đặt
  quantity            INTEGER      NOT NULL CHECK (quantity > 0), -- Số lượng (>0)
  price_at_order_time DECIMAL(10,2) NOT NULL CHECK (price_at_order_time >= 0), -- Giá khi đặt
  created_at          TIMESTAMP WITH TIME ZONE DEFAULT now(), -- Ngày tạo mục
  updated_at          TIMESTAMP WITH TIME ZONE DEFAULT now()  -- Ngày cập nhật
);

-- 11. Bảng payments: thông tin thanh toán
CREATE TABLE payments (
  id             SERIAL       PRIMARY KEY,           -- Khóa chính thanh toán
  order_id       INTEGER      NOT NULL REFERENCES orders(id) ON DELETE CASCADE, -- Thuộc đơn hàng
  method         VARCHAR(50)  NOT NULL,               -- Phương thức (card, PayPal...)
  status         VARCHAR(50)  NOT NULL DEFAULT 'pending', -- Trạng thái thanh toán
  transaction_id VARCHAR(255) UNIQUE,                 -- Mã giao dịch từ cổng
  amount         DECIMAL(12,2) NOT NULL CHECK (amount >= 0), -- Số tiền đã thanh toán
  paid_at        TIMESTAMP WITH TIME ZONE            -- Thời điểm thanh toán
);

-- 12. Bảng shipments: theo dõi vận chuyển đơn hàng
CREATE TABLE shipments (
  id              SERIAL       PRIMARY KEY,            -- Khóa chính vận chuyển
  order_id        INTEGER      NOT NULL REFERENCES orders(id) ON DELETE CASCADE, -- Đơn hàng vận chuyển
  carrier         VARCHAR(100),                         -- Đơn vị vận chuyển
  tracking_number VARCHAR(100),                         -- Mã theo dõi
  status          VARCHAR(50)  NOT NULL DEFAULT 'preparing', -- Trạng thái (preparing, shipped, delivered)
  shipped_at      TIMESTAMP WITH TIME ZONE,            -- Thời điểm gửi
  delivered_at    TIMESTAMP WITH TIME ZONE             -- Thời điểm giao
);

-- 13. Bảng messages: chat giữa người mua và người bán
CREATE TABLE messages (
  id          SERIAL       PRIMARY KEY,               -- Khóa chính tin nhắn
  sender_id   INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Người gửi
  receiver_id INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Người nhận
  product_id  INTEGER      REFERENCES products(id) ON DELETE SET NULL, -- Sản phẩm liên quan (nếu có)
  content     TEXT         NOT NULL,                   -- Nội dung tin nhắn
  read_status BOOLEAN      NOT NULL DEFAULT false,    -- Đã đọc hay chưa
  created_at  TIMESTAMP WITH TIME ZONE DEFAULT now(), -- Ngày gửi
  updated_at  TIMESTAMP WITH TIME ZONE DEFAULT now()  -- Ngày cập nhật
);

-- 14. Bảng reviews: đánh giá sản phẩm
CREATE TABLE reviews (
  id          SERIAL       PRIMARY KEY,               -- Khóa chính đánh giá
  product_id  INTEGER      NOT NULL REFERENCES products(id) ON DELETE CASCADE, -- Sản phẩm được đánh giá
  user_id     INTEGER      NOT NULL REFERENCES users(id) ON DELETE SET NULL, -- Người đánh giá
  rating      INTEGER      NOT NULL CHECK (rating BETWEEN 1 AND 5), -- Xếp hạng từ 1 tới 5
  comment     TEXT,                                    -- Nội dung nhận xét
  created_at  TIMESTAMP WITH TIME ZONE DEFAULT now(),  -- Ngày đánh giá
  updated_at  TIMESTAMP WITH TIME ZONE DEFAULT now()   -- Ngày cập nhật
);

-- 15. Bảng wishlists và wishlist_items: danh sách yêu thích
CREATE TABLE wishlists (
  id         SERIAL PRIMARY KEY,                        -- Khóa chính wishlist
  user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Chủ wishlist
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now()      -- Ngày tạo
);

CREATE TABLE wishlist_items (
  id          SERIAL       PRIMARY KEY,               -- Khóa chính mục wishlist
  wishlist_id INTEGER      NOT NULL REFERENCES wishlists(id) ON DELETE CASCADE, -- Wishlist chứa mục
  product_id  INTEGER      NOT NULL REFERENCES products(id) ON DELETE RESTRICT, -- Sản phẩm yêu thích
  created_at  TIMESTAMP WITH TIME ZONE DEFAULT now()    -- Ngày thêm
);

-- 16. Bảng notifications: thông báo hệ thống
CREATE TABLE notifications (
  id          SERIAL       PRIMARY KEY,               -- Khóa chính thông báo
  user_id     INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Người nhận thông báo
  type        VARCHAR(50)  NOT NULL,                   -- Loại (order_update, promotion...)
  message     TEXT         NOT NULL,                   -- Nội dung
  read_status BOOLEAN      NOT NULL DEFAULT false,    -- Đã đọc
  created_at  TIMESTAMP WITH TIME ZONE DEFAULT now()   -- Ngày tạo
);

-- 17. Bảng promotions và order_promotions: quản lý khuyến mãi
CREATE TABLE promotions (
  id           SERIAL       PRIMARY KEY,             -- Khóa chính promotion
  code         VARCHAR(50)  UNIQUE NOT NULL,         -- Mã khuyến mãi
  description  TEXT,                                  -- Mô tả chương trình
  discount_pct SMALLINT     CHECK (discount_pct BETWEEN 0 AND 100), -- Phần trăm giảm
  valid_from   TIMESTAMP WITH TIME ZONE,               -- Bắt đầu hiệu lực
  valid_to     TIMESTAMP WITH TIME ZONE,               -- Kết thúc hiệu lực
  active       BOOLEAN      NOT NULL DEFAULT true,    -- Trạng thái kích hoạt
  created_at   TIMESTAMP WITH TIME ZONE DEFAULT now(),-- Ngày tạo
  updated_at   TIMESTAMP WITH TIME ZONE DEFAULT now() -- Ngày cập nhật
);

CREATE TABLE order_promotions (
  id            SERIAL       PRIMARY KEY,          -- Khóa chính liên kết đơn hàng và khuyến mãi
  order_id      INTEGER      NOT NULL REFERENCES orders(id) ON DELETE CASCADE, -- Đơn hàng áp dụng
  promotion_id  INTEGER      NOT NULL REFERENCES promotions(id) ON DELETE RESTRICT, -- Khuyến mãi sử dụng
  applied_at    TIMESTAMP WITH TIME ZONE DEFAULT now() -- Thời điểm áp dụng
);

-- 18. Trigger tự động cập nhật cột updated_at khi bản ghi thay đổi
CREATE OR REPLACE FUNCTION trigger_set_timestamp()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = now();        -- Gán thời điểm hiện tại vào updated_at
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE
  tbl TEXT;
BEGIN
  -- Lặp qua các bảng có cột updated_at để tạo trigger
  FOR tbl IN
    SELECT tablename FROM pg_tables WHERE schemaname = 'public'
      AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = tablename AND column_name = 'updated_at')
  LOOP
    EXECUTE format($f$
      DROP TRIGGER IF EXISTS %1$I_set_timestamp ON %1$I;
      CREATE TRIGGER %1$I_set_timestamp
        BEFORE UPDATE ON %1$I FOR EACH ROW EXECUTE FUNCTION trigger_set_timestamp();
    $f$, tbl);
  END LOOP;
END$$;

-- 19. Các index bổ sung để tối ưu hiệu năng truy vấn
CREATE INDEX idx_products_category       ON products(category_id);
CREATE INDEX idx_products_seller         ON products(seller_id);
CREATE INDEX idx_orders_user             ON orders(user_id);
CREATE INDEX idx_order_items_order       ON order_items(order_id);
CREATE INDEX idx_order_items_product     ON order_items(product_id);
CREATE INDEX idx_shipping_options_fee    ON shipping_options(fee);
CREATE INDEX idx_user_addresses_user     ON user_addresses(user_id);
CREATE INDEX idx_promotions_code         ON promotions(code);
CREATE INDEX idx_order_promotions_order  ON order_promotions(order_id);

-- 20. Views hỗ trợ Backend-for-Frontend (BFF)

-- 20.1 View cho seller: liệt kê sản phẩm với tên category
CREATE VIEW vw_seller_products AS
SELECT
  p.seller_id,
  p.id            AS product_id,
  p.name,
  p.description,
  p.price,
  p.stock_qty,
  c.name          AS category_name,
  p.published,
  p.created_at
FROM products p
JOIN categories c ON p.category_id = c.id;

-- 20.2 View cho customer: lịch sử đơn hàng kèm chi tiết items dưới dạng JSON
CREATE VIEW vw_customer_orders AS
SELECT
  o.user_id         AS customer_id,
  o.id              AS order_id,
  o.total_amount,
  o.status,
  ua.address        AS shipping_address,
  so.name           AS shipping_method,
  so.fee            AS shipping_fee,
  o.discount_amount,
  o.created_at      AS order_date,
  json_agg(
    json_build_object(
      'product_id', oi.product_id,
      'quantity',   oi.quantity,
      'price',      oi.price_at_order_time
    ) ORDER BY oi.id
  ) AS items
FROM orders o
LEFT JOIN user_addresses ua ON o.order_address_id = ua.id
LEFT JOIN shipping_options so ON o.shipping_option_id = so.id
JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.user_id, o.id, o.total_amount, o.status, ua.address, so.name, so.fee, o.discount_amount, o.created_at;

-- 20.3 View cho admin: tổng quan số liệu hệ thống
CREATE VIEW vw_admin_overview AS
SELECT
  (SELECT COUNT(*) FROM users)                          AS total_users,
  (SELECT COUNT(*) FROM users WHERE role = 'seller')    AS total_sellers,
  (SELECT COUNT(*) FROM users WHERE role = 'customer')  AS total_customers,
  (SELECT COUNT(*) FROM products)                       AS total_products,
  (SELECT COUNT(*) FROM products WHERE published)       AS published_products,
  (SELECT COUNT(*) FROM orders)                         AS total_orders,
  (SELECT COUNT(*) FROM orders WHERE status = 'pending')   AS pending_orders,
  (SELECT COUNT(*) FROM orders WHERE status = 'paid')      AS paid_orders,
  (SELECT COUNT(*) FROM orders WHERE status = 'shipped')   AS shipped_orders,
  (SELECT COUNT(*) FROM orders WHERE status = 'delivered') AS delivered_orders,
  (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN ('shipped','delivered'))
                                                        AS total_revenue;
