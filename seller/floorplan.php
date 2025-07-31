<?php
// Handle shape clicks
$clicked_shape = '';
$message = '';

if ($_POST) {
    if (isset($_POST['shape'])) {
        $clicked_shape = $_POST['shape'];
        $message = "You clicked on: " . ucfirst(str_replace('_', ' ', $clicked_shape));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Visual Map</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .message {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .map-container {
            width: 800px;
            height: 600px;
            background-color: #e8e8e8;
            position: relative;
            border: 2px solid #ccc;
            border-radius: 8px;
        }

        /* Clickable shapes */
        .clickable {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clickable:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        /* Center stacked rectangles */
        .center-rect {
            width: 120px;
            height: 50px;
            background-color: #4a90e2;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            border: none;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .center-rect:nth-child(1) {
            top: 220px;
        }

        .center-rect:nth-child(2) {
            top: 285px;
        }

        .center-rect:nth-child(3) {
            top: 350px;
        }

        /* Side squares */
        .side-square {
            width: 80px;
            height: 80px;
            background-color: #7b7b7b;
            position: absolute;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            border: none;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        /* Top squares */
        .top-left {
            top: 80px;
            left: 120px;
        }

        .top-center {
            top: 80px;
            left: 360px;
        }

        .top-right {
            top: 80px;
            right: 120px;
        }

        /* Bottom squares */
        .bottom-left {
            bottom: 80px;
            left: 120px;
        }

        .bottom-center {
            bottom: 80px;
            left: 360px;
        }

        .bottom-right {
            bottom: 80px;
            right: 120px;
        }

        /* Left and right side squares */
        .left-side {
            top: 50%;
            left: 40px;
            transform: translateY(-50%);
        }

        .right-side {
            top: 50%;
            right: 40px;
            transform: translateY(-50%);
        }

        /* Pathway lines */
        .pathway {
            position: absolute;
            background-color: #d4d4d4;
            border-radius: 2px;
        }

        /* Horizontal pathways connecting to center */
        .pathway-h {
            height: 4px;
            top: 50%;
            transform: translateY(-50%);
        }

        .pathway-h.left {
            left: 130px;
            width: 210px;
            top: 300px;
        }

        .pathway-h.right {
            right: 130px;
            width: 210px;
            top: 300px;
        }

        /* Vertical pathways */
        .pathway-v {
            width: 4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .pathway-v.top {
            top: 170px;
            height: 40px;
        }

        .pathway-v.bottom {
            bottom: 170px;
            height: 40px;
        }

        /* Highlight clicked shape */
        .clicked {
            background-color: #ff6b6b !important;
            animation: pulse 0.5s ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" id="mapForm">
        <div class="map-container">
            <!-- Center blue rectangles -->
            <button type="submit" name="shape" value="center_rect_1" 
                    class="center-rect clickable <?php echo ($clicked_shape == 'center_rect_1') ? 'clicked' : ''; ?>">
                Rect 1
            </button>
            <button type="submit" name="shape" value="center_rect_2" 
                    class="center-rect clickable <?php echo ($clicked_shape == 'center_rect_2') ? 'clicked' : ''; ?>">
                Rect 2
            </button>
            <button type="submit" name="shape" value="center_rect_3" 
                    class="center-rect clickable <?php echo ($clicked_shape == 'center_rect_3') ? 'clicked' : ''; ?>">
                Rect 3
            </button>

            <!-- Side squares -->
            <button type="submit" name="shape" value="top_left" 
                    class="side-square top-left clickable <?php echo ($clicked_shape == 'top_left') ? 'clicked' : ''; ?>">
                TL
            </button>
            <button type="submit" name="shape" value="top_center" 
                    class="side-square top-center clickable <?php echo ($clicked_shape == 'top_center') ? 'clicked' : ''; ?>">
                TC
            </button>
            <button type="submit" name="shape" value="top_right" 
                    class="side-square top-right clickable <?php echo ($clicked_shape == 'top_right') ? 'clicked' : ''; ?>">
                TR
            </button>
            <button type="submit" name="shape" value="bottom_left" 
                    class="side-square bottom-left clickable <?php echo ($clicked_shape == 'bottom_left') ? 'clicked' : ''; ?>">
                BL
            </button>
            <button type="submit" name="shape" value="bottom_center" 
                    class="side-square bottom-center clickable <?php echo ($clicked_shape == 'bottom_center') ? 'clicked' : ''; ?>">
                BC
            </button>
            <button type="submit" name="shape" value="bottom_right" 
                    class="side-square bottom-right clickable <?php echo ($clicked_shape == 'bottom_right') ? 'clicked' : ''; ?>">
                BR
            </button>
            <button type="submit" name="shape" value="left_side" 
                    class="side-square left-side clickable <?php echo ($clicked_shape == 'left_side') ? 'clicked' : ''; ?>">
                L
            </button>
            <button type="submit" name="shape" value="right_side" 
                    class="side-square right-side clickable <?php echo ($clicked_shape == 'right_side') ? 'clicked' : ''; ?>">
                R
            </button>

            <!-- Pathways -->
            <div class="pathway pathway-h left"></div>
            <div class="pathway pathway-h right"></div>
            <div class="pathway pathway-v top"></div>
            <div class="pathway pathway-v bottom"></div>
        </div>
    </form>
</body>
</html>