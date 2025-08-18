# Orientações para Assistentes - Atualização de Imagem de Capa

## 🤖 **PROTOCOLO OBRIGATÓRIO**

### ⚠️ **SEMPRE PERGUNTAR ANTES DE EXECUTAR**

Quando o usuário solicitar atualização de imagem de capa, **SEMPRE** confirmar:

#### 1️⃣ **ID do Post**
- ❓ "Qual o ID do post que você quer atualizar a capa?"
- ❓ "Você sabe o número do post no WordPress?"
- 📝 Exemplo: Post ID 2988, 3007, 3111, etc.

#### 2️⃣ **Fonte da Imagem**
- ❓ "Qual a URL da imagem que você quer usar?"
- ❓ "Você tem o nome/caminho do arquivo da imagem?"
- 📝 Exemplos:
  - URL: `https://exemplo.com/imagem.jpg`
  - Arquivo local: `capa-nova.png`
  - Caminho completo: `c:/pasta/imagem.webp`

### ❌ **NÃO FAZER**
- ❌ **Não assumir** ID do post baseado no contexto
- ❌ **Não usar** "primeira imagem do post" automaticamente
- ❌ **Não executar** sem confirmação dos parâmetros
- ❌ **Não inventar** URLs ou caminhos de arquivo

### ✅ **FAZER**
- ✅ **Sempre confirmar** ambos os parâmetros
- ✅ **Perguntar especificamente** ID e URL/caminho
- ✅ **Aguardar resposta** antes de executar
- ✅ **Validar** se as informações estão completas

## 📋 **Fluxo Recomendado**

1. **Usuário solicita**: "Atualize a capa do post X"
2. **Assistente pergunta**: 
   - "Qual o ID do post?"
   - "Qual a URL/caminho da imagem?"
3. **Usuário responde**: ID e URL/caminho
4. **Assistente executa**: `python scripts/atualizar_imagem_capa.py ID URL`

## 💬 **Exemplos de Diálogo**

### ✅ **Correto**
```
Usuário: "Atualize a capa do post sobre telefonia"
Assistente: "Para atualizar a capa, preciso de duas informações:
1. Qual o ID do post sobre telefonia? 
2. Qual a URL da imagem que você quer usar como capa?"
```

### ❌ **Incorreto**
```
Usuário: "Atualize a capa do post sobre telefonia"
Assistente: "Vou usar a primeira imagem do post 2988..."
```

## 🔧 **Comandos de Verificação**

Antes de atualizar, pode usar:
```bash
# Verificar capa atual
python scripts/atualizar_imagem_capa.py --verificar POST_ID
```

## 📚 **Documentação Relacionada**
- `scripts/README.md` - Documentação técnica
- `scripts/atualizar_imagem_capa.py` - Script principal
- Instruções detalhadas no cabeçalho do script
