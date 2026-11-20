        // Script to toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    if (type === 'text') {
                        this.classList.remove('bx-hide');
                        this.classList.add('bx-show');
                        this.style.color = '#4f46e5';
                    } else {
                        this.classList.remove('bx-show');
                        this.classList.add('bx-hide');
                        this.style.color = '#94a3b8';
                    }
                });
            }
        });
