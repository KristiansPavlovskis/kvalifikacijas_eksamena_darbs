# GYMVERSE - Fitness Web Application

<p align="center">
  <img src="images/logo.png" alt="GYMVERSE Logo" width="200" height="200" style="border-radius: 15px;">
</p>

GYMVERSE is a comprehensive fitness web application designed to help users track their workout routines, nutrition, and fitness progress. With an intuitive interface and powerful features, GYMVERSE aims to revolutionize the modern sport world.

## 🌟 Features

- **User Authentication**: Secure login and registration system
- **Workout Tracking**: Log and monitor your fitness activities
- **Workout Analytics**: Visualize your progress with detailed charts
- **Nutrition Tracking**: Monitor your caloric intake and macronutrients
- **Leaderboard**: Compete with friends and track your rankings
- **Membership Plans**: Access premium features with different subscription tiers
- **Exercise Database**: Comprehensive collection of exercises with detailed instructions

## 🚀 Installation

Follow these steps to set up GYMVERSE on your local machine:

### Prerequisites

- PHP 7.4 or higher
- MySQL or MariaDB
- Web server (e.g., Apache, Nginx)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/gymverse.git
   cd gymverse
   ```

2. **Configure the database**
   
   Edit the database connection details in `config/db_connect.php`:
   ```php
   $host = 'localhost';
   $db_name = 'gymverse_db';
   $username = 'your_db_username';
   $password = 'your_db_password';
   ```

3. **Create the database and tables**
   
   Access the setup page in your browser:
   ```
   http://localhost/gymverse/config/setup_db.php
   ```
   
   This will automatically create the required database and tables.

4. **Set up your web server**
   
   Configure your web server to point to the project's directory.

5. **Access the application**
   
   Open your web browser and navigate to:
   ```
   http://localhost/gymverse/
   ```

## 💻 Usage

1. **Registration**: Create a new account using the registration page
2. **Login**: Access your account through the login page
3. **Profile**: View and update your personal information
4. **Workouts**: Explore different workout routines and exercises
5. **Analytics**: Track your progress through detailed analytics
6. **Leaderboard**: Compare your progress with other users

## 🔧 Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Charts**: Chart.js
- **Icons**: Font Awesome
- **Fonts**: Google Fonts

## 🌐 Directory Structure

```
gymverse/
├── config/             # Configuration files
│   ├── db_connect.php  # Database connection
│   └── setup_db.php    # Database setup script
├── images/             # Image assets
├── index.php           # Homepage
├── login.php           # Login page
├── register.php        # Registration page
├── profile.php         # User profile page
├── workouts.php        # Workouts overview
├── workout-planer.php  # Workout planning tool
├── workout-analytics.php # Workout statistics
├── leaderboard.php     # User leaderboard
├── nutrition.php       # Nutrition tracking
├── membership.php      # Membership plans
└── lietotaja-view.css  # Main stylesheet
```

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👥 Contact

For any questions or suggestions, please feel free to contact us:

- Email: info@gymverse.com
- Website: www.gymverse.com
- Social Media: @gymverse
