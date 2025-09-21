 ShakeStage — Ticketing Platform for Concerts & Events

ShakeStage is a production-ready, cleanly coded ticketing platform for concerts and events built with plain PHP (no framework) and vanilla JavaScript.
The codebase follows a modular structure, is fully commented, and written with maintainability, security and performance in mind — suitable to showcase as a professional portfolio / résumé project on GitHub.

 Elevator Pitch

A lightweight, fast, and secure ticketing web application for event organizers and customers. Provides event listing, seat selection, checkout, user accounts, and simple admin dashboard — all implemented using raw PHP, MySQL and modern JavaScript for a responsive, interactive UI.

 Key Features

Event listing & filtering (by date, venue, genre)

Interactive seat map & seat selection flow

Secure checkout flow (order summary, simulated payment integration)

User authentication (registration, login, profile, order history)

Admin panel: create/edit events, manage inventory, view orders

Responsive UI (mobile-first) using semantic HTML & CSS

Well-structured code with clear comments and modular includes

Input validation + basic security hardening (prepared statements, output escaping)

Exportable sample data & database schema for quick setup

 Tech Stack

Backend: PHP (plain / procedural + organized includes)

Frontend: HTML5, CSS3, Vanilla JavaScript (ES6+)

Database: MySQL / MariaDB

Dev tools: Git, VS Code, XAMPP / Laragon / MAMP (local server)

Optional (dev): Composer for dev tooling, PHPUnit for tests (if added)

 Project Structure (example)
concert/
│── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│── app/
│   ├── controllers/
│   ├── models/
│   └── services/
│── includes/
│   ├── header.php
│   └── footer.php
│── config/
│   └── database.php
│── storage/
│   └── logs/
│── sql/
│   └── shakestage_schema.sql
│── README.md

 Installation (Local — quick start)

Clone the repo:

git clone https://github.com/rezae140422/shakestage.git
cd shakestage


Place project into your local server folder (e.g. htdocs for XAMPP or www for Laragon). Example for XAMPP:

C:\xampp\htdocs\shakestage


Create a database and import schema:

Create a DB named shakestage (or update .env accordingly).

Import SQL:

mysql -u root -p shakestage < sql/shakestage_schema.sql


Configure database connection:

Copy config/.env.example → config/.env and set DB credentials:

DB_HOST=127.0.0.1
DB_NAME=shakestage
DB_USER=root
DB_PASS=


Open in browser:

http://localhost/shakestage/public

 Usage

Browse events on the home page.

Click an event to open seat map and choose seats.

Register or log in to proceed to checkout.

Admin: use /admin route (credentials in sql/seed_admin.sql or README_ADMIN_CREDENTIALS.md) to create events and manage bookings.

 Database (Schema highlights)

users — id, name, email, password_hash, role, created_at

events — id, title, venue, date_time, description, cover_image

seats — id, event_id, sector, row, seat_no, price, status

orders — id, user_id, event_id, total_amount, status, created_at

order_items — id, order_id, seat_id, price

(Full SQL schema is available in sql/shakestage_schema.sql)

 Security & Best Practices Implemented

Prepared statements / parameterized queries to prevent SQL injection.

Output escaping (HTML entities) to prevent XSS.

Passwords stored with password_hash() (bcrypt).

Minimal exposure of debug messages on production.

Role-based admin checks on protected routes.

 Code Quality & Comments

Each major module contains header comments explaining purpose and input/output.

Functions and complex blocks include inline comments describing algorithmic choices.

Clear separation between presentation (views), logic (controllers/services) and persistence (models).

 Screenshots / Demo

(Place screenshots under public/assets/images/screenshots/ and reference them here.)

screenshots/home.png — Home / Events list

screenshots/event_detail.png — Event detail + seat map

screenshots/checkout.png — Checkout flow

screenshots/admin_events.png — Admin event management

 Testing

Basic manual test cases for flows are included in /tests/manual/ (registration, seat selection, checkout, admin CRUD).

Unit tests can be added with PHPUnit (suggested as next step).

 Resume / Project Highlights (copy-paste-ready for your GitHub profile)

ShakeStage — Full-Stack Ticketing Platform

Implemented a full ticketing lifecycle: event creation, seat selection, checkout, and order history using plain PHP and vanilla JavaScript.

Designed a responsive front-end and an interactive seat map using DOM APIs and event-driven JS.

Built secure server-side logic with PDO prepared statements and bcrypt password hashing.

Created normalized MySQL schema and seed scripts for quick deployment.

Wrote clean, modular code with comprehensive comments and documentation to facilitate future maintenance and extension.

Skills demonstrated: PHP, MySQL, JavaScript (ES6), HTML/CSS, security best practices, application architecture, problem-solving, technical documentation.

 Contribution

Contributions are welcome. If you want to add features (payment gateway integration, unit tests, Docker support, or a React/Vue front-end), please:

Fork the repo

Create a feature branch: git checkout -b feat/payment-integration

Open a Pull Request with clear description and screenshots/tests

 Roadmap (possible next steps)

Integrate a real payment gateway (Stripe / local PSP) with proper PCI-DSS considerations.

Add automated tests (PHPUnit + Cypress for E2E).

Add Dockerfile + docker-compose for simplified deployment.

Implement role-based access with more granular permissions.

Add i18n for multi-language support.

 License

This project is released under the MIT License — see LICENSE for details.

 Contact

Hamidreza — Full Stack Developer

GitHub: https://github.com/rezae140422

Email: h.r.blackcreeper@gmail.com
