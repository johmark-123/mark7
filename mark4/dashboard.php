<?php
require 'db.php';

// 1. SECURITY CHECK: Kick out anyone not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=error|Please login to access the dashboard.");
    exit;
}

$message = "";

// 2. HANDLE ACTIONS (Add Employee / Process Payroll)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_employee'])) {
        $stmt = $conn->prepare("INSERT INTO employees (name, hourly_rate) VALUES (?, ?)");
        $stmt->bind_param("sd", $_POST['name'], $_POST['hourly_rate']);
        $message = $stmt->execute() ? "success|Employee successfully added." : "error|Failed to add employee.";
    } 
    elseif (isset($_POST['calculate_payroll'])) {
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
            $message = $ins->execute() ? "success|Payroll record saved!" : "error|Failed to save record.";
        }
    }
}

// 3. FETCH DATA
$total_paid = $conn->query("SELECT SUM(net_pay) as total FROM payroll")->fetch_assoc()['total'] ?? 0;
$emp_count = $conn->query("SELECT COUNT(*) as total FROM employees")->fetch_assoc()['total'] ?? 0;
$employees = $conn->query("SELECT id, name FROM employees");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Payroll Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            background-attachment: fixed; 
            min-height: 100vh; 
            color: white; 
        }
        .glass { 
            background: rgba(255, 255, 255, 0.12); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(255, 255, 255, 0.2); 
            box-shadow: 0 8px 32px rgba(0,0,0,0.25); 
        }
        .glass-input { 
            background: rgba(255, 255, 255, 0.08); 
            border: 1px solid rgba(255, 255, 255, 0.2); 
            color: white; 
            transition: 0.3s;
        }
        .glass-input:focus { border-color: rgba(255, 255, 255, 0.6); background: rgba(255, 255, 255, 0.15); }
        .glass-input option { color: #1a202c; }
    </style>
</head>
<body class="p-6 md:p-12">

<div class="max-w-6xl mx-auto">
    <nav class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Payroll Dashboard</h1>
            <p class="text-blue-200 text-sm">Active Session: <span class="font-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></span></p>
        </div>
        <div class="flex items-center gap-4">
            <div class="glass px-6 py-3 rounded-2xl text-center">
                <p class="text-[10px] uppercase font-bold opacity-60">Total Paid</p>
                <p class="text-xl font-bold">$<?php echo number_format($total_paid, 2); ?></p>
            </div>
            <a href="login.php?logout=1" class="bg-rose-500/80 hover:bg-rose-600 text-white px-6 py-3 rounded-2xl font-bold transition shadow-lg">Logout</a>
        </div>
    </nav>

    <?php echo getMessage($message); ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <section class="glass p-8 rounded-3xl">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                <span class="w-2 h-6 bg-blue-400 rounded-full"></span> New Hire Registration
            </h2>
            <form method="post" class="space-y-4">
                <input type="text" name="name" placeholder="Full Name" class="glass-input w-full p-4 rounded-xl outline-none" required>
                <input type="number" step="0.01" name="hourly_rate" placeholder="Hourly Rate ($)" class="glass-input w-full p-4 rounded-xl outline-none" required>
                <button type="submit" name="add_employee" class="w-full bg-white text-indigo-700 font-bold p-4 rounded-xl hover:bg-opacity-90 transition transform hover:scale-[1.01]">Save Employee</button>
            </form>
        </section>

        <section class="glass p-8 rounded-3xl">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                <span class="w-2 h-6 bg-emerald-400 rounded-full"></span> Generate Paycheck
            </h2>
            <form method="post" class="space-y-4">
                <select name="employee_id" class="glass-input w-full p-4 rounded-xl outline-none" required>
                    <option value="">Choose Staff Member...</option>
                    <?php while ($row = $employees->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" step="0.01" name="hours_worked" placeholder="Hours" class="glass-input p-4 rounded-xl outline-none" required>
                    <input type="date" name="pay_date" class="glass-input p-4 rounded-xl outline-none" required>
                </div>
                <button type="submit" name="calculate_payroll" class="w-full bg-indigo-500 text-white border border-white/20 font-bold p-4 rounded-xl hover:bg-indigo-400 transition transform hover:scale-[1.01]">Finalize Payment</button>
            </form>
        </section>
    </div>

    <div class="glass rounded-3xl overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-xl font-bold">Payroll Log</h2>
            <span class="text-xs font-bold bg-white/10 px-3 py-1 rounded-full uppercase tracking-tighter"><?php echo $emp_count; ?> Employees Total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white/5">
                    <tr>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-50">Name</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-50 text-center">Hours</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-50">Net Amount</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-widest opacity-50">Issue Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php
                    $records = $conn->query("SELECT e.name, p.hours_worked, p.net_pay, p.pay_date 
                                             FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.pay_date DESC");
                    while ($row = $records->fetch_assoc()): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="p-4 font-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="p-4 text-center"><?php echo $row['hours_worked']; ?></td>
                            <td class="p-4">
                                <span class="bg-emerald-400/20 text-emerald-200 border border-emerald-400/20 px-4 py-1.5 rounded-xl font-bold shadow-sm">
                                    $<?php echo number_format($row['net_pay'], 2); ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm opacity-60"><?php echo date("M d, Y", strtotime($row['pay_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>