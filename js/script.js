
document.getElementById('cep')?.addEventListener('blur', async function(){
    const cep = this.value.replace(/\D/g, '');
    if(cep.length === 8){
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json`);
        const data = await res.json();
        if(!data.erro){
            document.getElementById('endereco').value = data.logradouro;
            document.getElementById('cidade').value = data.localidade;
            document.getElementById('estado').value = data.uf;
        }
    }
})