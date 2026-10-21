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

  // Ensure appointment date picker starts from today
  const apptDateInput = document.getElementById("appointment_date");
  if (apptDateInput) {
    const today = new Date().toISOString().split("T")[0];
    apptDateInput.min = today;
  }

  // Payment proof image instant preview
  const proofInput = document.getElementById("proof_image");
  if (proofInput) {
    proofInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file && (file.type === "image/jpeg" || file.type === "image/png")) {
        let preview = document.getElementById("proof-preview-img");
        if (!preview) {
          preview = document.createElement("img");
          preview.id = "proof-preview-img";
          preview.style.display = "block";
          preview.style.marginTop = "10px";
          preview.style.maxHeight = "120px";
          preview.style.borderRadius = "6px";
          preview.style.border = "1px solid #cbd5e1";
          proofInput.parentNode.appendChild(preview);
        }
        const reader = new FileReader();
        reader.onload = function (evt) {
          preview.src = evt.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }
});
