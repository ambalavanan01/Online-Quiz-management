# Online Quiz Management System

A robust, feature-rich online quiz platform built with PHP. This system provides a comprehensive solution for creating, managing, and taking quizzes without the need for an external database server—it runs entirely on a fast, concurrent flat-file JSON database system.

## 🌟 Key Features

### User Roles & Management
* **Admin**: Complete system overview, manage all users, quizzes, and site settings.
* **Teacher**: Create and manage quizzes, add questions, view student results, and analytics.
* **Student**: Access assigned quizzes, view real-time leaderboards, and detailed performance reports.

### Advanced Quiz Engine
* **Multiple Question Types**: Supports Multiple Choice (MCQ), True/False, and Short Answer questions.
* **Rich Media Integration**: Embed images, audio, and video directly into questions.
* **Flexible Settings**:
    * Scheduled start and end times
    * Password-protected quizzes
    * Customizable attempt limits
    * Optional negative marking for incorrect answers
    * Shuffle questions

### 🛡️ Strict Anti-Cheat & Proctoring
* **Tab-Switching Detection**: Automatically detects and warns when a user loses focus or changes tabs.
* **Fullscreen Enforcement**: Forces the test-taker into fullscreen mode.
* **No-Copy/Paste**: Disables context menus, copying, and pasting during the exam.
* **Auto-Submit on Violations**: Automatically submits the quiz if maximum policy violations are reached.

### 📊 Analytics & Reporting
* Real-time leaderboard
* Comprehensive student dashboard
* Exportable results
* Detailed view of individual quiz attempts

## 🚀 Getting Started

### Prerequisites
* PHP 7.4 or higher
* A web server (Apache, Nginx, or PHP's built-in server)
* *No MySQL database or external dependencies required!*

### Installation

1. **Clone or Download** this repository to your web server's document root (e.g., `htdocs`, `www`).
2. **Set Permissions**: Ensure the `data/` and `uploads/` directories are writable by your web server. The system will automatically create `data/` if it doesn't exist.
    ```bash
    chmod -R 777 data uploads
    ```
3. **Run the Application**: Open your browser and navigate to the project folder (e.g., `http://localhost/online-quiz-management/`).
4. **Default Login**: *(You may need to register an admin user if the system provides an installation script, or use standard credentials if configured).*

## 🗂️ Project Structure

* `/assets/` - CSS styles and JavaScript files.
* `/data/` - JSON files acting as the database (auto-generated).
* `/includes/` - Core logic, JSON database class (`db.php`), authentication (`auth.php`), header, and footer.
* `/uploads/` - Directory for question media uploads.
* `*.php` - Various dashboard, quiz taking, and management views.

## 🛠️ Built With

* Vanilla PHP
* HTML5 / CSS3 / Vanilla JavaScript
* Custom JSON Flat-file Database Architecture

## 📄 License

This project is open-source and available under the MIT License.
