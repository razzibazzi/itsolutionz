// Login functionality
document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();
    
    // Auto-focus on first input
    document.getElementById('schoolId').focus();
    
    // Form submission
    document.getElementById('loginForm').addEventListener('submit', handleLogin);

    // Toggle password visibility
    const togglePasswordButton = document.getElementById('togglePassword');
    if (togglePasswordButton) {
        togglePasswordButton.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            if (icon) {
                // swap icon
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
            // update accessible label/title
            togglePasswordButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            togglePasswordButton.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
        });
    }
});

function checkLoginStatus() {
    fetch('check_login.php')
        .then(response => response.json())
        .then(data => {
            if (data.logged_in) {
                // User is already logged in, redirect to dashboard.html
                window.location.href = 'dashboard.html';
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
        });
}

function handleLogin(e) {
    e.preventDefault();
    
    const schoolId = document.getElementById('schoolId').value.trim();
    const password = document.getElementById('password').value.trim();
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    const loginBtn = document.getElementById('loginBtn');
    const loading = document.querySelector('.loading');
    
    // Hide previous errors
    errorAlert.style.display = 'none';
    
    if (!schoolId || !password) {
        showError('Please fill in all fields');
        return;
    }
    
    // Show loading state
    loginBtn.style.display = 'none';
    loading.style.display = 'block';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('schoolId', schoolId);
    formData.append('password', password);
    
    // Submit login request
    fetch('operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Login successful, redirect to dashboard.html
            window.location.href = 'dashboard.html';
        } else {
            // Login failed, show error
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred. Please try again.');
    })
    .finally(() => {
        // Hide loading state
        loginBtn.style.display = 'block';
        loading.style.display = 'none';
    });
}

function showError(message) {
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    
    errorMessage.textContent = message;
    errorAlert.style.display = 'block';
    
    // Scroll to error message
    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}