<?php
/*
 * ReguCheck - A checklist generator for regulatory compliance
 * Author: encrypter15
 * Email: encrypter15@gmail.com
 * License: BSD
 * Description: Generates compliance checklists based on industry and location for GDPR, HIPAA, PCI-DSS, etc.
 */

session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'regucheck_db');

// Connect to MySQL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize variables
$industry = isset($_POST['industry']) ? $_POST['industry'] : '';
$location = isset($_POST['location']) ? $_POST['location'] : '';
$checklist = [];
$errors = [];

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Predefined regulatory standards and controls
$standards = [
    'GDPR' => [
        'controls' => [
            ['id' => 'GDPR-1', 'desc' => 'Implement data protection by design and default.', 'industries' => ['Tech', 'Healthcare', 'Finance'], 'locations' => ['EU']],
            ['id' => 'GDPR-2', 'desc' => 'Maintain records of processing activities.', 'industries' => ['All'], 'locations' => ['EU']],
            ['id' => 'GDPR-3', 'desc' => 'Conduct Data Protection Impact Assessments (DPIA).', 'industries' => ['Tech', 'Healthcare'], 'locations' => ['EU']],
        ],
    ],
    'HIPAA' => [
        'controls' => [
            ['id' => 'HIPAA-1', 'desc' => 'Ensure physical safeguards for PHI.', 'industries' => ['Healthcare'], 'locations' => ['US']],
            ['id' => 'HIPAA-2', 'desc' => 'Implement access controls for ePHI.', 'industries' => ['Healthcare'], 'locations' => ['US']],
            ['id' => 'HIPAA-3', 'desc' => 'Conduct regular risk assessments.', 'industries' => ['Healthcare'], 'locations' => ['US']],
        ],
    ],
    'PCI-DSS' => [
        'controls' => [
            ['id' => 'PCI-1', 'desc' => 'Install and maintain a firewall configuration.', 'industries' => ['Finance', 'Retail'], 'locations' => ['All']],
            ['id' => 'PCI-2', 'desc' => 'Do not use vendor-supplied defaults for passwords.', 'industries' => ['Finance', 'Retail'], 'locations' => ['All']],
            ['id' => 'PCI-3', 'desc' => 'Protect stored cardholder data with encryption.', 'industries' => ['Finance', 'Retail'], 'locations' => ['All']],
        ],
    ],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $industry = sanitizeInput($industry);
    $location = sanitizeInput($location);

    if (empty($industry) || empty($location)) {
        $errors[] = "Please select both industry and location.";
    } else {
        // Generate checklist
        foreach ($standards as $standard => $data) {
            foreach ($data['controls'] as $control) {
                $applicable_industry = in_array($industry, $control['industries']) || in_array('All', $control['industries']);
                $applicable_location = in_array($location, $control['locations']) || in_array('All', $control['locations']);
                if ($applicable_industry && $applicable_location) {
                    $checklist[] = [
                        'standard' => $standard,
                        'id' => $control['id'],
                        'desc' => $control['desc'],
                        'status' => 'Pending',
                        'notes' => '',
                    ];
                }
            }
        }

        // Save to database
        try {
            $stmt = $pdo->prepare("INSERT INTO checklists (user_id, standard, control_id, description, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($checklist as $item) {
                $stmt->execute([session_id(), $item['standard'], $item['id'], $item['desc'], $item['status'], $item['notes']]);
            }
        } catch (PDOException $e) {
            $errors[] = "Failed to save checklist: " . $e->getMessage();
        }
    }
}

// Fetch existing checklist for the user
try {
    $stmt = $pdo->prepare("SELECT * FROM checklists WHERE user_id = ? ORDER BY standard, control_id");
    $stmt->execute([session_id()]);
    $saved_checklist = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to load checklist: " . $e->getMessage();
}

// Handle status/notes updates
if (isset($_POST['update'])) {
    $control_id = sanitizeInput($_POST['control_id']);
    $status = sanitizeInput($_POST['status']);
    $notes = sanitizeInput($_POST['notes']);
    
    try {
        $stmt = $pdo->prepare("UPDATE checklists SET status = ?, notes = ? WHERE user_id = ? AND control_id = ?");
        $stmt->execute([$status, $notes, session_id(), $control_id]);
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh page
        exit;
    } catch (PDOException $e) {
        $errors[] = "Failed to update checklist: " . $e->getMessage();
    }
}

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="regucheck_checklist.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Standard', 'Control ID', 'Description', 'Status', 'Notes']);
    foreach ($saved_checklist as $item) {
        fputcsv($output, [$item['standard'], $item['control_id'], $item['description'], $item['status'], $item['notes']]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReguCheck - Compliance Checklist Generator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>ReguCheck - Compliance Checklist Generator</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Industry:
            <select name="industry" required>
                <option value="">Select Industry</option>
                <option value="Tech" <?php echo $industry === 'Tech' ? 'selected' : ''; ?>>Technology</option>
                <option value="Healthcare" <?php echo $industry === 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                <option value="Finance" <?php echo $industry === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                <option value="Retail" <?php echo $industry === 'Retail' ? 'selected' : ''; ?>>Retail</option>
            </select>
        </label>
        <label>Location:
            <select name="location" required>
                <option value="">Select Location</option>
                <option value="EU" <?php echo $location === 'EU' ? 'selected' : ''; ?>>European Union</option>
                <option value="US" <?php echo $location === 'US' ? 'selected' : ''; ?>>United States</option>
                <option value="All" <?php echo $location === 'All' ? 'selected' : ''; ?>>Global</option>
            </select>
        </label>
        <button type="submit">Generate Checklist</button>
    </form>

    <?php if (!empty($saved_checklist)): ?>
        <h2>Your Compliance Checklist</h2>
        <a href="?export=csv">Export to CSV</a>
        <table>
            <thead>
                <tr>
                    <th>Standard</th>
                    <th>Control ID</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saved_checklist as $item): ?>
                    <tr>
                        <td><?php echo $item['standard']; ?></td>
                        <td><?php echo $item['control_id']; ?></td>
                        <td><?php echo $item['description']; ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="control_id" value="<?php echo $item['control_id']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="Pending" <?php echo $item['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="In Progress" <?php echo $item['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo $item['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                        </td>
                        <td>
                                <textarea name="notes" rows="2" cols="20"><?php echo $item['notes']; ?></textarea>
                                <input type="hidden" name="update" value="1">
                                <button type="submit">Update</button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>

<?php
// Create database and table if not exists
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    $pdo->exec("CREATE TABLE IF NOT EXISTS checklists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        standard VARCHAR(50) NOT NULL,
        control_id VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        notes TEXT
    )");
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>

