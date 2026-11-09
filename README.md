# Lost and Found System — Niels Brock

A web-based lost and found platform designed for Niels Brock Copenhagen Business College. This project implements a **weighted multi-attribute matching algorithm** to overcome the "vocabulary mismatch" problem inherent in traditional keyword-based search.

## 📌 Features
*   **Multi-Attribute Matching:** Ranks items based on Color (10), Brand (50), Location (10), and Date (30).
*   **Category Hard Filter:** Only compares items within the exact same category.
*   **Dynamic Category Management:** Users can suggest new categories; admins can approve/reject them.
*   **Claims System:** Users can claim found items; admins manage the approval workflow.
*   **Admin Dashboard:** Full control over users, items, categories, and claims.
*   **Notifications:** In-app alerts when a potential match is found.
*   **Floral UI Theme:** Soft, user-friendly interface built with CSS.

## 🛠 Tech Stack
*   **Backend:** PHP 8.2
*   **Database:** MySQL / MariaDB (Port 3307 in local XAMPP)
*   **Frontend:** HTML5, CSS3, JavaScript
*   **Environment:** XAMPP (Apache)

## 🚀 Setup Instructions
1. Clone this repository to your `htdocs` folder:
   `git clone https://github.com/YOUR_USERNAME/lost-and-found-system.git`
2. Start **Apache** and **MySQL** in XAMPP.
3. Open **phpMyAdmin** and create a database named `lost_and_found`.
4. Import the `database_backup.sql` file (included in this repo) into the database.
5. Update `dbconnect.php` with your database credentials (port, username, password).
6. Open your browser and go to: `http://localhost/lost-and-found-system/`

## 🔑 Test Accounts
| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | admin@nielsbrock.dk | admin123 |
| **User** | test@test.com | test123 |

## 📊 Evaluation Results
The system was tested on a synthetic dataset of 40 evaluation pairs.
*   **Multi-Attribute System:** 97.5% Top-1 Correct Match Rate.
*   **Keyword Baseline:** 32.5% Top-1 Correct Match Rate.
*   **Improvement:** +65 Percentage Points.

## 📝 Author
**Asmita Tamang** (P2863731)  
BSc Computer Science, De Montfort University  
Supervisor: Dr. Muhammad Waleed
