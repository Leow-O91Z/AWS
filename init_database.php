<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Initialization Error</title></head><body>";
echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 4px;'>";
echo "<h1>Initialization Script Incompatible</h1>";
echo "<p>This script (<code>init_database.php</code>) is designed for a different database structure and is <strong>incompatible</strong> with the required <code>user_actions.sql</code> schema.</p>";
echo "<p><strong>Do not run this script.</strong></p>";
echo "<p>To set up the database:</p>";
echo "<ol>";
echo "<li>Ensure the database named '<code>user_actions</code>' exists on your MySQL server (<code>localhost</code>).</li>";
echo "<li>Use a tool like phpMyAdmin or the MySQL command line to <strong>import the structure from your <code>user_actions.sql</code> file</strong> into the '<code>user_actions</code>' database.</li>";
echo "<li><strong>CRITICAL:</strong> After importing, run the following SQL command to fix password security: <br><code>ALTER TABLE customer MODIFY COLUMN password VARCHAR(255) NOT NULL;</code><br><code>ALTER TABLE admin MODIFY COLUMN password VARCHAR(255) NOT NULL;</code></li>";
echo "</ol>";
echo "<p><a href='db_check.php' style='color: #721c24; text-decoration: underline;'>Check Database Status</a></p>";
echo "</div>";
echo "</body></html>";
die();