document.addEventListener('DOMContentLoaded', function() {
    initializeI18n();

    const modal = document.getElementById('authModal');
    const authButton = document.getElementById('authButton');
    const closeButton = document.getElementsByClassName('close')[0];
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const resetForm = document.getElementById('resetForm');
    const showRegisterLink = document.getElementById('showRegisterLink');
    const showLoginLink = document.getElementById('showLoginLink');
    const showResetLink = document.getElementById('showResetLink');
    const backToLoginLink = document.getElementById('backToLoginLink');
    
    // Funcție pentru a afișa mesaje de eroare sau succes
   function showMessage(messageKey, isError = false) {
    const message = i18next.t(messageKey);
    const messageElement = document.createElement('div');
    messageElement.textContent = message;
    messageElement.className = isError ? 'error-message' : 'success-message';

    // Utilizează un container dedicat pentru mesaje sau document.body dacă nu există
    const container = document.getElementById('messageContainer') || document.body;
    container.appendChild(messageElement);

    // Elimină mesajul după 5 secunde
    setTimeout(() => messageElement.remove(), 5000);
}


    // Language selector functionality
    $('#langSelect').on('change', function() {
        i18next.changeLanguage(this.value, (err, t) => {
            if (err) return console.log('something went wrong loading', err);
            $('body').localize();
        });
    });

    // Deschide modalul
    if (authButton) {
        authButton.onclick = function() {
            modal.style.display = "block";
        }
    }

    // Închide modalul
    if (closeButton) {
        closeButton.onclick = function() {
            modal.style.display = "none";
        }
    }

    // Închide modalul când se dă click în afara lui
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Schimbă între formulare
    if (showRegisterLink) {
        showRegisterLink.onclick = function(e) {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            resetForm.style.display = 'none';
        }
    }

    if (showLoginLink) {
        showLoginLink.onclick = function(e) {
            e.preventDefault();
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            resetForm.style.display = 'none';
        }
    }

    if (showResetLink) {
        showResetLink.onclick = function(e) {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'none';
            resetForm.style.display = 'block';
        }
    }

    if (backToLoginLink) {
        backToLoginLink.onclick = function(e) {
            e.preventDefault();
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            resetForm.style.display = 'none';
        }
    }

    // Gestionează trimiterea formularului de autentificare
document.getElementById('loginFormElement').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    console.log('Sending data:', data);
    fetch('login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        return JSON.parse(text);
    })
    .then(data => {
        console.log('Parsed data:', data);
        if (data.success) {
            showMessage(data.message || 'Autentificare reușită');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            showMessage(data.message || 'Autentificare eșuată', true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Eroare: ' + error.message, true);
    });
});

    // Gestionează trimiterea formularului de înregistrare
    document.getElementById('registerFormElement').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('regSuccess');
                showLoginLink.click();
            } else {
                showMessage('regError', true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('generalError', true);
        });
    });

    // Gestionează trimiterea formularului de resetare a parolei
    document.getElementById('resetFormElement').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('reset_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('resetSuccess');
                showLoginLink.click();
            } else {
                showMessage('resetError', true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('generalError', true);
        });
    });
});