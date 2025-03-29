import { checkExists } from "./request-utils.js";

document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("#register-form");
  const validation = new window.JustValidate("#register-form", {
    validateBeforeSubmitting: true,
    errorFieldCssClass: 'is-invalid', // Add this class to invalid fields
    errorLabelStyle: {
      color: '#dc3545', // Red color for errors
    },
  });

  validation
    .addField("#email", [
      {
        rule: "required",
        errorMessage: "Email is required",
      },
      {
        rule: "email",
        errorMessage: "Please enter a valid email",
      },
      {
        validator: (value) => () =>
          checkExists("email", value).then((exists) => !exists),
        errorMessage: "Email address unavailable",
      },
    ], {
      errorsContainer: "#email-error" // Display errors in this container
    })
    .addField("#phone-number", [
      {
        rule: "required",
        errorMessage: "Phone number is required",
      },
      {
        rule: "minLength",
        value: 10,
        errorMessage: "Minimum 10 digits required",
      },
      {
        rule: "maxLength",
        value: 15,
        errorMessage: "Maximum 15 digits allowed",
      },
      {
        rule: "number",
        errorMessage: "Only numbers allowed",
      },
      {
        validator: (value) => () =>
          checkExists("phone_number", value).then((exists) => !exists),
        errorMessage: "Phone number unavailable",
      },
    ], {
      errorsContainer: "#phone-number-error"
    })
    .addField("#full-name", [
      {
        rule: "required",
        errorMessage: "Full name is required",
      },
      {
        rule: "minLength",
        value: 3,
        errorMessage: "Minimum 3 characters required",
      },
      {
        rule: "maxLength",
        value: 15,
        errorMessage: "Maximum 15 characters allowed",
      },
    ], {
      errorsContainer: "#full-name-error"
    })
    .addField("#username", [
      {
        rule: "required",
        errorMessage: "Username is required",
      },
      {
        rule: "customRegexp",
        value: /^[a-zA-Z0-9._]+$/,
        errorMessage: "Only letters, numbers, dots and underscores allowed",
      },
      {
        rule: "minLength",
        value: 1,
        errorMessage: "Minimum 1 character required",
      },
      {
        rule: "maxLength",
        value: 15,
        errorMessage: "Maximum 15 characters allowed",
      },
      {
        validator: (value) => () =>
          checkExists("username", value).then((exists) => !exists),
        errorMessage: "Username unavailable. Try another.",
      },
    ], {
      errorsContainer: "#username-error"
    })
.addField("#password", [
    {
        rule: "required",
        errorMessage: "Password is required",
    },
    {
        rule: "minLength",
        value: 8,
        errorMessage: "Password must be at least 8 characters",
    },
    {
        rule: "maxLength",
        value: 30,
        errorMessage: "Password cannot exceed 30 characters",
    },
    {
        rule: "customRegexp",
        value: /[A-Z]/,
        errorMessage: "Must contain at least 1 uppercase letter",
    },
    {
        rule: "customRegexp",
        value: /[a-z]/,
        errorMessage: "Must contain at least 1 lowercase letter",
    },
    {
        rule: "customRegexp",
        value: /[0-9]/,
        errorMessage: "Must contain at least 1 number",
    },
], {
    errorsContainer: "#password-error",
    successMessage: "Password meets all requirements",
})
.onSuccess((event) => {
    event.preventDefault();
    HTMLFormElement.prototype.submit.call(form);
});

// Add real-time password validation feedback
const passwordInput = document.getElementById('password');
const requirements = {
    length: document.getElementById('req-length'),
    uppercase: document.getElementById('req-uppercase'),
    lowercase: document.getElementById('req-lowercase'),
    number: document.getElementById('req-number')
};

passwordInput.addEventListener('input', function() {
    const value = this.value;
    
    // Check each requirement and update UI
    requirements.length.classList.toggle('text-success', value.length >= 8);
    requirements.length.classList.toggle('text-muted', value.length < 8);
    
    requirements.uppercase.classList.toggle('text-success', /[A-Z]/.test(value));
    requirements.uppercase.classList.toggle('text-muted', !/[A-Z]/.test(value));
    
    requirements.lowercase.classList.toggle('text-success', /[a-z]/.test(value));
    requirements.lowercase.classList.toggle('text-muted', !/[a-z]/.test(value));
    
    requirements.number.classList.toggle('text-success', /[0-9]/.test(value));
    requirements.number.classList.toggle('text-muted', !/[0-9]/.test(value));
    
    // Revalidate the field as user types
    validation.revalidateField('#password');
});

  // Add real-time validation as user types
  document.querySelectorAll('#register-form input').forEach(input => {
    input.addEventListener('input', () => {
      validation.revalidateField(input.id);
    });
  });
});
