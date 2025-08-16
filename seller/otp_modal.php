<?php
// otp_modal.php - OTP Verification Modal Component
?>

<!-- OTP Verification Modal -->
<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="otpModalLabel">
                    <i class="fas fa-envelope-open-text me-2"></i>Email Verification
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="otp-icon mb-3">
                    <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                </div>
                <h6>Verify Your Email Address</h6>
                <p class="text-muted mb-4">
                    We've sent a 6-digit verification code to:<br>
                    <strong id="otpEmailDisplay"><?php echo htmlspecialchars($_SESSION['signup_data']['email'] ?? 'your email'); ?></strong>
                </p>
                
                <div class="otp-input-container mb-3">
                    <input type="text" 
                           id="otpInput" 
                           class="form-control form-control-lg text-center" 
                           maxlength="6" 
                           placeholder="Enter 6-digit code"
                           style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                </div>
                
                <div id="otpError" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="otpErrorMessage"></span>
                </div>
                
                <div id="otpSuccess" class="alert alert-success" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    Email verified successfully!
                </div>
                
                <div class="otp-timer mb-3">
                    <small class="text-muted">
                        Didn't receive the code? 
                        <button type="button" id="resendOtpBtn" class="btn btn-link p-0 text-decoration-none" disabled>
                            Resend in <span id="resendTimer">60</span>s
                        </button>
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="verifyOtpBtn" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-check me-2"></i>Verify Code
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* OTP Modal Styles */
#otpModal .modal-content {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

#otpModal .modal-header {
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
}

#otpModal .modal-header .modal-title {
    font-weight: 600;
}

.otp-input-container {
    max-width: 250px;
    margin: 0 auto;
}

#otpInput {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

#otpInput:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

#otpInput.is-valid {
    border-color: #28a745;
}

#otpInput.is-invalid {
    border-color: #dc3545;
}

.otp-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

#verifyOtpBtn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

#verifyOtpBtn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

#verifyOtpBtn:disabled {
    background: #6c757d;
    transform: none;
    box-shadow: none;
}

.btn-link {
    font-size: 0.875rem;
}

.btn-link:hover {
    text-decoration: underline !important;
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let resendCountdown = 60;
    let countdownInterval;
    
    const otpInput = document.getElementById('otpInput');
    const verifyBtn = document.getElementById('verifyOtpBtn');
    const resendBtn = document.getElementById('resendOtpBtn');
    const resendTimer = document.getElementById('resendTimer');
    const errorDiv = document.getElementById('otpError');
    const errorMessage = document.getElementById('otpErrorMessage');
    const successDiv = document.getElementById('otpSuccess');

    // Start countdown timer
    function startResendTimer() {
        resendCountdown = 60;
        resendBtn.disabled = true;
        countdownInterval = setInterval(function() {
            resendCountdown--;
            resendTimer.textContent = resendCountdown;
            
            if (resendCountdown <= 0) {
                clearInterval(countdownInterval);
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'Resend Code';
            }
        }, 1000);
    }

    // Show error message
    function showError(message) {
        errorMessage.textContent = message;
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
        otpInput.classList.add('is-invalid');
        otpInput.classList.remove('is-valid');
    }

    // Show success message
    function showSuccess() {
        successDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        otpInput.classList.add('is-valid');
        otpInput.classList.remove('is-invalid');
    }

    // Hide messages
    function hideMessages() {
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        otpInput.classList.remove('is-valid', 'is-invalid');
    }

    // Auto-format OTP input (numbers only)
    otpInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        hideMessages();
        
        if (this.value.length === 6) {
            verifyBtn.disabled = false;
        } else {
            verifyBtn.disabled = true;
        }
    });

    // Enter key to verify
    otpInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.length === 6) {
            verifyOtpBtn.click();
        }
    });

    // Verify OTP
    verifyBtn.addEventListener('click', function() {
        const otp = otpInput.value.trim();

        if (otp.length !== 6) {
            showError("Please enter a 6-digit code.");
            return;
        }

        // Disable button and show loading
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';

        fetch('verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'otp=' + encodeURIComponent(otp)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccess();
                setTimeout(function() {
                    $('#otpModal').modal('hide');
                    // Proceed to step 2
                    window.location.href = 'signup.php?step=2';
                }, 1500);
            } else {
                showError(data.message || 'Invalid verification code. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Network error. Please check your connection and try again.');
        })
        .finally(() => {
            // Re-enable button
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Verify Code';
        });
    });

    // Resend OTP
    resendBtn.addEventListener('click', function() {
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

        fetch('send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                hideMessages();
                otpInput.value = '';
                startResendTimer();
            } else {
                showError(data.message || 'Failed to resend code. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Network error. Please try again.');
        })
        .finally(() => {
            if (!resendBtn.disabled) {
                resendBtn.innerHTML = 'Resend Code';
            }
        });
    });

    // Initialize timer when modal is shown
    $('#otpModal').on('shown.bs.modal', function() {
        startResendTimer();
        otpInput.focus();
    });

    // Clean up timer when modal is hidden
    $('#otpModal').on('hidden.bs.modal', function() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });
});
</script>