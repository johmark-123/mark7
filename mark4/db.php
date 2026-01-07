<?php
session_start();
$conn = new mysqli("localhost", "root", "", "payroll_db");
if ($conn->connect_error) die("Connection failed");

// Helper function for messages
function getMessage($msg) {
    if (!$msg) return "";
    list($type, $txt) = explode('|', $msg);
    $color = ($type == 'success') ? 'border-emerald-400' : 'border-rose-400';
    return "<div class='mb-6 p-4 glass border-l-4 $color'>$txt</div>";
}
?>