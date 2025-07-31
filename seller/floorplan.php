<?php
// Handle stall selection
$selected_stall = '';
$message = '';

if ($_POST) {
    if (isset($_POST['stall'])) {
        $selected_stall = $_POST['stall'];
        
        // Determine vendor type for message
        $vendor_type = '';
        if (strpos($selected_stall, 'F') === 0) {
            $vendor_type = ' (Fish Vendor)';
        } elseif (strpos($selected_stall, 'M') === 0) {
            $vendor_type = ' (Meat Vendor)';
        }
        
        $message = "You selected stall: " . strtoupper($selected_stall) . $vendor_type . " - Click 'Register' to confirm your market spot!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Stall Registration Map</title>
    <link rel="stylesheet" href="../assets/css/floorplan.css">
</head>
<body>
    <div class="header">
        <h1>Market Stall Registration</h1>
        <p>Click on any available stall to select your market spot</p>
    </div>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" id="marketForm">
        <div class="market-container">
            <!-- Top row stalls -->
            <?php for ($i = 1; $i <= 11; $i++): ?>
                <button type="submit" name="stall" value="T<?php echo $i; ?>"
                    class="stall square-stall top-<?php echo $i; ?> <?php echo ($selected_stall == 'T'.$i) ? 'selected' : ''; ?>">
                    T<?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <!-- Bottom row stalls -->
            <?php for ($i = 1; $i <= 11; $i++): ?>
                <button type="submit" name="stall" value="B<?php echo $i; ?>"
                    class="stall square-stall bottom-<?php echo $i; ?> <?php echo ($selected_stall == 'B'.$i) ? 'selected' : ''; ?>">
                    B<?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <!-- Left column stalls -->
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <button type="submit" name="stall" value="L<?php echo $i; ?>"
                    class="stall square-stall left-<?php echo $i; ?> <?php echo ($selected_stall == 'L'.$i) ? 'selected' : ''; ?>">
                    L<?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <!-- Right column stalls -->
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <button type="submit" name="stall" value="R<?php echo $i; ?>"
                    class="stall square-stall right-<?php echo $i; ?> <?php echo ($selected_stall == 'R'.$i) ? 'selected' : ''; ?>">
                    R<?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <!-- Fish Vendors (Left Section) - F1 to F16 -->
            <?php for ($i = 1; $i <= 16; $i++): ?>
                <button type="submit" name="stall" value="F<?php echo $i; ?>"
                    class="stall fish-vendor fish-<?php echo $i; ?> <?php echo ($selected_stall == 'F'.$i) ? 'selected' : ''; ?>">
                    F<?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <!-- Meat Vendors (Right Section) - M1 to M16 -->
            <?php for ($i = 1; $i <= 16; $i++): ?>
                <button type="submit" name="stall" value="M<?php echo $i; ?>"
                    class="stall meat-vendor meat-<?php echo $i; ?> <?php echo ($selected_stall == 'M'.$i) ? 'selected' : ''; ?>">
                    M<?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <!-- Center Circle -->
            <div class="center-circle">
                Market<br>Center
            </div>
        </div>

        <div class="controls">
            <?php if ($selected_stall): ?>
                <button type="button" class="register-btn"
                    onclick="alert('Registration confirmed for stall <?php echo $selected_stall; ?>!')">
                    Register for Stall <?php echo $selected_stall; ?>
                </button>
            <?php else: ?>
                <button type="button" class="register-btn" disabled>
                    Select a stall first
                </button>
            <?php endif; ?>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color available"></div>
                <span>General Stalls</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #20b2aa; border-color: #1a9a91;"></div>
                <span>Fish Vendors</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #dc3545; border-color: #c82333;"></div>
                <span>Meat Vendors</span>
            </div>
            <?php if ($selected_stall): ?>
            <div class="legend-item">
                <div class="legend-color selected-legend"></div>
                <span>Selected Stall</span>
            </div>
            <?php endif; ?>
        </div>
    </form>
</body>
</html>