document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();

    // Auto-focus on first input
    document.getElementById('schoolId').focus();

    // Form submission
    document.getElementById('loginForm').addEventListener('submit', handleLogin);

    // PASSWORD SHOW/HIDE
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (passwordInput && togglePassword && togglePasswordIcon) {

        // Default state = hidden password
        passwordInput.type = 'password';
        togglePasswordIcon.className = 'fas fa-eye-slash';

        togglePassword.addEventListener('click', function () {

            if (passwordInput.type === 'password') {

                // Show password
                passwordInput.type = 'text';

                // Open eye icon
                togglePasswordIcon.className = 'fas fa-eye';

                togglePassword.setAttribute('aria-label', 'Hide password');
                togglePassword.setAttribute('title', 'Hide password');

            } else {

                // Hide password
                passwordInput.type = 'password';

                // Closed eye icon
                togglePasswordIcon.className = 'fas fa-eye-slash';

                togglePassword.setAttribute('aria-label', 'Show password');
                togglePassword.setAttribute('title', 'Show password');
            }
        });
    }
});



// // Login functionality
// document.addEventListener('DOMContentLoaded', function() {
//     checkLoginStatus();
    
//     // Auto-focus on first input
//     document.getElementById('schoolId').focus();
    
//     // Form submission
//     document.getElementById('loginForm').addEventListener('submit', handleLogin);

//     // Toggle password visibility
//     const togglePasswordButton = document.getElementById('togglePassword');
//     if (togglePasswordButton) {
//         togglePasswordButton.addEventListener('click', function() {
//             const passwordInput = document.getElementById('password');
//             const icon = document.getElementById('togglePasswordIcon');
//             const isPassword = passwordInput.getAttribute('type') === 'password';
//             passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
//             if (icon) {
//                 // swap icon
//                 icon.classList.toggle('fa-eye');
//                 icon.classList.toggle('fa-eye-slash');
//             }
//             // update accessible label/title
//             togglePasswordButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
//             togglePasswordButton.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
//         });
//     }
// });

// function checkLoginStatus() {
//     const formData = new FormData();
//     formData.append('operation', 'check_login');
//     fetch('index.php', {
//         method: 'POST',
//         body: formData
//     })
//         .then(response => response.json())
//         .then(data => {
//             if (data.logged_in) {
//                 // User is already logged in, redirect to dashboard.html
//                 window.location.href = 'dashboard.html';
//             }
//         })
//         .catch(error => {
//             console.error('Error checking login status:', error);
//         });
// }

// function handleLogin(e) {
//     e.preventDefault();
    
//     const schoolId = document.getElementById('schoolId').value.trim();
//     const password = document.getElementById('password').value.trim();
//     const errorAlert = document.getElementById('errorAlert');
//     const errorMessage = document.getElementById('errorMessage');
//     const loginBtn = document.getElementById('loginBtn');
//     const loading = document.querySelector('.loading');
    
//     // Hide previous errors
//     errorAlert.style.display = 'none';
    
//     if (!schoolId || !password) {
//         showError('Please fill in all fields');
//         return;
//     }
    
//     // Show loading state
//     loginBtn.style.display = 'none';
//     loading.style.display = 'block';
    
//     const formData = new URLSearchParams();
//     formData.append('operation', 'login');
//     formData.append('schoolId', schoolId);
//     formData.append('password', password);
//     formData.append('ajax', '1');
//     fetch('index.php', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//         body: formData.toString()
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             window.location.href = 'dashboard.html';
//         } else {
//             showError(data.message);
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         showError('An error occurred. Please try again.');
//     })
//     .finally(() => {
//         loginBtn.style.display = 'block';
//         loading.style.display = 'none';
//     });
// }

// function showError(message) {
//     const errorAlert = document.getElementById('errorAlert');
//     const errorMessage = document.getElementById('errorMessage');
    
//     errorMessage.textContent = message;
//     errorAlert.style.display = 'block';
    
//     // Scroll to error message
//     errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
// }

// function logout() {
//     const formData = new FormData();
//     formData.append('operation', 'logout');

//     fetch('index.php', {
//         method: 'POST',
//         body: formData
//     })
//     .finally(() => {
//         window.location.href = 'login.html';
//     });
// }

