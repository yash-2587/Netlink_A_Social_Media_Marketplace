<?php
session_start();

if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['otp']) || isset($_POST['request_otp_again'])) {
    $_SESSION['otp'] = rand(1000, 9999); 
    $_SESSION['otp_expiry'] = time() + 30; 
   
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="css/otp.css" />
    <style>
        .virtual-keyboard {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
            width: 150px;
            margin-left: auto;
            margin-right: auto;
        }

        .virtual-key {
            padding: 15px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            font-size: 18px;
        }

        .virtual-key:hover {
            background-color: #ddd;
        }

        .container {
            text-align: center;
            margin-top: 50px;
        }

        .row {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .row input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        #request {
            color: blue;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>OTP Verification</h2>
        <p>Please type the verification code sent to your registered mobile number
        </p>
        <p>The OTP will expire in <span id="expire">30</span> Sec</p>
        <form method="POST" action="execute_file.php?filename=validate_otp.php">
            <div class="row">
                <input type="text" name="otp[]" maxlength="1" readonly />
                <input type="text" name="otp[]" maxlength="1" disabled readonly />
                <input type="text" name="otp[]" maxlength="1" disabled readonly />
                <input type="text" name="otp[]" maxlength="1" disabled readonly />
            </div>
            <div class="virtual-keyboard" id="virtualKeyboard"></div>
            <button type="submit" id="verifyButton" disabled>Verify OTP</button>
        </form>
        <p>Didn't receive the code?
            <a href="javascript:void(0)" id="request" onclick="generateOTP(true)">Request Again!</a>
        </p>
    </div>

    <script>
    const inputs = document.querySelectorAll("input"),
        verifyButton = document.getElementById("verifyButton"),
        expire = document.getElementById("expire"),
        virtualKeyboard = document.getElementById("virtualKeyboard");

    let OTP = "<?php echo $_SESSION['otp']; ?>",
        expireInterval = null,
        isExpired = false,
        currentInputIndex = 0;

    let numbers = [...Array(10).keys()]; 
    alert("Your OTP is: " + OTP);

    function generateOTP(isResend = false) {
        if (isResend) {
            // Send a POST request to regenerate the OTP
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'request_otp_again=true'
            }).then(() => {
                window.location.reload(); // Reload the page to get the new OTP
            });
            return;
        }

        expire.innerText = 30;
        isExpired = false;
        verifyButton.disabled = true;

        clearInterval(expireInterval);
        expireInterval = setInterval(() => {
            let timeLeft = parseInt(expire.innerText);
            if (timeLeft > 0) {
                expire.innerText = timeLeft - 1;
            } else {
                clearInterval(expireInterval);
                expire.innerText = "Timeout!";
                isExpired = true;
                verifyButton.disabled = true;
                alert("OTP expired! Please request again.");
                clearOTP();
                setTimeout(() => generateOTP(true), 1000);
            }
        }, 1000);

        if (isResend || !virtualKeyboard.hasChildNodes()) {
            shuffleNumbers();
            createVirtualKeyboard();
        }
    }

    function clearOTP() {
        inputs.forEach((input) => {
            input.value = "";
            input.setAttribute("disabled", true);
        });
        inputs[0].removeAttribute("disabled");
        inputs[0].focus();

        clearInterval(expireInterval);
        expire.innerText = "0";
        verifyButton.disabled = true;
    }

    function shuffleNumbers() {
        numbers.sort(() => Math.random() - 0.5);
    }

    function createVirtualKeyboard() {
        virtualKeyboard.innerHTML = "";
        numbers.forEach(num => {
            const key = document.createElement("div");
            key.classList.add("virtual-key");
            key.textContent = num;
            key.addEventListener("click", () => handleKeyClick(num));
            virtualKeyboard.appendChild(key);
        });

        const backspaceKey = document.createElement("div");
        backspaceKey.classList.add("virtual-key");
        backspaceKey.textContent = "⌫"; 
        backspaceKey.addEventListener("click", handleBackspace);
        virtualKeyboard.appendChild(backspaceKey);
    }

    function handleKeyClick(num) {
        if (currentInputIndex < inputs.length) {
            const currentInput = inputs[currentInputIndex];
            currentInput.value = num;
            currentInputIndex++;

            if (currentInputIndex < inputs.length) {
                inputs[currentInputIndex].removeAttribute("disabled");
                inputs[currentInputIndex].focus();
            }
            checkOTPComplete();
        }
    }

    function handleBackspace() {
        if (currentInputIndex > 0) {
            currentInputIndex--;
            const currentInput = inputs[currentInputIndex];
            currentInput.value = "";
            currentInput.focus();

            if (currentInputIndex < inputs.length - 1) {
                inputs[currentInputIndex + 1].setAttribute("disabled", true);
            }
            checkOTPComplete();
        }
    }

    inputs.forEach((input, index) => {
        input.addEventListener("focus", () => {
            currentInputIndex = index;
        });
        input.addEventListener("keydown", (e) => {
            e.preventDefault();
            if (e.key === "Backspace" && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = "";
                currentInputIndex = index - 1;
                checkOTPComplete();
            }
        });
    });

    function checkOTPComplete() {
        const isComplete = [...inputs].every(input => input.value !== "");
        verifyButton.disabled = !isComplete || isExpired;
    }

    window.addEventListener("load", () => {
        generateOTP();
    });
</script>
</body>

</html>