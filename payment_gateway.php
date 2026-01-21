<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['asset_id'])) {
    header("Location: browse.php");
    exit;
}

$asset_id = intval($_GET['asset_id']);

// Fetch Asset Info
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    die("Asset not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="dashboard-container">
        
        <header class="dash-header" style="display:flex; align-items:center; justify-content:space-between; padding: 20px 40px; border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div class="logo-area" style="margin:0;">
                <a href="asset_details.php?id=<?php echo $asset_id; ?>" style="color:white; text-decoration:none; display:flex; align-items:center; gap:5px; font-weight:500;">
                    <ion-icon name="arrow-back-outline"></ion-icon> 
                    <span>Cancel</span>
                </a>
            </div>
            <h2 style="font-size:1rem; font-weight:600; opacity:0.7; letter-spacing:0.5px; text-transform:uppercase;">Secure Checkout</h2>
        </header>

        <div style="max-width:500px; margin:40px auto; display:flex; gap:20px; flex-direction:column;">
            
            <!-- Order Summary -->
            <div style="background:#1e1e1e; padding:20px; border-radius:10px;">
                <h3 style="margin-bottom:15px; color:#ddd;">Order Summary</h3>
                <div style="display:flex; gap:15px; align-items:center;">
                    <div style="width:60px; height:60px; background:#333; border-radius:8px; overflow:hidden;">
                        <?php if ($asset['thumbnail_path']): ?>
                            <img src="<?php echo htmlspecialchars($asset['thumbnail_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background:linear-gradient(45deg, #444, #222);"></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($asset['title']); ?></div>
                        <div style="font-size:12px; color:#aaa; margin-top:5px;">Standard License</div>
                    </div>
                    <div style="font-size:18px; font-weight:bold; color:#38ef7d;">
                        ₹<?php echo number_format($asset['price'], 2); ?>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div style="background:#1e1e1e; padding:20px; border-radius:10px;">
                <h3 style="margin-bottom:20px; color:#ddd;">Payment Details</h3>
                
                <form id="paymentForm" action="purchase.php" method="POST">
                    <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                    <input type="hidden" name="payment_method" value="card">
                    
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" name="cardholder_name" id="cardholder_name" placeholder="John Doe" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white;">
                        <small class="error-msg" id="nameError" style="color:#ff4d4d; font-size:11px; display:none; margin-top:5px;"></small>
                    </div>

                    <div class="form-group">
                        <label>Card Number</label>
                        <div style="position:relative;">
                            <input type="text" name="card_number" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white; letter-spacing:1px;">
                            <ion-icon name="card-outline" style="position:absolute; right:12px; top:12px; color:#aaa;"></ion-icon>
                        </div>
                        <small class="error-msg" id="cardError" style="color:#ff4d4d; font-size:11px; display:none; margin-top:5px;"></small>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" name="expiry_date" id="expiry_date" placeholder="MM/YY" maxlength="5" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white;">
                            <small class="error-msg" id="expiryError" style="color:#ff4d4d; font-size:11px; display:none; margin-top:5px;"></small>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" name="cvv" id="cvv" placeholder="123" maxlength="4" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white;">
                            <small class="error-msg" id="cvvError" style="color:#ff4d4d; font-size:11px; display:none; margin-top:5px;"></small>
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap:10px; font-size:12px; color:#aaa; margin-top:10px;">
                        <input type="checkbox" name="terms" checked required>
                        I agree to the Terms of Service and Refund Policy
                    </label>

                    <button type="submit" id="payButton" class="btn-primary" style="margin-top:20px; width:100%; font-size:16px;">
                        Pay ₹<?php echo number_format($asset['price'], 2); ?>
                    </button>


                    <div style="text-align:center; margin-top:15px; font-size:12px; color:#555;">
                        <ion-icon name="lock-closed"></ion-icon> Secure Encrypted Transaction (Mock)
                    </div>
                </form>

                <script>
                document.getElementById('card_number').addEventListener('input', function (e) {
                    let target = e.target;
                    let position = target.selectionStart;
                    let length = target.value.length;
                    
                    target.value = target.value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ').trim();
                    
                    // Maintain cursor position
                    if(length !== target.value.length && position !== length) {
                        target.selectionEnd = position + (target.value.length - length);
                    }
                });

                document.getElementById('expiry_date').addEventListener('input', function (e) {
                    let target = e.target;
                    let val = target.value.replace(/\D/g, '');
                    if (val.length > 2) {
                        target.value = val.substring(0, 2) + '/' + val.substring(2, 4);
                    } else {
                        target.value = val;
                    }
                });

                document.getElementById('cvv').addEventListener('input', function (e) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });

                document.getElementById('paymentForm').addEventListener('submit', function (e) {
                    let hasError = false;
                    const cardNum = document.getElementById('card_number').value.replace(/\s/g, '');
                    const expiry = document.getElementById('expiry_date').value;
                    const cvv = document.getElementById('cvv').value;
                    const name = document.getElementById('cardholder_name').value;

                    // Reset errors
                    document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('input').forEach(el => el.style.borderColor = '#333');

                    if (name.length < 3) {
                        const err = document.getElementById('nameError');
                        err.innerText = "Please enter full name";
                        err.style.display = 'block';
                        document.getElementById('cardholder_name').style.borderColor = '#ff4d4d';
                        hasError = true;
                    }

                    if (cardNum.length !== 16) {
                        const err = document.getElementById('cardError');
                        err.innerText = "Enter a valid 16-digit card number";
                        err.style.display = 'block';
                        document.getElementById('card_number').style.borderColor = '#ff4d4d';
                        hasError = true;
                    }

                    if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                        const err = document.getElementById('expiryError');
                        err.innerText = "Use MM/YY format";
                        err.style.display = 'block';
                        document.getElementById('expiry_date').style.borderColor = '#ff4d4d';
                        hasError = true;
                    } else {
                        const parts = expiry.split('/');
                        const month = parseInt(parts[0]);
                        const year = parseInt('20' + parts[1]);
                        const now = new Date();
                        const currentMonth = now.getMonth() + 1;
                        const currentYear = now.getFullYear();

                        if (month < 1 || month > 12) {
                            const err = document.getElementById('expiryError');
                            err.innerText = "Invalid month";
                            err.style.display = 'block';
                            document.getElementById('expiry_date').style.borderColor = '#ff4d4d';
                            hasError = true;
                        } else if (year < currentYear || (year === currentYear && month < currentMonth)) {
                            const err = document.getElementById('expiryError');
                            err.innerText = "Card has expired";
                            err.style.display = 'block';
                            document.getElementById('expiry_date').style.borderColor = '#ff4d4d';
                            hasError = true;
                        }
                    }

                    if (cvv.length < 3 || cvv.length > 4) {
                        const err = document.getElementById('cvvError');
                        err.innerText = "Invalid CVV (3-4 digits)";
                        err.style.display = 'block';
                        document.getElementById('cvv').style.borderColor = '#ff4d4d';
                        hasError = true;
                    }

                    if (hasError) {
                        e.preventDefault();
                    } else {
                        const btn = document.getElementById('payButton');
                        btn.disabled = true;
                        btn.innerHTML = '<ion-icon name="sync-outline" class="rotate" style="animation: spin 1s linear infinite;"></ion-icon> Processing...';
                        btn.style.opacity = '0.7';
                    }
                });

                // Add spin animation
                const style = document.createElement('style');
                style.innerHTML = `
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                    .rotate {
                        display: inline-block;
                        vertical-align: middle;
                        margin-right: 5px;
                    }
                `;
                document.head.appendChild(style);
                </script>

            </div>

        </div>
    </div>
</body>
</html>
