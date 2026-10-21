// javascript.js - DocHelp UI interactions & helpers

document.addEventListener("DOMContentLoaded", function () {
  console.log("DocHelp JS initialized.");

  // Password confirmation real-time check for Register form
  const registerForm = document.querySelector('form[action="register.php"]');
  if (registerForm) {
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm_password");

    function validatePasswordMatch() {
      if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
        confirmPasswordInput.setCustomValidity("Passwords do not match");
      } else {
        confirmPasswordInput.setCustomValidity("");
      }
    }

    if (passwordInput && confirmPasswordInput) {
      passwordInput.addEventListener("input", validatePasswordMatch);
      confirmPasswordInput.addEventListener("input", validatePasswordMatch);
    }
  }

  // Smooth delete confirmation
  const deleteButtons = document.querySelectorAll(".btn-danger");
  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      if (!confirm("Are you sure you want to proceed with this deletion?")) {
        e.preventDefault();
      }
    });
  });
});
