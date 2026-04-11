# 🍽️ FoodieExpress - Full Stack Food Ordering System
## BCA College Project | XAMPP + PHP + MySQL

---

## 📁 PROJECT FOLDER STRUCTURE

```
FoodieExpress/
├── index.php              ← Homepage
├── login.php              ← User Login
├── register.php           ← User Registration
├── dashboard.php          ← User Dashboard
├── menu.php               ← Food Menu (Search + Filter)
├── restaurants.php        ← Restaurant Listing
├── ai-diet.php            ← AI Diet & Health Assistant
├── surprise.php           ← Surprise Me Feature
├── group-order.php        ← Group Ordering + Bill Split
├── emergency.php          ← Emergency Food Mode
├── checkout.php           ← Checkout Page
├── orders.php             ← My Orders + Tracking
├── feedback.php           ← Feedback Submission
│
├── css/
│   └── style.css          ← Main Stylesheet (Silver & White Theme)
│
├── js/
│   └── main.js            ← JavaScript (Cart, Toast, Animations)
│
├── php/
│   ├── cart.php           ← Cart AJAX Handler
│   ├── surprise.php       ← Surprise Me AJAX Handler
│   └── logout.php         ← Logout Handler
│
├── includes/
│   ├── config.php         ← Database Config + Session
│   ├── header.php         ← Navigation Header
│   └── footer.php         ← Footer + Cart Panel
│
├── admin/
│   ├── index.php          ← Admin Dashboard (All Features)
│   ├── login.php          ← Admin Login
│   └── logout.php         ← Admin Logout
│
└── database.sql           ← Complete MySQL Schema + Sample Data
```

---

## ⚙️ HOW TO RUN ON XAMPP

### Step 1: Install XAMPP
- Download from: https://www.apachefriends.org/
- Install and open XAMPP Control Panel
- Start **Apache** and **MySQL**

### Step 2: Place Project Files
```
Copy entire FoodieExpress folder to:
C:\xampp\htdocs\FoodieExpress\
```

### Step 3: Create Database
1. Open browser → go to `http://localhost/phpmyadmin`
2. Click **"New"** → Create database named: `foodieexpress`
3. Select the database → Click **"Import"**
4. Choose file: `FoodieExpress/database.sql`
5. Click **"Go"** — database and sample data will be created

### Step 4: Configure (if needed)
Open `includes/config.php` and check:
```php
define('DB_HOST', 'localhost');     // Usually localhost
define('DB_USER', 'root');          // XAMPP default: root
define('DB_PASS', '');              // XAMPP default: empty
define('DB_NAME', 'foodieexpress');
define('SITE_URL', 'http://localhost/FoodieExpress');
```

### Step 5: Open in Browser
```
http://localhost/FoodieExpress/
```

---

## 🔐 LOGIN CREDENTIALS

### User Account (Create New):
- Go to: `http://localhost/FoodieExpress/register.php`
- Register with any email and password

### Admin Panel:
- URL: `http://localhost/FoodieExpress/admin/`
- Username: `admin`
- Password: `password`

---

## 🎯 FEATURES IMPLEMENTED

| Feature | Status |
|---------|--------|
| User Registration & Login | ✅ Complete |
| Browse Restaurants | ✅ Complete |
| Food Menu + Search + Filter | ✅ Complete |
| Add to Cart (AJAX) | ✅ Complete |
| Checkout & Place Order | ✅ Complete |
| Order History & Tracking | ✅ Complete |
| AI Diet & Health Assistant | ✅ Complete |
| Surprise Me Feature | ✅ Complete |
| Group Ordering + Bill Split | ✅ Complete |
| Emergency Food Mode | ✅ Complete |
| Feedback System | ✅ Complete |
| Ratings Display | ✅ Complete |
| Admin Dashboard | ✅ Complete |
| Admin: Manage Restaurants | ✅ Complete |
| Admin: Manage Food Items | ✅ Complete |
| Admin: Manage Users | ✅ Complete |
| Admin: Manage Orders | ✅ Complete |
| Admin: View Feedback | ✅ Complete |
| Responsive Design | ✅ Complete |
| Mobile Hamburger Menu | ✅ Complete |

---

## 🗄️ DATABASE TABLES

| Table | Purpose |
|-------|---------|
| users | Customer accounts |
| admin | Admin accounts |
| restaurants | Restaurant details |
| food_items | Food menu items |
| cart | Shopping cart |
| orders | Placed orders |
| order_items | Items in each order |
| feedback | User feedback |
| ratings | Food ratings |
| group_orders | Group order sessions |
| group_order_members | Members of group orders |

---

## 🎨 DESIGN

- **Theme Colors**: Silver (#B8BEC7) and White (#FFFFFF)
- **Accent Color**: Red (#E63946) for CTAs and highlights
- **Fonts**: Playfair Display (headings) + DM Sans (body)
- **Framework**: Bootstrap 5.3
- **Icons**: Bootstrap Icons
- **Responsive**: Mobile, Tablet, Laptop, Desktop

---

## 🧪 TESTING CHECKLIST

- [ ] Homepage loads correctly
- [ ] User can register and login
- [ ] Browsing restaurants works
- [ ] Menu filter (Veg/Non-Veg/Healthy) works
- [ ] Add to cart works without page refresh
- [ ] Cart panel opens/closes
- [ ] Checkout places order
- [ ] Orders page shows order history
- [ ] Order tracking shows correct status
- [ ] AI Diet page recommends meals
- [ ] Surprise Me generates random meals
- [ ] Group order create/join works
- [ ] Emergency mode shows open restaurants
- [ ] Feedback form submits successfully
- [ ] Admin login works
- [ ] Admin can manage orders/restaurants/foods/users

---

*Built for BCA College Project | FoodieExpress v1.0*
