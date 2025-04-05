# ReguCheck

## Overview
**ReguCheck** is a web-based PHP and MySQL application designed to generate compliance checklists for regulatory standards such as GDPR, HIPAA, and PCI-DSS. It adapts the checklist based on the user's selected industry and location, ensuring all required controls are documented.

- **Author**: encrypter15
- **Email**: encrypter15@gmail.com
- **License**: BSD

## Features
- Generates checklists tailored to industry (e.g., Tech, Healthcare, Finance, Retail) and location (e.g., EU, US, Global).
- Supports GDPR, HIPAA, and PCI-DSS with predefined controls.
- Saves checklist data to a MySQL database for persistence.
- Allows updating status (Pending, In Progress, Completed) and adding notes.
- Exports checklist to CSV for reporting.
- Robust error handling for database connections, form submissions, and data updates.
- Simple and responsive UI.

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (e.g., Apache, Nginx)

## Installation
1. **Clone the Repository**:
   ```bash
   git clone <repository-url>
   cd regucheck
   ```

2. **Set Up MySQL**:
   - Create a MySQL database named `regucheck_db`.
   - Update the database credentials in `index.php` if different from defaults (`root`, no password):
     ```php
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

3. **Deploy to Web Server**:
   - Place the `index.php` file in your web server’s root directory (e.g., `/var/www/html/regucheck`).
   - Ensure the web server has write permissions for session handling.

4. **Access the Application**:
   - Open your browser and navigate to `http://localhost/regucheck/index.php`.

## Usage
1. **Generate Checklist**:
   - Select your industry and location from the dropdowns.
   - Click "Generate Checklist" to create a tailored list of controls.

2. **Manage Checklist**:
   - Update the status of each control using the dropdown.
   - Add notes in the textarea and click "Update" to save changes.

3. **Export**:
   - Click "Export to CSV" to download the checklist as a CSV file.

## Database Schema
- **Table**: `checklists`
  - `id`: INT, AUTO_INCREMENT, PRIMARY KEY
  - `user_id`: VARCHAR(255), NOT NULL (session ID for user tracking)
  - `standard`: VARCHAR(50), NOT NULL (e.g., GDPR, HIPAA)
  - `control_id`: VARCHAR(50), NOT NULL (unique control identifier)
  - `description`: TEXT, NOT NULL (control description)
  - `status`: VARCHAR(20), DEFAULT 'Pending' (Pending, In Progress, Completed)
  - `notes`: TEXT (user notes)

## Error Handling
- Displays errors for database connection failures, empty form submissions, and data save/update issues.
- Sanitizes all user inputs to prevent XSS and SQL injection.

## Security Notes
- Use a secure MySQL user with a strong password in production.
- Enable HTTPS on your web server to protect data in transit.
- Consider adding user authentication for multi-user environments.

## License
This software is licensed under the BSD License. See the LICENSE file for details.
```

---

### Notes on ReguCheck
- **Robustness**: Uses PDO with prepared statements for secure database interactions, includes input sanitization, and handles errors gracefully.
- **Feature-Rich**: Supports dynamic checklist generation, status updates, notes, and CSV export.
- **Setup**: The code auto-creates the database and table on first run (if permissions allow), making it easy to deploy.

