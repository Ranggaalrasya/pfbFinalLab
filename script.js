// script.js - ini kumpulan script javascript buat bikin tampilan makin interaktif & ngebantu validasi form di client-side

document.addEventListener("DOMContentLoaded", function () {
  console.log("Mantap! Script DocHelp udah jalan dengan lancar.");

  // validasi real-time konfirmasi password pas user lagi ngetik di form register
  const registerForm = document.querySelector('form[action="register.php"]');
  if (registerForm) {
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm_password");

    function validatePasswordMatch() {
      if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
        confirmPasswordInput.setCustomValidity("Waduh, passwordnya belum sama nih!");
      } else {
        confirmPasswordInput.setCustomValidity("");
      }
    }

    if (passwordInput && confirmPasswordInput) {
      passwordInput.addEventListener("input", validatePasswordMatch);
      confirmPasswordInput.addEventListener("input", validatePasswordMatch);
    }
  }

  // kasih pop-up konfirmasi pas tombol hapus yang merah diklik biar ga kepencet ga sengaja
  const deleteButtons = document.querySelectorAll(".btn-danger");
  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      if (!confirm("Beneran mau ngehapus data ini bro? Data yang dihapus ga bisa balik lagi lho!")) {
        e.preventDefault();
      }
    });
  });

  // batasin inputan tanggal booking biar minimal hari ini (ga boleh milih tanggal kemarin)
  const apptDateInput = document.getElementById("appointment_date");
  if (apptDateInput) {
    const today = new Date().toISOString().split("T")[0];
    apptDateInput.min = today;
  }

  // fitur preview gambar bukti transfer secara instan waktu user milih file
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
