<?php
include "db.php";

$sql = "SELECT f.name, f.village, c.crop_name, p.quantity_kg,
               (c.price_per_kg * p.quantity_kg) AS amount, p.proc_date
        FROM procurement p
        JOIN farmer f ON p.farmer_id = f.farmer_id
        JOIN crop c ON p.crop_id = c.crop_id
        ORDER BY p.proc_date DESC, p.proc_id DESC";

$result = mysqli_query($conn, $sql);
$total = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Procurement Report</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f4; }
        .box { width: 96%; margin: 30px auto; background: #fff; padding: 25px;
               border-radius: 8px; box-shadow: 0 0 8px #ccc; }
        h2 { text-align: center; color: #2e6b2e; }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; white-space: nowrap; }
        th { background: #2e6b2e; color: #fff; }
        td.num { text-align: right; }
        tr.total td { font-weight: bold; background: #e6f0e6; }
        .link { margin-top: 15px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Crop Procurement Report</h2>

    <div class="scroll">
    <table>
        <tr>
            <th>Farmer Name</th>
            <th>Village</th>
            <th>Crop Name</th>
            <th>Quantity (kg)</th>
            <th>Amount (₹)</th>
            <th>Date</th>
        </tr>
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $total += (float)$row['amount'];
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['village'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['crop_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td class='num'>" . number_format((float)$row['quantity_kg'], 2) . "</td>";
                echo "<td class='num'>" . number_format((float)$row['amount'], 2) . "</td>";
                echo "<td>" . date('d-m-Y', strtotime($row['proc_date'])) . "</td>";
                echo "</tr>";
            }
            echo "<tr class='total'><td colspan='4' class='num'>Total Amount</td>" .
                 "<td class='num'>" . number_format($total, 2) . "</td><td></td></tr>";
        } else {
            echo "<tr><td colspan='6'>No procurement records found.</td></tr>";
        }
        ?>
    </table>
    </div>

    <div class="link"><a href="procure.php">&laquo; Back to Procurement Entry</a></div>
</div>
</body>
</html>
