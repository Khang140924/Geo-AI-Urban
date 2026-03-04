# Geo-AI Urban Incident Reporting Platform

A smart city web application designed to streamline the process of reporting public infrastructure issues. This platform empowers citizens to report problems like potholes, broken streetlights, or fallen trees by simply uploading a photo. 

The system leverages AI for automatic issue classification and extracts GPS metadata to pinpoint the exact location, reducing manual entry and improving response times for urban management.

## ✨ Key Features

* **🤖 AI-Powered Image Analysis:** Integrates the Google Gemini API to automatically scan uploaded photos, detect the main issue (e.g., road damage, trash, fallen trees), and auto-fill the report category.
* **📍 Automatic Geolocation:** Uses EXIF metadata extraction to pull latitude and longitude directly from the user's uploaded image, automatically dropping a pin on the interactive map.
* **⚡ Seamless User Experience:** Built with asynchronous requests (AJAX) for real-time AI analysis without page reloads. Features modern, responsive popups and toasts via SweetAlert2.
* **🔐 Secure Authentication:** Includes secure user login and Google OAuth integration.
* **📊 Management Dashboard:** Provides a clear overview of reported issues, their statuses (New, Processing, Resolved), and category statistics.

## 🛠️ Technology Stack

* **Frontend:** HTML5, CSS3, JavaScript (jQuery), Bootstrap, SweetAlert2, Leaflet (Maps).
* **Backend:** PHP.
* **Database:** MySQL.
* **External APIs & Libraries:** * Google Gemini API (for image recognition)
  * Google OAuth 2.0 (for authentication)
  * EXIF.js (for GPS data extraction)

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/Khang140924/Geo-AI-Urban.git](https://github.com/Khang140924/Geo-AI-Urban.git)
Setup the Database:

Import the provided .sql file into your MySQL server (e.g., using phpMyAdmin).

Update database connection settings in the source code.

Configure API Keys:

Obtain a Google Gemini API Key and add it to api/analyze_image.php.

Obtain Google OAuth Client ID & Secret and configure them in login_google_action.php.

Note: Never hardcode or commit real API keys to a public repository.

Run the Application:

Host the project on a local server (like XAMPP, WAMP) or a live web server.

💡 How It Works
The user captures or uploads an image of an urban issue.

The front-end temporarily locks the submission and sends the image to the PHP backend via AJAX.

The backend communicates with the Google Gemini API, providing a strict prompt to classify the image into predefined database IDs.

Concurrently, EXIF.js reads the image metadata to extract GPS coordinates and updates the map.

The UI dynamically updates with the suggested category and location, allowing the user to submit the report with a single click.

👨‍💻 Author
Nguyen Lam Khang

Full-stack Web Development
