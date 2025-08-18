# Exemplos de Uso - Script Atualizar Imagem de Capa

## 🎯 **Script Atualizado**: `atualizar_imagem_capa.py`

## ⚠️ **ORIENTAÇÃO IMPORTANTE PARA ASSISTENTES**

**Se o usuário pedir para atualizar imagem de capa, SEMPRE perguntar:**
1. **ID do post** (número específico do WordPress)
2. **URL da imagem** ou caminho do arquivo local

**❌ Não assumir valores padrão** - sempre confirmar ambos os parâmetros!

**✅ Exemplos de perguntas necessárias:**
- "Qual o ID do post que você quer atualizar a capa?"
- "Qual a URL da imagem que você quer usar como capa?"
- "Você tem o caminho/nome do arquivo da imagem?"

### 📖 **Como Usar**

#### 1️⃣ **Verificar capa atual de um post**
```bash
python scripts/atualizar_imagem_capa.py --verificar 2988
```

#### 2️⃣ **Atualizar capa com URL externa**
```bash
python scripts/atualizar_imagem_capa.py 2988 "https://exemplo.com/nova-capa.jpg"
```

#### 3️⃣ **Atualizar capa com arquivo local (nome apenas)**
```bash
python scripts/atualizar_imagem_capa.py 3007 "capa-nova.png"
# Busca automaticamente em: imagens/capa-nova.png
```

#### 4️⃣ **Atualizar capa com caminho completo**
```bash
python scripts/atualizar_imagem_capa.py 3100 "c:/path/completo/imagem.webp"
```

### 🔧 **Funcionalidades Principais**

- ✅ **Flexibilidade**: URLs externas ou arquivos locais
- ✅ **Validação**: Verifica se post existe antes de processar  
- ✅ **Formatos**: PNG, JPG, JPEG, WebP, GIF
- ✅ **Logs detalhados**: Relatório completo do processo
- ✅ **Verificação**: Modo consulta para ver capa atual

### 🎯 **Exemplos Práticos**

```bash
# Exemplo real do projeto
python scripts/atualizar_imagem_capa.py 3111 "capa-3111-porque-escolher-sufficit.png"

# Com URL da imagem gerada por IA
python scripts/atualizar_imagem_capa.py 2988 "https://oaidalleapiprodscus.blob.core.windows.net/private/exemplo.png"

# Verificando antes de atualizar
python scripts/atualizar_imagem_capa.py --verificar 3007
```

### ⚠️ **Requisitos**
- Python 3.7+
- Biblioteca `requests`
- Arquivo `config/config.json` configurado
- Permissões de upload no WordPress
