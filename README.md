# AML Transaction Monitoring System

A web-based Anti-Money Laundering (AML) transaction monitoring project developed using **PHP, PostgreSQL, HTML, CSS, and JavaScript**.

The system is designed to monitor customer transactions, identify potentially suspicious activity using risk-based rules, and provide customer and transaction risk information through a web interface.

## Project Overview

The purpose of this project is to demonstrate how a basic AML transaction monitoring system can be developed using server-side programming and a relational database.

The application works with customer, account, and transaction information stored in PostgreSQL. PHP is used to connect the web application to the database and process transaction and risk information.

## Features

* Customer management
* Customer risk assessment
* Transaction monitoring
* Transaction risk scoring
* Detection of potentially suspicious transactions
* Customer risk information
* Dashboard for monitoring information
* Analytics and transaction information
* PostgreSQL database integration
* PHP server-side processing

## Technologies Used

* **PHP 8.2**
* **PostgreSQL**
* **HTML5**
* **CSS3**
* **JavaScript**
* **PDO / PostgreSQL**
* **XAMPP**
* **Git & GitHub**

## Project Structure

```text
aml-transaction-monitoring/
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── php/
│   ├── analytics.php
│   ├── config.php
│   ├── customer_manage.php
│   ├── customer_risk.php
│   ├── customers.php
│   ├── dashboard.php
│   ├── dashboard_backup.php
│   ├── detect.php
│   ├── risk_score.php
│   └── test_connection.php
│
├── sql/
│   └── schema.sql
│
├── .gitignore
└── README.md
```

## Database

The project uses **PostgreSQL** as the database system.

The database contains information used for the monitoring system, including:

* Customers
* Accounts
* Transactions
* Risk-related information

The `database/schema.sql` file contains the database structure.

The `database/seed.sql` file contains sample database data used for testing and development.

## Configuration

Database credentials are stored locally in a `.env` file.

The `.env` file is intentionally excluded from GitHub using `.gitignore` so that database credentials are not committed to the repository.

Example configuration:

```text
DB_HOST=localhost
DB_PORT=5432
DB_NAME=aml_engine
DB_USER=postgres
DB_PASSWORD=your_password
```

**Do not commit your real database password to GitHub.**

## Running the Project Locally

### 1. Install the required software

You will need:

* PHP
* PostgreSQL
* XAMPP or another PHP environment
* Git

### 2. Create the PostgreSQL database

Create a PostgreSQL database named:

```text
aml_engine
```

Then import the database schema:

```text
database/schema.sql
```

You can also use the sample data from:

```text
database/seed.sql
```

### 3. Configure the environment

Create a `.env` file in the project root:

```text
DB_HOST=localhost
DB_PORT=5432
DB_NAME=aml_engine
DB_USER=postgres
DB_PASSWORD=your_password
```

Replace `your_password` with your local PostgreSQL password.

### 4. Start the PHP development server

From the project directory, run:

```powershell
& "C:\xampp\php\php.exe" -S localhost:8000 -t php
```

Then open:

```text
http://localhost:8000
```

## Risk Monitoring

The application includes PHP functionality for evaluating transaction and customer risk.

The project demonstrates concepts such as:

* Transaction monitoring
* Risk scoring
* Customer risk assessment
* Detection of potentially suspicious activity
* Database-driven analysis

This project is intended as an educational implementation and is **not a production AML compliance system**.

## Security

Sensitive configuration information such as database passwords should not be committed to the repository.

The project uses a `.env` file for local database credentials, and `.env` is excluded through `.gitignore`.

## Future Improvements

Possible future improvements include:

* More advanced transaction monitoring rules
* Additional risk indicators
* Improved dashboard visualizations
* User authentication and authorization
* Transaction filtering and searching
* Alert management
* Audit logging
* Improved reporting
* More comprehensive AML detection scenarios

## Author

**Christina Poudel**

Computer Programming and Analysis Student
George Brown Polytechnic
Toronto, Ontario, Canada

## Disclaimer

This project was created for educational and development purposes. It demonstrates basic concepts related to transaction monitoring and risk assessment and should not be considered a complete AML compliance solution.
