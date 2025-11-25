// register.js - Lógica do Wizard

let dadosCarregados = false;

// === MÁSCARAS E UTILIDADES ===
function maskCPF(el) {
    el.value = el.value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2').substring(0, 14);
}
function maskTelefone(el) {
    let v = el.value.replace(/\D/g, '');
    if (v.length > 10) el.value = v.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    else el.value = v.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
}
function togglePasswordVisibility(id, icon) {
    const input = document.getElementById(id);
    if(input && input.type === 'password') { input.type = 'text'; icon.innerText = '🙈'; }
    else if (input) { input.type = 'password'; icon.innerText = '👁️'; }
}

// === NAVEGAÇÃO ===
function goToStep2() {
    const nome = document.getElementById('nome').value.trim();
    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value;
    const conf = document.getElementById('confirma_senha').value;
    const funcao = document.getElementById('funcao_usuario').value; 

    if (!nome || !email || !senha || !conf || !funcao || funcao === "") { 
        alert("Por favor, preencha todos os campos obrigatórios do Passo 1.");
        return;
    }
    if (senha.length < 8) {
        alert("A senha deve ter no mínimo 8 caracteres."); 
        return;
    }
    if (senha !== conf) {
        alert("As senhas não conferem.");
        return;
    }

    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';

    if (!dadosCarregados) {
        carregarDadosExternos();
    }
    ajustarStep2();
}

function goToStep1() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
}

function ajustarStep2() {
    const funcao = document.getElementById('funcao_usuario').value;
    const divDoencas = document.getElementById('secao-doencas'); 
    
    if (divDoencas) {
        if(funcao === 'paciente') {
            divDoencas.style.display = 'block';
            const btnModal = document.getElementById('btn-modal-doencas');
            if(btnModal) btnModal.onclick = abrirModalDoencas;
        } else {
            divDoencas.style.display = 'none';
        }
    }
}

// === CARREGAMENTO DE DADOS ===
function carregarDadosExternos() {
    fetch('get_dados_cadastro.php')
        .then(response => {
            if (!response.ok) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            // 1. Preenche Select de Posto
            const selectPosto = document.getElementById('id_posto'); 
            if (selectPosto && selectPosto.options.length <= 1) {
                selectPosto.innerHTML = '<option value="" disabled selected>Selecione...</option>';
                if (data.postos) {
                    data.postos.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id_posto;
                        opt.innerText = p.nome_posto;
                        selectPosto.appendChild(opt);
                    });
                }
            }
            
            // 2. Preenche Doenças (Cria na Marra)
            const divList = document.getElementById('lista-doencas');
            if (divList) {
                divList.innerHTML = ''; 
                
                // A. Cria "Nenhuma" manualmente
                const itemNenhuma = document.createElement('div');
                itemNenhuma.className = 'checkbox-item';
                itemNenhuma.innerHTML = `
                    <input type="checkbox" id="chk_nenhuma_js" class="chk-doenca" value="0" data-nome="Nenhuma">
                    <label for="chk_nenhuma_js" style="font-weight: bold;">Nenhuma</label>
                `;
                const chkNenhuma = itemNenhuma.querySelector('input');
                chkNenhuma.addEventListener('change', function() {
                    gerenciarSelecaoDoencas(this, 'Nenhuma');
                });
                divList.appendChild(itemNenhuma);

                // B. Lista do Banco
                if (data.doencas) {
                    data.doencas.forEach(d => {
                        if (d.nome_doenca === 'Nenhuma') return;

                        const item = document.createElement('div');
                        item.className = 'checkbox-item';
                        
                        const chk = document.createElement('input');
                        chk.type = 'checkbox';
                        chk.name = 'doencas[]'; 
                        chk.value = d.id_doenca;
                        chk.id = 'doenca_' + d.id_doenca;
                        chk.className = 'chk-doenca'; 
                        chk.setAttribute('data-nome', d.nome_doenca);
                        
                        chk.addEventListener('change', function() {
                            gerenciarSelecaoDoencas(this, d.nome_doenca);
                        });

                        const lbl = document.createElement('label');
                        lbl.htmlFor = 'doenca_' + d.id_doenca;
                        lbl.innerText = d.nome_doenca; 

                        item.appendChild(chk);
                        item.appendChild(lbl);
                        divList.appendChild(item);
                    });
                }
                atualizarContagemDoencas();
            }
            dadosCarregados = true;
        })
        .catch(err => console.error("Erro JS:", err));
}

// === LÓGICA DE SELEÇÃO MÚTUA ===
function gerenciarSelecaoDoencas(checkbox, nome) {
    const todosChecks = document.querySelectorAll('.chk-doenca');
    
    if (nome === 'Nenhuma') {
        if (checkbox.checked) {
            todosChecks.forEach(c => {
                if (c !== checkbox) c.checked = false;
            });
        }
    } else {
        if (checkbox.checked) {
            const chkNenhuma = document.getElementById('chk_nenhuma_js');
            if (chkNenhuma) chkNenhuma.checked = false;
        }
    }
    atualizarContagemDoencas();
}

// === MODAL E CONTAGEM ===
const modalDoencas = document.getElementById('modal-doencas');
const btnModalDoencas = document.getElementById('btn-modal-doencas'); 

function abrirModalDoencas() {
    if (modalDoencas) modalDoencas.style.display = 'block';
}

function fecharModalDoencas() {
    if (modalDoencas) modalDoencas.style.display = 'none';
    atualizarContagemDoencas();
}

function atualizarContagemDoencas() {
    const marcados = document.querySelectorAll('.chk-doenca:checked').length;
    if (btnModalDoencas) {
        if (marcados === 0) {
            btnModalDoencas.innerText = "Selecionar Condições (Nenhuma selecionada)";
        } else {
            btnModalDoencas.innerText = `Selecionar Condições (${marcados} selecionada${marcados !== 1 ? 's' : ''})`;
        }
    }
}

// =========================================================================
// SUBMIT (CORRIGIDO - O PULO DO GATO ESTÁ AQUI)
// =========================================================================
document.getElementById('cadastroForm').addEventListener('submit', function(e) {
    const funcao = document.getElementById('funcao_usuario').value;
    const termos = document.getElementById('termos').checked;
    
    if (!termos) {
        e.preventDefault();
        alert("Você precisa concordar com os Termos de Uso.");
        return;
    }

    if (funcao === 'paciente') {
        const cpf = document.getElementById('cpf').value;
        const id_posto = document.getElementById('id_posto').value;
        
        if (!cpf || id_posto === "") {
            e.preventDefault();
            alert("Preencha CPF e Posto de Saúde.");
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            return;
        }

        // CORREÇÃO: Removemos APENAS os inputs hidden dentro do form, 
        // NÃO removemos os checkboxes do modal (que têm o mesmo name)
        this.querySelectorAll('input[type="hidden"][name="doencas[]"]').forEach(el => el.remove());
        
        // Agora podemos ler os checkboxes porque eles AINDA EXISTEM
        const checks = document.querySelectorAll('.chk-doenca:checked');
        
        checks.forEach(c => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'doencas[]';
            input.value = c.value; 
            this.appendChild(input);
        });
    } 
});

// === INICIALIZAÇÃO ===
document.addEventListener('DOMContentLoaded', () => {
    const btnConfirmar = document.getElementById('btn-confirmar-selecao');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', fecharModalDoencas);
    }
    if (btnModalDoencas) {
        btnModalDoencas.addEventListener('click', abrirModalDoencas);
    }
    ajustarStep2();
});