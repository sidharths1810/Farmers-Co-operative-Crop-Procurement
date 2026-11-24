<?php
include "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions on SQL errors

// Options shown in the drop-down lists (crop_id => crop_name)
$crop_list = [
    1  => "Rice",
    2  => "Banana",
    3  => "Pepper",
    4  => "Wheat",
    5  => "Coconut",
    6  => "Tapioca",
    7  => "Cardamom",
    8  => "Ginger",
    9  => "Maize",
    10 => "Sugarcane"
];
$season_list = ["Kharif", "Rabi", "Zaid", "Annual"];

$message = "";
$class   = "";
$notes   = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Farmer fields
    $farmer_id = trim($_POST["farmer_id"] ?? "");
    $name      = trim($_POST["name"] ?? "");
    $village   = trim($_POST["village"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    // Crop fields
    $crop_id   = trim($_POST["crop_id"] ?? "");
    $crop_name = $crop_list[(int)($_POST["crop_id"] ?? 0)] ?? "";
    $season    = trim($_POST["season"] ?? "");
    $price     = trim($_POST["price_per_kg"] ?? "");
    // Procurement fields
    $quantity  = trim($_POST["quantity_kg"] ?? "");
    $proc_date = trim($_POST["proc_date"] ?? "");

    if ($farmer_id === "" || $name === "" || $village === "" || $phone === "" ||
        $crop_id === "" || $crop_name === "" || $season === "" || $price === "" ||
        $quantity === "" || $proc_date === "") {
        $message = "Error: All fields are required.";
        $class = "error";
    } elseif (!ctype_digit($farmer_id) || !ctype_digit($crop_id)) {
        $message = "Error: Farmer ID and Crop ID must be whole numbers.";
        $class = "error";
    } elseif (!isset($crop_list[(int)$crop_id]) || !in_array($season, $season_list, true)) {
        $message = "Error: Please select a valid crop and season from the lists.";
        $class = "error";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "Error: Phone number must be 10 digits.";
        $class = "error";
    } elseif (!is_numeric($price) || $price <= 0 || !is_numeric($quantity) || $quantity <= 0) {
        $message = "Error: Price and quantity must be positive numbers.";
        $class = "error";
    } else {
        try {
            mysqli_begin_transaction($conn);

            // 1. Farmer: insert only if this farmer_id is new
            $stmt = mysqli_prepare($conn, "SELECT farmer_id FROM farmer WHERE farmer_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $farmer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $farmer_exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($farmer_exists) {
                $notes[] = "Farmer ID $farmer_id already exists, so the existing farmer details were used.";
            } else {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO farmer (farmer_id, name, village, phone) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isss", $farmer_id, $name, $village, $phone);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $notes[] = "New farmer saved.";
            }

            // 2. Crop: insert only if this crop_id is new
            $stmt = mysqli_prepare($conn, "SELECT crop_id FROM crop WHERE crop_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $crop_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $crop_exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($crop_exists) {
                $notes[] = "Crop ID $crop_id already exists, so the existing crop details were used.";
            } else {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO crop (crop_id, crop_name, season, price_per_kg) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "issd", $crop_id, $crop_name, $season, $price);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $notes[] = "New crop saved.";
            }

            // 3. Procurement record
            $stmt = mysqli_prepare($conn,
                "INSERT INTO procurement (farmer_id, crop_id, quantity_kg, proc_date) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iids", $farmer_id, $crop_id, $quantity, $proc_date);
            mysqli_stmt_execute($stmt);
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            $message = "Record saved successfully (Procurement ID: $new_id).";
            $class = "success";
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conn);
            $message = "Error: Record was not saved. " . $e->getMessage();
            $class = "error";
            $notes = [];
        }
    }
}

// Keep entered values in the form after an error
function old($key) {
    return ($_SERVER["REQUEST_METHOD"] === "POST") ? htmlspecialchars($_POST[$key] ?? "") : "";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crop Procurement Entry</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f4; }
        .box { width: 520px; margin: 30px auto; background: #fff; padding: 25px;
               border-radius: 8px; box-shadow: 0 0 8px #ccc; }
        h2 { text-align: center; color: #2e6b2e; }
        fieldset { margin-top: 15px; border: 1px solid #9bbf9b; border-radius: 6px; }
        legend { font-weight: bold; color: #2e6b2e; padding: 0 6px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        button { margin-top: 18px; width: 100%; padding: 10px; background: #2e6b2e;
                 color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px; }
        .error   { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 10px; }
        .info    { font-size: 13px; margin: 4px 0 0 18px; }
        .link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Crop Procurement Entry</h2>

    <?php
    if ($message != "") {
        echo "<div class='$class'>" . htmlspecialchars($message);
        foreach ($notes as $n) { echo "<div class='info'>&bull; " . htmlspecialchars($n) . "</div>"; }
        echo "</div>";
    }
    ?>

    <form method="post" action="procure.php">
        <fieldset>
            <legend>Farmer Details</legend>
            <label>Farmer ID</label>
            <input type="number" min="1" name="farmer_id" value="<?= old('farmer_id') ?>" required>
            <label>Name</label>
            <input type="text" name="name" maxlength="100" value="<?= old('name') ?>" required>
            <label>Village</label>
            <input type="text" name="village" maxlength="100" value="<?= old('village') ?>" required>
            <label>Phone</label>
            <input type="text" name="phone" maxlength="10" pattern="[0-9]{10}" title="10 digit phone number"
                   value="<?= old('phone') ?>" required>
        </fieldset>

        <fieldset>
            <legend>Crop Details</legend>
            <label>Crop (ID - Name)</label>
            <select name="crop_id" required>
                <option value="">-- Select Crop --</option>
                <?php foreach ($crop_list as $id => $cname) { ?>
                    <option value="<?= $id ?>" <?= old('crop_id') == $id ? 'selected' : '' ?>>
                        <?= $id . " - " . htmlspecialchars($cname) ?>
                    </option>
                <?php } ?>
            </select>
            <label>Season</label>
            <select name="season" required>
                <option value="">-- Select Season --</option>
                <?php foreach ($season_list as $sn) { ?>
                    <option value="<?= $sn ?>" <?= old('season') === $sn ? 'selected' : '' ?>><?= $sn ?></option>
                <?php } ?>
            </select>
            <label>Price per kg (₹)</label>
            <input type="number" step="0.01" min="0.01" name="price_per_kg" value="<?= old('price_per_kg') ?>" required>
        </fieldset>

        <fieldset>
            <legend>Procurement Details</legend>
            <label>Quantity (kg)</label>
            <input type="number" step="0.01" min="0.01" name="quantity_kg" value="<?= old('quantity_kg') ?>" required>
            <label>Procurement Date</label>
            <input type="date" name="proc_date"
                   value="<?= old('proc_date') !== '' ? old('proc_date') : date('Y-m-d') ?>" required>
        </fieldset>

        <button type="submit" name="submit">Submit</button>
    </form>

    <div class="link"><a href="report.php">View Procurement Report</a></div>
</div>
</body>
</html>