# DocHelp - Doctor Appointment Booking & Management System

> **COSC6050 – Programming for Business (Final Lab Project)**  
> Built with Pure PHP (no frameworks), MySQLi, HTML5, CSS3, and JavaScript.

---

## 📋 Features Overview

### 1. Guest
* **Landing Page (`index.php`)**: Displays navigation bar, branding, and 3 random doctors from the database.
* **Doctor Listing (`doctors.php`)**: Search doctors by name and filter by medical specialty.
* **Doctor Profile (`doctor-profile.php`)**: View full doctor credentials, fee, location, and weekly practice schedule. Directs guests to login when clicking "Book Appointment".
* **Authentication**:
  * **Register (`register.php`)**: Validates letters-only username, `@gmail.com` email, numeric 10–13 digit phone, password (min 8 chars) hashed with Salt/BCrypt.
  * **Login (`login.php`)**: Authenticates against database records; includes a **1-week "Remember Me" Cookie** option.

### 2. Member
* **Appointment Booking (`book-appointment.php`)**:
  * Select doctor and appointment date (present or future dates only).
  * "Show Slots" calculates available 1-hour slots matching the doctor's shift for that day, automatically excluding already booked hours.
* **Payment Page (`payment.php`)**:
  * Clear breakdown: Consultation fee + Rp 10.000 administration fee = Grand Total.
  * Transfer amount validation (numeric) and proof image upload (`.jpg`, `.png`).
* **Patient Dashboard (`dashboard.php`)**:
  * View list of booked appointments with consultation status (`Waiting`, `Completed`, `Canceled`) and payment status (`pending`, `approved`, `rejected`, `Not Paid`).
  * Direct action link to pay or update payment proof.

### 3. Admin
* **Specialty Listing (`manage-specialties.php`)**: Add, Edit, and Delete doctor specialties.
* **Manage Doctors (`manage-doctors.php`)**:
  * Add and Edit doctors (unique name, service fee, rating, location, bio).
  * Configure weekly working days (Monday–Sunday) and custom start/end shift hours.
  * Delete doctors (cascades associated schedules).
* **Manage Appointments (`manage-appointments.php`)**: View all patient bookings, change status (`Waiting`, `Completed`, `Canceled`), or delete records.
* **Manage Payments (`manage-payments.php`)**: View payment proof receipts in high quality, approve or reject payment statuses.

---

## 🗄️ Database Setup (`dochelp.sql`)

1. Open **XAMPP Control Panel** and ensure **Apache** and **MySQL** are running.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Import the included [`dochelp.sql`](dochelp.sql) file:
   ```sql
   mysql -u root < dochelp.sql
   ```
   *(This automatically creates `dochelp_db` with all 6 tables and initial seed data).*

---

## 🔑 Default Test Accounts

| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@gmail.com` | `password123` |
| **Member** | `devon@gmail.com` | `password123` |

---

## 📂 Project Directory Structure

```text
pfbFinalLab/
├── config/
│   └── db.php                  # MySQLi connection handler
├── includes/
│   ├── auth.php                # Session management & cookie handler
│   ├── header.php              # Dynamic navigation bar (Guest / Member / Admin)
│   └── footer.php              # Shared footer
├── uploads/                    # Directory storing payment proof images
│   ├── .gitkeep
│   └── sample_receipt.jpg
├── index.php                   # Home landing page (3 random doctors)
├── doctors.php                 # Doctor search & filter
├── doctor-profile.php          # Doctor bio & weekly schedule
├── login.php                   # User login with Remember Me cookie
├── register.php                # User registration with validations & BCrypt hash
├── logout.php                  # Session and cookie destruction
├── book-appointment.php        # Dynamic hourly appointment booking
├── payment.php                 # Payment summary & proof upload
├── dashboard.php               # Patient dashboard
├── manage-specialties.php      # Admin: Specialty CRUD
├── manage-doctors.php          # Admin: Doctor & schedule management
├── manage-appointments.php     # Admin: Appointment status management
├── manage-payments.php         # Admin: Payment proof verification
├── dochelp.sql                 # Complete database schema + seed data
├── style.css                   # Custom responsive styling matching mockups
├── javascript.js               # Client-side UI enhancements
└── README.md                   # Documentation
```
