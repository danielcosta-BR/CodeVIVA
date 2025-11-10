document.getElementById('cpf').addEventListener('input', function (e) {
    var value = e.target.value;
    // Remove tudo que não for dígito
    value = value.replace(/\D/g, "");
    
    // Adiciona a máscara
    if (value.length > 3) {
        value = value.replace(/(\d{3})(\d)/, "$1.$2");
    }
    if (value.length > 7) {
        value = value.replace(/(\d{3}\.\d{3})(\d)/, "$1.$2");
    }
    if (value.length > 11) {
        value = value.replace(/(\d{3}\.\d{3}\.\d{3})(\d)/, "$1-$2");
    }
    
    e.target.value = value;
});
