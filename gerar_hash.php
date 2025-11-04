<?php
// gerar_hash.php (CRIAR E DEPOIS EXCLUIR)

$senha_pura = "@dM!n_C0mmaND"; // <<< SUBSTITUA POR SUA SENHA REAL AQUI!

$hash_seguro = password_hash($senha_pura, PASSWORD_DEFAULT);

echo "A senha pura que você digitou é: " . $senha_pura . "<br>";
echo "O HASH SEGURO é: <strong>" . $hash_seguro . "</strong><br><br>";
echo "Copie e cole este HASH na sua tabela 'Usuario' do phpMyAdmin.";

// Exemplo de Hash gerado: $2y$10$fV3b8dE9qR6wZ0x.N2s0s.hA5G.oA...
?>