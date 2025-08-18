<!-- Error Modal when Submitting a Complaint -->
<?php
function showErrorModal($message, $goBack = false)
{
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>
        <style>
            body {
                margin: 0;
                font-family: 'Inter', sans-serif;
                background: rgba(0, 0, 0, 0.5);
            }
            .error-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2.5rem;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 90%;
                width: 400px;
                animation: modalFadeIn 0.5s ease-out forwards;
            }
            @keyframes modalFadeIn {
                from { 
                    opacity: 0; 
                    transform: translate(-50%, -60%);
                }
                to { 
                    opacity: 1; 
                    transform: translate(-50%, -50%);
                }
            }
            .error-icon {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: #dc3545;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                animation: iconScale 0.5s ease-out 0.2s forwards;
                transform: scale(0);
            }
            @keyframes iconScale {
                from { transform: scale(0); }
                to { transform: scale(1); }
            }
            .error-icon i {
                color: white;
                font-size: 2.5rem;
            }
            .modal-title {
                color: #2d3436;
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }
            .modal-message {
                color: #636e72;
                font-size: 1rem;
                line-height: 1.5;
                margin-bottom: 1.5rem;
            }
            .modal-button {
                background: #dc3545;
                color: white;
                border: none;
                padding: 0.8rem 2rem;
                border-radius: 8px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .modal-button:hover {
                background: #c82333;
                transform: translateY(-2px);
            }
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(5px);
                animation: overlayFadeIn 0.5s ease-out forwards;
            }
            @keyframes overlayFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
    </head>
    <body>
        <div class='modal-overlay'>
            <div class='error-modal'>
                <div class='error-icon'>
                    <i class='bi bi-x-lg'></i>
                </div>
                <h4 class='modal-title'>Error</h4>
                <p class='modal-message'>" . htmlspecialchars($message) . "</p>
                <button class='modal-button' onclick='" . ($goBack ? "window.history.back()" : "window.location.href='index.php'") . "'>
                    " . ($goBack ? 'Go Back' : 'Back to Home') . "
                </button>
            </div>
        </div>
    </body>
    </html>";
}
?>