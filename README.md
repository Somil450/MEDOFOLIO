# 🏥 **MEDOFOLIO** - Advanced Medical Intelligence Platform

<div align="center">

![HealthIntel Logo](https://img.shields.io/badge/HealthIntel-Medical_Platform-blue?style=for-the-badge\&logo=medical)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square\&logo=php\&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square\&logo=mysql\&logoColor=white)
![Google Gemini](https://img.shields.io/badge/Google_Gemini-AI_Powered-4285F4?style=flat-square\&logo=google\&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)

**Revolutionizing Personal Healthcare Management with AI-Powered Intelligence**

</div>

---

## 🌟 **Overview**

**Medofolio** is a comprehensive, AI-powered medical management platform that bridges the gap between patients, healthcare providers, and intelligent medical insights. Built with modern web technologies and integrated with cutting-edge AI, it provides a seamless experience for managing personal health records, connecting with doctors, and accessing nearby medical facilities.

---

## 🎯 **Key Highlights**

* 🤖 **AI-Powered Medical Intelligence** - Google Gemini integration for smart health summaries
* 🏥 **Dual Portal System** - Separate interfaces for patients and healthcare providers
* 🗺️ **Location-Aware Services** - Google Maps integration for nearby hospital discovery
* 📊 **Advanced Analytics** - Risk assessment and disease prediction engines
* 🔒 **Enterprise-Grade Security** - HIPAA-compliant data handling and encryption
* 📱 **Responsive Design** - Optimized for desktop, tablet, and mobile devices

---

## 🚀 **Core Features**

### 👤 **Patient Portal**

* 🔐 Secure Authentication - Registration, login, password reset with email verification
* 📋 Medical History Tracking - Comprehensive disease history with severity levels
* 🤖 AI Health Summaries - Intelligent medical report analysis and insights
* 📅 Appointment Management - Book, reschedule, and cancel appointments
* 📤 Document Upload - Secure medical report and document management
* 📊 Health Timeline - Visual timeline of medical events and treatments
* 📄 PDF Reports - Download detailed medical history reports

---

### 👨‍⚕️ **Doctor Portal**

* 👥 Patient Management - View and manage assigned patients
* 📝 Medical Notes - Add detailed notes to patient records
* 🚨 Alert System - Create and manage patient health alerts
* 📋 Prescription Management - Generate and track prescriptions
* 📊 Patient Analytics - Monitor patient health trends and risks

---

### 🧠 **AI-Powered Engines**

* 🔍 Disease Detection Engine - Intelligent disease identification and classification
* ⚠️ Risk Assessment Engine - Predictive health risk analysis
* 💊 Prescription Engine - Smart medication recommendations
* 🏥 Hospital Search Engine - Location-based healthcare facility discovery

---

### 🔧 **Technical Features**

* 🗄️ Robust Database - Normalized MySQL schema with referential integrity
* 🔌 RESTful APIs - Clean API endpoints for external integrations
* 📧 Email Integration - Automated notifications and password recovery
* 🎨 Professional UI - Modern, accessible design with enhanced CSS
* 🔒 Security First - Prepared statements, session management, input validation

---

## 🛠 **Technology Stack**

### **Backend**

```php
PHP 8.0+          # Server-side logic and API endpoints
MySQL 8.0+        # Relational database management
FPDF Library      # PDF generation and reporting
```

### **Frontend**

```html
HTML5             # Semantic markup and structure
CSS3              # Responsive styling and animations
JavaScript ES6+   # Interactive user interfaces
```

### **APIs & Services**

```php
Google Gemini AI   # AI-powered medical intelligence
Google Maps API    # Location services and hospital search
Custom REST APIs   # Internal service communication
```

### **Development Tools**

```bash
XAMPP/WAMP        # Local development environment
Git               # Version control
Composer          # PHP dependency management
```

---

## 📁 **Project Structure**

```bash
healthintel/
├── 📁 api/
│   ├── doctor_actions.php
│   └── get_nearby_hospitals.php
├── 📁 assets/
├── 📁 auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── reset_password.php
├── 📁 config/
│   └── gemini.php
├── 📁 dashboard/
│   ├── dashboard.php
│   └── doctor_dashboard.php
├── 📁 engine/
│   ├── ai_summary_engine.php
│   ├── disease_engine.php
│   ├── risk_engine.php
│   └── prescription_engine.php
├── 📁 patient/
│   ├── add_history.php
│   ├── book_appointment.php
│   └── upload_reports.php
├── 📁 uploads/
├── db.php
├── index.php
└── README.md
```

---

## ⚡ **Quick Start**

### **Prerequisites**

* PHP 8.0+ with extensions: `mysqli`, `curl`, `mbstring`
* MySQL 8.0+ database server
* Apache/Nginx web server
* Composer (optional)

---

### **Installation**

```bash
git clone https://github.com/yourusername/healthintel.git
cd healthintel
```

---

### **Database Setup**

```bash
mysql -u root -p < database_setup.sql

# Or run the setup script
php setup_database.php
```

---

### **Configure Environment**

```php
# Edit db.php with your database credentials
$host = "localhost";
$user = "your_username";
$pass = "your_password";
$db = "healthintel";
```

---

### **API Configuration**

```php
# Add your API keys in config/gemini.php
define("GEMINI_API_KEY", "your_gemini_api_key");

# Add Google Maps API key
$GOOGLE_MAPS_API_KEY = "your_google_maps_api_key";
```

---

### **Start the Application**

```bash
# Using XAMPP - place in htdocs and start Apache/MySQL
# Or using PHP built-in server
php -S localhost:8000
```

---

### **Access the Application**

```
http://localhost/healthintel
```

---

## 🔧 **Configuration**

### **Database Configuration**

```php
$host = "localhost";
$user = "root";
$pass = "";
$db = "healthintel";
```

---

### **API Keys Setup**

```php
define("GEMINI_API_KEY", "your_actual_gemini_key");

$GOOGLE_MAPS_API_KEY = "your_actual_maps_key";
```

---

### **Email Configuration (Optional)**

```php
$smtp_host = "smtp.gmail.com";
$smtp_user = "your_email@gmail.com";
$smtp_pass = "your_app_password";
```

---

## 📊 **Database Schema**

### **Core Tables**

* users
* doctors
* patients
* disease_master
* patient_disease_history
* doctor_medical_history
* appointments
* prescriptions
* medical_reports

---

### **Relationships**

```
users (1) ──── (N) patients
users (1) ──── (N) doctors
patients (N) ──── (N) patient_disease_history
patient_disease_history (N) ──── (1) disease_master
doctors (N) ──── (N) doctor_medical_history
patients (N) ──── (N) appointments
```

---

## 🔌 **API Endpoints**

### **Doctor Actions API**

```
POST /api/doctor_actions.php?action=add_note
POST /api/doctor_actions.php?action=assign_patient
POST /api/doctor_actions.php?action=create_alert
GET  /api/doctor_actions.php?action=get_patients
```

---

### **Hospital Search API**

```
GET /api/get_nearby_hospitals.php?lat={latitude}&lng={longitude}
```

---

## 🤖 **AI Integration**

* Medical Summary Generation
* Disease Classification
* Risk Assessment
* Treatment Recommendations

---

## 🔒 **Security Features**

* Password Hashing (bcrypt)
* SQL Injection Protection
* Session Management
* XSS Prevention
* CSRF Protection
* Audit Logging

---

## 🧪 **Testing**

```bash
php test_database.php
php test_api_endpoints.php
php test_authentication.php
```

---

## 🚀 **Deployment**

### **Production Deployment**

* Apache/Nginx configuration
* Enable HTTPS
* Database optimization
* File permissions setup
* Backup strategy

---

### **Cloud Deployment Options**

* AWS EC2 with RDS
* Google Cloud Platform
* DigitalOcean
* Heroku

---

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to GitHub
5. Open a Pull Request

---

## 🙏 **Acknowledgments**

* Google Gemini AI
* Google Maps Platform
* FPDF Library
* PHP Community

---

<div align="center">

**Made with ❤️ for better healthcare management**

⭐ Star this repository if you find it helpful!

</div>

