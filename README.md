# GreenLife Wellness Center Admin Dashboard

## Overview
This project is an admin dashboard for the GreenLife Wellness Center, designed to manage users, services, and appointments. It provides functionalities for admin login, registration, and management of various aspects of the wellness center.

## Project Structure
The project is organized into several directories and files:

- **admin/**: Contains all admin-related functionalities.
  - **login.php**: Admin login functionality.
  - **register.php**: Admin registration functionality.
  - **dashboard.php**: Main dashboard for admins.
  - **logout.php**: Handles admin logout.
  - **users/**: User management functionalities.
    - **index.php**: Lists all registered users.
    - **view.php**: Displays detailed information about a specific user.
    - **edit.php**: Allows editing of user information.
    - **delete.php**: Handles user deletion.
  - **services/**: Service management functionalities.
    - **index.php**: Lists all services.
    - **add.php**: Allows adding new services.
    - **edit.php**: Allows editing existing services.
    - **delete.php**: Handles service deletion.
  - **appointments/**: Appointment management functionalities.
    - **index.php**: Lists all appointments.
    - **view.php**: Displays detailed information about a specific appointment.
    - **edit.php**: Allows editing of appointment details.
    - **delete.php**: Handles appointment deletion.

- **config/**: Contains configuration files.
  - **database.php**: Database connection settings.
  - **config.php**: Application configuration settings.

- **includes/**: Contains reusable components.
  - **functions.php**: Utility functions for the application.
  - **header.php**: HTML header section for admin pages.
  - **footer.php**: HTML footer section for admin pages.

- **assets/**: Contains static assets.
  - **css/**: Stylesheets for the admin dashboard.
    - **admin.css**: CSS styles for the admin interface.
  - **js/**: JavaScript files for the admin dashboard.
    - **admin.js**: JavaScript functions for interactivity.

- **sql/**: Contains SQL scripts.
  - **database.sql**: SQL statements to create necessary database tables.

## Setup Instructions
1. **Clone the Repository**: Clone this repository to your local machine.
2. **Install XAMPP**: Ensure you have XAMPP installed and running.
3. **Create Database**: Import the `database.sql` file into your MySQL database using phpMyAdmin.
4. **Configure Database Connection**: Update the `config/database.php` file with your database credentials.
5. **Access the Application**: Open your web browser and navigate to `http://localhost/greenlife-wellness-admin/admin/login.php` to access the admin login page.

## Usage Guidelines
- **Admin Login**: Use the login page to access the admin dashboard.
- **Admin Registration**: New admins can register using the registration page.
- **Manage Users**: Admins can view, edit, and delete users from the user management section.
- **Manage Services**: Admins can add, edit, and delete services offered by the wellness center.
- **Manage Appointments**: Admins can view and manage appointments through the appointments section.

## Contributing
Contributions are welcome! Please feel free to submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License.
