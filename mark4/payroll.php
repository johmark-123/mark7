<?php
// Database connection
$servername = "localhost"; $username = "root"; $password = ""; $dbname = "payroll_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed");

$message = "";

// Handle form logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_employee'])) {
        $stmt = $conn->prepare("INSERT INTO employees (name, hourly_rate) VALUES (?, ?)");
        $stmt->bind_param("sd", $_POST['name'], $_POST['hourly_rate']);
        $message = $stmt->execute() ? "success|Employee added!" : "error|Failed to add.";
    } elseif (isset($_POST['calculate_payroll'])) {
        $stmt = $conn->prepare("SELECT hourly_rate FROM employees WHERE id = ?");
        $stmt->bind_param("i", $_POST['employee_id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) {
            $gross = $_POST['hours_worked'] * $res['hourly_rate'];
            $tax = $gross * 0.20;
            $net = $gross - $tax;
            $ins = $conn->prepare("INSERT INTO payroll (employee_id, hours_worked, gross_pay, tax_deduction, net_pay, pay_date) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("idddds", $_POST['employee_id'], $_POST['hours_worked'], $gross, $tax, $net, $_POST['pay_date']);
            $message = $ins->execute() ? "success|Payroll calculated!" : "error|Failed to save.";
        }
    }
}

// Stats
$total_paid = $conn->query("SELECT SUM(net_pay) as total FROM payroll")->fetch_assoc()['total'] ?? 0;
$emp_count = $conn->query("SELECT COUNT(*) as total FROM employees")->fetch_assoc()['total'] ?? 0;
$employees = $conn->query("SELECT id, name FROM employees");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glassmorphism Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .glass-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        .glass-input::placeholder { color: rgba(255, 255, 255, 0.7); }
        .glass-input option { color: #333; }
    </style>
</head>
<body class="p-4 md:p-8 text-white">

<div class="max-w-6xl mx-auto">
    <header class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-extrabold tracking-tight drop-shadow-lg">Payroll Studio</h1>
            <p class="text-blue-100 opacity-80">Financial Management & Oversight</p>
        </div>
        <div class="flex gap-4">
            <div class="glass p-4 rounded-2xl flex items-center gap-4 min-w-[180px]">
                <div class="bg-white/20 p-2 rounded-lg text-white font-bold">$$</div>
                <div>
                    <p class="text-[10px] uppercase font-bold tracking-widest opacity-70">Total Net</p>
                    <p class="text-xl font-bold">$<?php echo number_format($total_paid, 2); ?></p>
                </div>
            </div>
            <div class="glass p-4 rounded-2xl flex items-center gap-4 min-w-[140px]">
                <div class="bg-white/20 p-2 rounded-lg text-white font-bold">#</div>
                <div>
                    <p class="text-[10px] uppercase font-bold tracking-widest opacity-70">Staff</p>
                    <p class="text-xl font-bold"><?php echo $emp_count; ?></p>
                </div>
            </div>
        </div>
    </header>

    <?php if ($message): list($type, $txt) = explode('|', $message); ?>
        <div class="mb-6 p-4 rounded-xl glass border-l-4 <?php echo $type == 'success' ? 'border-emerald-400' : 'border-rose-400'; ?>">
            <?php echo $txt; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <div class="glass p-8 rounded-3xl">
            <h2 class="text-xl font-bold mb-6">Register Staff</h2>
            <form method="post" class="space-y-4 text-slate-800">
                <input type="text" name="name" placeholder="Full Name" class="glass-input w-full p-4 rounded-xl focus:ring-2 focus:ring-white/50 outline-none" required>
                <input type="number" step="0.01" name="hourly_rate" placeholder="Hourly Rate ($)" class="glass-input w-full p-4 rounded-xl focus:ring-2 focus:ring-white/50 outline-none" required>
                <button type="submit" name="add_employee" class="w-full bg-white text-indigo-700 font-bold p-4 rounded-xl hover:bg-opacity-90 transition transform hover:scale-[1.02]">Add Employee</button>
            </form>
        </div>

        <div class="glass p-8 rounded-3xl">
            <h2 class="text-xl font-bold mb-6">Process Payment</h2>
            <form method="post" class="space-y-4">
                <select name="employee_id" class="glass-input w-full p-4 rounded-xl focus:ring-2 focus:ring-white/50 outline-none" required>
                    <option value="">Select Employee</option>
                    <?php while ($row = $employees->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" step="0.01" name="hours_worked" placeholder="Hours" class="glass-input p-4 rounded-xl outline-none" required>
                    <input type="date" name="pay_date" class="glass-input p-4 rounded-xl outline-none" required>
                </div>
                <button type="submit" name="calculate_payroll" class="w-full bg-indigo-500 text-white border border-white/30 font-bold p-4 rounded-xl hover:bg-indigo-400 transition transform hover:scale-[1.02]">Save Record</button>
            </form>
        </div>
    </div>

    <div class="glass rounded-3xl overflow-hidden">
        <div class="p-6 border-b border-white/10">
            <h2 class="text-xl font-bold">Transaction History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white/10">
                    <tr>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-70">Employee</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-70">Hours</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-70">Net Pay</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-70">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    <?php
                    $records = $conn->query("SELECT e.name, p.hours_worked, p.net_pay, p.pay_date 
                                             FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.pay_date DESC");
                    while ($row = $records->fetch_assoc()): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="p-4 font-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="p-4 opacity-80"><?php echo $row['hours_worked']; ?>h</td>
                            <td class="p-4">
                                <span class="bg-emerald-400/20 text-emerald-200 border border-emerald-400/30 px-3 py-1 rounded-lg text-sm font-bold">
                                    $<?php echo number_format($row['net_pay'], 2); ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm opacity-60"><?php echo date("M d", strtotime($row['pay_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>