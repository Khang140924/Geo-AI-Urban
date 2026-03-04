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
