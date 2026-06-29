# Interlinked — C2C Marketplace

A Consumer-to-Consumer (C2C) marketplace platform built as part of the **ITECA3-12 Summative Assessment** (Deliverable 2) for the Web Development & e-Commerce module at Eduvos.

**Student:** Lehlogonolo Vusani Nkumane (EDUV4941658)  
**Block:** 4, 2025

## Features

- **User Authentication** — Register, login, password reset with token-based flow
- **Product Listings** — Create, browse, search, filter, sort with pagination
- **C2C Marketplace** — Buy/sell between consumers, seller verification
- **Shopping Cart & Checkout** — Order placement with shipping details
- **Payment** — QR code payment flow with confirmation
- **Wishlist** — Save favourite products (AJAX API)
- **Messaging** — Buyer-seller in-app chat
- **User Profiles** — Avatar upload, account management
- **Seller Verification** — ID document upload, admin review queue
- **Admin Panel** — Dashboard, user/product/order management, reports, categories, verification queue
- **Chatbot** — AI-powered product assistance
- **Dark Theme** — Custom dark UI with Bootstrap

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5 |
| Backend | PHP (procedural + prepared statements) |
| Database | MySQL (InnoDB, utf8mb4) |
| Hosting | InfinityFree / Awardspace |
| Security | CSRF tokens, password_hash(), htmlspecialchars(), prepared stmts, role-based access |

## Project Structure

    admin/              Admin panel (dashboard, users, products, orders, reports)
    api/                AJAX endpoints (chatbot, wishlist)
    assets/             CSS, JS, images
    auth/               Login, register, forgot/reset password, logout
    config/             Database and app config
    includes/           Shared header, footer, session, chatbot
    uploads/            User uploads (avatars, products, verification docs)
    index.php           Homepage
    products.php        Product listing with pagination
    product.php         Single product view
    create_product.php  Sell a product
    checkout.php        Checkout flow
    payment.php         Payment confirmation
    orders.php          Order history
    messages.php        Messaging
    wishlist.php        Saved products
    profile.php         User profile
    dashboard.php       Seller dashboard
    verification.php    Seller verification upload
    setup_clean.sql     Database schema

## Setup

1. Import setup_clean.sql into MySQL
2. Update config/database.php with your DB credentials
3. Place files in your web server document root (XAMPP htdocs or similar)
4. Visit index.php in your browser

## Live Site

Hosted at: https://interlinked.kesug.com

## License

This project is for academic purposes only.
