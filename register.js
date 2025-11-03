document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const senhaInput = document.getElementById('senha');
        const confirmaSenhaInput = document.getElementById('confirma_senha');
        
        // Função que verifica a igualdade das senhas
        function validarSenhas(event) {
            if (senhaInput.value !== confirmaSenhaInput.value) {
                // Impede o envio do formulário
                event.preventDefault(); 
                
                alert('Erro: As senhas digitadas não coincidem. Por favor, verifique.');
                
                // Opcional: Adicionar feedback visual (ex: borda vermelha)
                senhaInput.style.border = '2px solid red';
                confirmaSenhaInput.style.border = '2px solid red';
                
                return false;
            } else {
                // Limpa o feedback visual, caso a validação anterior tenha falhado
                senhaInput.style.border = '';
                confirmaSenhaInput.style.border = '';
                return true;
            }
        }
        
        // Adiciona o validador ao evento de submissão do formulário
        form.addEventListener('submit', validarSenhas);
        
        // (BÔNUS) Adiciona verificação enquanto o usuário digita
        confirmaSenhaInput.addEventListener('input', validarEmTempoReal);
        
        function validarEmTempoReal() {
            if (senhaInput.value !== confirmaSenhaInput.value && confirmaSenhaInput.value.length > 0) {
                confirmaSenhaInput.setCustomValidity('As senhas não coincidem.');
                confirmaSenhaInput.reportValidity();
            } else {
                confirmaSenhaInput.setCustomValidity(''); // Senhas OK ou campo vazio
            }
        }
    });