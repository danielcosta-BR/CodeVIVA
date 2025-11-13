document.addEventListener('DOMContentLoaded', function() {
    const profileBtn = document.getElementById('profile-btn');
    const dropdown = document.getElementById('profile-dropdown');
    const logoutTrigger = document.getElementById('logout-trigger');
    
    // Elementos do Modal
    const modal = document.getElementById('logout-modal');
    const confirmLogout = document.getElementById('confirm-logout');
    const cancelLogout = document.getElementById('cancel-logout');

    // 1. Alterna o Dropdown
    if (profileBtn) {
        profileBtn.addEventListener('click', function() {
            dropdown.classList.toggle('show-dropdown');
        });
    }

    // 2. Fecha o dropdown se o usuário clicar fora
    window.addEventListener('click', function(event) {
        if (profileBtn && !profileBtn.contains(event.target) && !dropdown.contains(event.target)) {
            if (dropdown.classList.contains('show-dropdown')) {
                dropdown.classList.remove('show-dropdown');
            }
        }
    });

    // 3. Abre o Modal de Confirmação
    if (logoutTrigger) {
        logoutTrigger.addEventListener('click', function(e) {
            e.preventDefault(); // Impede o link de navegar imediatamente
            dropdown.classList.remove('show-dropdown'); // Fecha o dropdown
            if (modal) {
                modal.style.display = 'block';
            }
        });
    }
    
    // 4. Botão 'Cancelar' no Modal
    if (cancelLogout) {
        cancelLogout.addEventListener('click', function() {
            if (modal) {
                modal.style.display = 'none';
            }
        });
    }

    // 5. Botão 'Confirmar' no Modal (Redireciona)
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function() {
            // Redireciona para o logout.php
            window.location.href = '../logout.php'; 
        });
    }

    // 6. Fecha o Modal clicando fora
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
});