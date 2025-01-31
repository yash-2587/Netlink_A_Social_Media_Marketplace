const inputs = document.querySelectorAll("input"),
    button = document.querySelector("button"),
    mobile = document.getElementById("mobile"),
    expire = document.getElementById("expire");

// Get URL parameters
const urlParams = new URLSearchParams(window.location.search);
const email = urlParams.get("email");
const contact = urlParams.get("contact");

// Get the previous page URL (referrer)
const previousURL = document.referrer;
const isFromForgot = previousURL.includes("forgot.html");
const isFromLogin = previousURL.includes("func3.php");

console.log(previousURL); // Log the referrer URL for debugging

let OTP = "", expireInterval = null, isExpired = false;

function generateOTP() {
    OTP = Math.floor(1000 + Math.random() * 9000).toString(); 
    alert("Your OTP is: " + OTP);

    clearOTP(); 
    inputs[0].focus();
    
    expire.innerText = 30;
    isExpired = false; // Reset expiration flag
    button.disabled = true; // Disable Verify button initially

    clearInterval(expireInterval);
    expireInterval = setInterval(() => {
        let timeLeft = parseInt(expire.innerText);
        if (timeLeft > 0) {
            expire.innerText = timeLeft - 1;
        } else {
            clearInterval(expireInterval);
            expire.innerText = "Timeout!";
            isExpired = true; // Mark OTP as expired
            button.disabled = true; // Disable Verify button permanently
            alert("OTP expired! Please request again.");
            clearOTP();
            setTimeout(() => {
                generateOTP(); // Auto-regenerate OTP after expiry
            }, 1000);
        }
    }, 1000);
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
    button.disabled = true;
}

// Handle OTP input fields
inputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
        if (!/^\d$/.test(e.target.value)) {
            e.target.value = ""; // Prevent non-numeric input
            return;
        }
        if (index < inputs.length - 1) {
            inputs[index + 1].removeAttribute("disabled");
            inputs[index + 1].focus();
        }
        checkOTPComplete();
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace") {
            input.value = "";
            if (index > 0) {
                inputs[index - 1].focus();
            }
            checkOTPComplete();
        }
    });
});

// Enable Verify button only when all inputs are filled and OTP is not expired
function checkOTPComplete() {
    const isComplete = [...inputs].every(input => input.value !== "");
    button.disabled = !isComplete || isExpired;
}

window.addEventListener("load", () => {
    if (contact) {
        mobile.innerText = contact; // Display the contact number
    }
    generateOTP();
});

button.addEventListener("click", () => {
    if (isExpired) {
        alert("OTP has expired! Please request a new one.");
        return;
    }

    let enteredOTP = [...inputs].map(input => input.value).join("");
    if (enteredOTP === OTP) {
        alert("Your account has been verified!");

        // Redirect based on the referrer page (previous URL)
        if (isFromLogin) {
            window.location.href = "http://localhost/fcs/dashboard.html";
        } else if (isFromForgot) {
            window.location.href = "reset.html?source=forgot&contact=" + encodeURIComponent(contact);
        }
    } else {
        alert("Incorrect OTP. Try again.");
        clearOTP();
    }
});
