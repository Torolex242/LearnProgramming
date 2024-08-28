document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('/custom_js/contact_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Mesajul a fost trimis cu succes!');
                    this.reset();
                } else {
                    alert('A apărut o eroare la trimiterea mesajului: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A apărut o eroare la trimiterea mesajului: ' + error.message);
            });
        });
    }
});