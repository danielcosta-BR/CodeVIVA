function togglePasswordVisibility(inputId, iconElement) {
    // Encontra o input específico pelo ID passado na função HTML
    const input = document.getElementById(inputId);
    
    // Verifica o tipo atual do input
    if (input.type === 'password') {
        // Se for 'password', muda para 'text' (visível)
        input.type = 'text';
        // Muda o ícone de olho aberto para olho fechado
        iconElement.textContent = '🚫'; 
    } else {
        // Se for 'text', muda para 'password' (oculto)
        input.type = 'password';
        // Muda o ícone de volta para olho aberto
        iconElement.textContent = '👁️';
    }
}