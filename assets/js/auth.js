/**
 * HabeshaEqub - Admin Authentication JavaScript
 * Handles login, registration, and form validation with AJAX
 * No page reloads - app-like experience
 */

class AuthManager {
    constructor() {
        this.initializeEventListeners();
        this.setupFormValidation();
    }

    /**
     * Initialize all event listeners for forms and buttons
     */
    initializeEventListeners() {
        // Login form submission
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Registration form submission
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Real-time form validation
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', (e) => this.validateField(e.target));
            input.addEventListener('input', (e) => this.clearFieldError(e.target));
        });
    }

    /**
     * Handle admin login via AJAX
     */
    async handleLogin(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('.btn-primary');
        const username = form.querySelector('#username').value.trim();
        const password = form.querySelector('#password').value;

        // Client-side validation
        if (!this.validateLoginForm(username, password)) {
            return;
        }

        try {
            // Show loading state
            this.setLoadingState(submitBtn, true);
            this.hideAllAlerts();

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);

            // Send AJAX request
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', result.message || 'Login successful! Redirecting...');
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = result.redirect || 'dashboard.php';
                }, 1500);
            } else {
                this.showAlert('error', result.message || 'Login failed. Please try again.');
                this.setLoadingState(submitBtn, false);
            }

        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('error', 'Network error. Please check your connection and try again.');
            this.setLoadingState(submitBtn, false);
        }
    }

    /**
     * Handle admin registration via AJAX
     */
    async handleRegister(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('.btn-primary');
        const username = form.querySelector('#username').value.trim();
        const password = form.querySelector('#password').value;
        const confirmPassword = form.querySelector('#confirm_password').value;

        // Client-side validation
        if (!this.validateRegisterForm(username, password, confirmPassword)) {
            return;
        }

        try {
            // Show loading state
            this.setLoadingState(submitBtn, true);
            this.hideAllAlerts();

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);
            formData.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);

            // Send AJAX request
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', result.message || 'Registration successful! You can now login.');
                form.reset();
                
                // Optional: Auto-redirect to login page
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                this.showAlert('error', result.message || 'Registration failed. Please try again.');
            }

        } catch (error) {
            console.error('Registration error:', error);
            this.showAlert('error', 'Network error. Please check your connection and try again.');
        } finally {
            this.setLoadingState(submitBtn, false);
        }
    }

    /**
     * Validate login form fields
     */
    validateLoginForm(username, password) {
        let isValid = true;

        // Username validation
        if (!username) {
            this.showFieldError('username', 'Username is required');
            isValid = false;
        } else if (username.length < 3) {
            this.showFieldError('username', 'Username must be at least 3 characters');
            isValid = false;
        }

        // Password validation
        if (!password) {
            this.showFieldError('password', 'Password is required');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validate registration form fields
     */
    validateRegisterForm(username, password, confirmPassword) {
        let isValid = true;

        // Username validation
        if (!username) {
            this.showFieldError('username', 'Username is required');
            isValid = false;
        } else if (username.length < 3) {
            this.showFieldError('username', 'Username must be at least 3 characters');
            isValid = false;
        } else if (username.length > 20) {
            this.showFieldError('username', 'Username must be less than 20 characters');
            isValid = false;
        } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            this.showFieldError('username', 'Username can only contain letters, numbers, and underscores');
            isValid = false;
        }

        // Password validation
        if (!password) {
            this.showFieldError('password', 'Password is required');
            isValid = false;
        } else if (password.length < 6) {
            this.showFieldError('password', 'Password must be at least 6 characters');
            isValid = false;
        } else if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password)) {
            this.showFieldError('password', 'Password must contain both letters and numbers');
            isValid = false;
        }

        // Confirm password validation
        if (!confirmPassword) {
            this.showFieldError('confirm_password', 'Please confirm your password');
            isValid = false;
        } else if (password !== confirmPassword) {
            this.showFieldError('confirm_password', 'Passwords do not match');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validate individual form field on blur
     */
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name || field.id;

        // Clear any existing errors
        this.clearFieldError(field);

        switch (fieldName) {
            case 'username':
                if (value && value.length < 3) {
                    this.showFieldError(fieldName, 'Username must be at least 3 characters');
                } else if (value && !/^[a-zA-Z0-9_]+$/.test(value)) {
                    this.showFieldError(fieldName, 'Username can only contain letters, numbers, and underscores');
                } else if (value) {
                    this.showFieldSuccess(fieldName);
                }
                break;

            case 'password':
                if (value && value.length < 6) {
                    this.showFieldError(fieldName, 'Password must be at least 6 characters');
                } else if (value && !/(?=.*[a-zA-Z])(?=.*\d)/.test(value)) {
                    this.showFieldError(fieldName, 'Password must contain both letters and numbers');
                } else if (value) {
                    this.showFieldSuccess(fieldName);
                }
                break;

            case 'confirm_password':
                const passwordField = document.getElementById('password');
                if (value && passwordField && value !== passwordField.value) {
                    this.showFieldError(fieldName, 'Passwords do not match');
                } else if (value && passwordField && value === passwordField.value) {
                    this.showFieldSuccess(fieldName);
                }
                break;
        }
    }

    /**
     * Show field-specific error message
     */
    showFieldError(fieldName, message) {
        const field = document.getElementById(fieldName);
        const errorElement = field.parentElement.querySelector('.error-message');
        
        field.classList.add('error');
        field.classList.remove('success');
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    /**
     * Show field success state
     */
    showFieldSuccess(fieldName) {
        const field = document.getElementById(fieldName);
        field.classList.add('success');
        field.classList.remove('error');
        
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    /**
     * Clear field error state
     */
    clearFieldError(field) {
        field.classList.remove('error', 'success');
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    /**
     * Show alert message
     */
    showAlert(type, message) {
        this.hideAllAlerts();
        
        const alertElement = document.querySelector(`.alert-${type}`);
        if (alertElement) {
            alertElement.textContent = message;
            alertElement.style.display = 'block';
            alertElement.classList.add('fade-in');
            
            // Auto-hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    alertElement.style.display = 'none';
                }, 5000);
            }
        }
    }

    /**
     * Hide all alert messages
     */
    hideAllAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.display = 'none';
            alert.classList.remove('fade-in');
        });
    }

    /**
     * Set button loading state
     */
    setLoadingState(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('btn-loading');
            button.setAttribute('data-original-text', button.textContent);
        } else {
            button.disabled = false;
            button.classList.remove('btn-loading');
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.textContent = originalText;
            }
        }
    }

    /**
     * Setup form validation event listeners
     */
    setupFormValidation() {
        // Prevent form submission if there are validation errors
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const errorFields = form.querySelectorAll('.form-control.error');
                if (errorFields.length > 0) {
                    e.preventDefault();
                    this.showAlert('error', 'Please fix the errors below before submitting.');
                }
            });
        });
    }
}

// Initialize authentication manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
});

// Password toggle functionality (global)
window.togglePassword = function(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const passwordIcon = document.getElementById(fieldId + 'Icon');
    
    if (!passwordField || !passwordIcon) {
        console.error('Password field or icon not found:', fieldId);
        return;
    }
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        passwordIcon.innerHTML = '🙈';
        passwordIcon.setAttribute('title', 'Hide password');
    } else {
        passwordField.type = 'password';
        passwordIcon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>`;
        passwordIcon.setAttribute('title', 'Show password');
    }
};

// Utility function for checking if user is logged in (for other pages)
window.AuthUtils = {
    /**
     * Check if admin is logged in via AJAX
     */
    async checkAuthStatus() {
        try {
            const response = await fetch('api/auth.php?action=check_auth');
            const result = await response.json();
            return result.authenticated || false;
        } catch (error) {
            console.error('Auth check error:', error);
            return false;
        }
    },

    /**
     * Logout admin via AJAX
     */
    async logout() {
        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=logout'
            });
            
            const result = await response.json();
            if (result.success) {
                window.location.href = 'login.php';
            }
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = 'login.php';
        }
    }
}; 