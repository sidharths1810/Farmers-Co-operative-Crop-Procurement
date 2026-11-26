<?php
include "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Load crop options directly from the database.
$crop_result = mysqli_query($conn, "SELECT crop_id, crop_name FROM crop ORDER BY CAST(crop_id AS UNSIGNED) ASC");
$crop_list = [];
while ($crop = mysqli_fetch_assoc($crop_result)) {
    $crop_list[$crop['crop_id']] = $crop['crop_name'];
}

$message = "";
$class = "";
$notes = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $farmer_id = trim($_POST["farmer_id"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $village = trim($_POST["village"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $crop_id = trim($_POST["crop_id"] ?? "");
    $quantity = trim($_POST["quantity_kg"] ?? "");
    $proc_date = trim($_POST["proc_date"] ?? "");

    if ($farmer_id === "" || $name === "" || $village === "" || $phone === "" ||
        $crop_id === "" || $quantity === "" || $proc_date === "") {
        $message = "Error: All fields are required.";
        $class = "error";
    } elseif (!ctype_digit($farmer_id) || (int)$farmer_id < 1 ||
              !ctype_digit($crop_id) || !isset($crop_list[(int)$crop_id])) {
        $message = "Error: Please enter a valid Farmer ID and select a crop.";
        $class = "error";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "Error: Phone number must be 10 digits.";
        $class = "error";
    } elseif (!is_numeric($quantity) || (float)$quantity <= 0) {
        $message = "Error: Quantity must be a positive number.";
        $class = "error";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $proc_date) ||
              !checkdate((int)substr($proc_date, 5, 2), (int)substr($proc_date, 8, 2), (int)substr($proc_date, 0, 4))) {
        $message = "Error: Please enter a valid procurement date.";
        $class = "error";
    } else {
        // Do not allow a procurement submission when the Farmer ID already exists.
        $stmt = mysqli_prepare($conn, "SELECT farmer_id FROM farmer WHERE farmer_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $farmer_id);
        mysqli_stmt_execute($stmt);
        $existing_farmer = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);

        if ($existing_farmer) {
            $message = "Warning: Farmer ID $farmer_id already exists. No data has been submitted. Please enter a new Farmer ID.";
            $class = "error";
        } else {
            try {
                mysqli_begin_transaction($conn);

                // Save the new farmer details.
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO farmer (farmer_id, name, village, phone) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isss", $farmer_id, $name, $village, $phone);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Confirm that the selected crop still exists in the database.
                $stmt = mysqli_prepare($conn, "SELECT crop_id FROM crop WHERE crop_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $crop_id);
                mysqli_stmt_execute($stmt);
                $crop_exists = mysqli_stmt_get_result($stmt)->fetch_assoc();
                mysqli_stmt_close($stmt);
                if (!$crop_exists) {
                    throw new Exception("The selected crop is no longer available. Please reload the page.");
                }

                // Save the procurement record only for a new farmer ID.
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO procurement (farmer_id, crop_id, quantity_kg, proc_date) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iids", $farmer_id, $crop_id, $quantity, $proc_date);
                mysqli_stmt_execute($stmt);
                $new_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
                $message = "Record saved successfully (Procurement ID: $new_id).";
                $class = "success";
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $message = "Error: Record was not saved. " . $e->getMessage();
                $class = "error";
            }
        }
    }
}

function old($key) {
    return ($_SERVER["REQUEST_METHOD"] === "POST") ? htmlspecialchars($_POST[$key] ?? "", ENT_QUOTES, "UTF-8") : "";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crop Procurement Entry</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f4; }
        .box { width: 520px; max-width: calc(100% - 50px); margin: 30px auto; background: #fff; padding: 25px;
               border-radius: 8px; box-shadow: 0 0 8px #ccc; }
        h2 { text-align: center; color: #2e6b2e; }
        fieldset { margin-top: 15px; border: 1px solid #9bbf9b; border-radius: 6px; }
        legend { font-weight: bold; color: #2e6b2e; padding: 0 6px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        button { margin-top: 18px; width: 100%; padding: 10px; background: #2e6b2e;
                 color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 10px; }
        .info { font-size: 13px; margin: 4px 0 0 18px; }
        .hint { font-size: 13px; color: #555; margin-top: 6px; }
        .link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Crop Procurement Entry</h2>

    <?php if ($message !== "") { ?>
        <div class="<?= htmlspecialchars($class) ?>">
            <?= htmlspecialchars($message) ?>
            <?php foreach ($notes as $note) { ?>
                <div class="info">&bull; <?= htmlspecialchars($note) ?></div>
            <?php } ?>
        </div>
    <?php } ?>

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
            <input type="text" name="phone" maxlength="10" pattern="[0-9]{10}"
                   title="10 digit phone number" value="<?= old('phone') ?>" required>
            <div class="hint">Each Farmer ID must be unique. An existing ID will stop the submission.</div>
        </fieldset>

        <fieldset>
            <legend>Crop Details</legend>
            <label>Crop (ID - Name)</label>
            <select name="crop_id" required>
                <option value="">-- Select Crop --</option>
                <?php foreach ($crop_list as $id => $cname) { ?>
                    <option value="<?= (int)$id ?>" <?= old('crop_id') == $id ? 'selected' : '' ?>>
                        <?= (int)$id . " - " . htmlspecialchars($cname) ?>
                    </option>
                <?php } ?>
            </select>
            <div class="hint">Crop options are loaded from the crop table. The saved crop price is used in the report.</div>
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
