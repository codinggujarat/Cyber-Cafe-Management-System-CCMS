# Cyber Cafe Management System (CCMS) 🚀

A next-generation, full-stack web application designed to digitize and streamline operations for cyber cafes, common service centers (CSCs), and document centers. It features a triple-interface system (User, Admin, Delivery) supercharged with **AI Assistance**, **Video Conferencing**, **Real-Time Chat**, and **QR-Based Tracking**.

![Version](https://img.shields.io/badge/Version-2.6-blue.svg) ![Status](https://img.shields.io/badge/Status-Production%20Ready-green.svg) ![PHP](https://img.shields.io/badge/PHP-8.2-purple.svg) ![Razorpay](https://img.shields.io/badge/Payment-Razorpay-blue.svg) ![Jitsi](https://img.shields.io/badge/Video-Jitsi%20WebRTC-blue.svg) ![Gemini](https://img.shields.io/badge/AI-Google%20Gemini-orange.svg)

---

## 🌟 New Feature Highlights (v2.6)

### 📅 Smart Appointment System
*   **Flexible Booking**: Users can book **In-Person** visits or **Virtual Consultations** (Video/Voice).
*   **Slot Management**: Intelligent system prevents double-booking.
*   **Status Workflow**: `Pending` → `Confirmed` → `Completed` flow with email notifications.

### 📹 Secure Video Conferencing
*   **Built-in Meeting Room**: No external apps required. Video calls happen directly in the browser via **WebRTC / Jitsi**.
*   **Secure Access**: Unique, hashed room IDs ensure only the authorized User and Admin can join.
*   **Conditional Entry**: Video rooms unlock only when the appointment is confirmed and scheduled.

### 💬 1-to-1 Live Support Chat
*   **Dedicated Channels**: Private chat rooms for every specific appointment.
*   **History Preservation**: All conversations are archived for future reference.
*   **Real-Time**: Instant message delivery between Users and Admins.

### 🤖 AI Chat Assistant
*   **Intelligent Support**: Integrated **Google Gemini AI** to answer general queries instantly.
*   **Context Aware**: Knows your service list and FAQs.

---

## 🏗️ Core Features

### 🖥️ Customer / User Panel
*   **Assisted Service Workflow**: Apply for government services (PAN, Aadhaar). Track: *Submitted* → *In Process* → *Approved*.
*   **Document Vault**: Download official acknowledgments directly.
*   **Modern Dashboard**: Glassmorphism UI tracking spend, orders, and appointments.
*   **QR Tracking**: Scan your invoice QR code to get instant order status on `track_public.php`.

### 🛠️ Admin Panel (The Command Center)
*   **Premium Analytics**: Dark Mode Dashboard with Revenue, Top Services, and Growth charts.
*   **Service Ops**: Form Builder, Price Control, and Inventory Management.
*   **Order Intelligence**: Automated Returns, Refunds, and Status Updates.
*   **Communication Hub**: Manage Chats and Video calls from the "Appointments" tab.

### 🛵 Delivery Partner App
*   **Mobile-First Design**: Optimized for on-the-go usage.
*   **Task Management**: Accept, Start, and Complete deliveries.
*   **Earnings**: Real-time payout request system.

---

## 🚀 Technology Stack
*   **Frontend**: Native PHP, **Tailwind CSS** (CDN), FontAwesome, Chart.js.
*   **Backend**: PHP 8.x, Vanilla JS (AJAX).
*   **Database**: MySQL (PDO).
*   **Live Video**: Jitsi Meet External API (WebRTC).
*   **AI Engine**: Google Gemini API.
*   **Payments**: Razorpay API.
*   **QR Engines**: HTML5-QRCode, QRServer API.

---

## 📥 Installation & Setup

### 1. Server Requirements
*   XAMPP / WAMP / LAMP Stack.
*   PHP 8.0 or higher.
*   MySQL 5.7 or higher.

### 2. Database Configuration
1.  Create a database named `cyber_cafe_db`.
2.  Import `cyber_cafe_db.sql`.
3.  **Updates**: If upgrading, run `update_google_schema.php` and `setup_appointments_db.php`.

### 3. Application Config
Edit `config/config.php` to set your environment variables:

```php
// Payments (Razorpay)
define('RAZORPAY_KEY_ID', 'rzp_test_...');
define('RAZORPAY_KEY_SECRET', '...');

// AI (Google Gemini)
define('GEMINI_API_KEY', 'AIza...');

// Google OAuth
define('GOOGLE_CLIENT_ID', '...');
define('GOOGLE_CLIENT_SECRET', '...');
```

### 4. Running the Project
1.  Place the project folder in `htdocs`.
2.  Access `http://localhost/cafemgmt/`
3.  **Admin Login**: `admin@admin.com` / `password`

---

## 📂 Directory Structure

```
/admin          # Dashboard, Orders, Appointments, Returns
/user           # Booking, Video Room, Chat, Orders
/api            # Internal APIs (Chatbot, Updates)
/delivery       # Delivery Partner Mobile App
/config         # DB Connections, Helper Functions
/uploads        # Secure storage for user documents
/assets         # CSS, JS, Images
```

---

## 🔒 Security Measures
*   **Role-Based Access Control (RBAC)**: Strict permission checks (Admin vs User vs Delivery).
*   **Video Security**: Appointment rooms are time-locked and role-gated.
*   **Data Masking**: Auto-mask sensitive IDs (Aadhaar/PAN).
*   **CSRF Protection**: Token-based protection.

---

*Built with ❤️ by the Advanced Agentic Coding Team*
