# Projeto WordPress - Gestão de Conteúdo

> **Worktrees (padrão Sufficit):** toda árvore de trabalho deste projeto (humanos ou agentes de IA) deve ser criada dentro da pasta do próprio projeto: `git worktree add .worktrees/<nome>`. A pasta `.worktrees/` é ignorada pelo git (`.gitignore` → `**/.worktrees/`) e nunca deve ser versionada ou criada fora da raiz do repositório.


## 📁 Estrutura do Projeto

```
c:\Desenvolvimento\wordpress/
├── � config/                  # 🔧 Configurações
│   ├── config.json             # WordPress API
│   └── config-openai.json      # OpenAI DALL-E
├── 📁 postagens/               # 📝 Arquivos HTML de posts
│   └── como-funciona-telefone-ip.html
├── 📁 imagens/                 # 🖼️ Todas as imagens geradas
│   ├── call-center-ambiente.png
│   ├── headset-voip.png
│   └── telefone-voip-2025.png
├── 📁 scripts/                 # 🚀 Scripts permanentes (Python)
│   ├── README.md               # Documentação dos scripts
│   ├── atualizar_post_python.py
│   ├── gerar_imagem.py
│   └── gerar-imagem-v5.ps1     # Legacy PowerShell
├── 📁 temp/                    # 🧪 Scripts temporários/experimentais
├── 📁 .github/
│   └── copilot-instructions.md # Instruções para o Copilot
└── 📄 organizar-projeto.ps1    # Script de organização
```

## 🎯 Regras de Organização

### Postagens HTML
- **Local**: `postagens/`
- **Formato**: Arquivos HTML prontos para WordPress
- **Nomenclatura**: `nome-do-post.html` (sem espaços, usar hífens)

### Imagens
- **Local**: `imagens/` (raiz do projeto)
- **Tipos**: PNG, JPG geradas via DALL-E
- **Nomenclatura**: Descritiva e sem espaços

### Scripts
- **Local**: `scripts/` para permanentes, `temp/` para experimentais
- **Tipos**: PowerShell (`.ps1`) e Python (`.py`)
- **Função**: Automação de tarefas WordPress e OpenAI
- **Documentação**: Detalhada em `scripts/README.md`

## 🔧 Scripts Principais

- `scripts/atualizar_post_python.py` - Atualiza posts via API WordPress (Python)
- `scripts/gerar_imagem.py` - Gera imagens via DALL-E (Python)
- `scripts/gerar-imagem-v5.ps1` - Gera imagens via DALL-E (PowerShell - Legacy)
- `organizar-projeto.ps1` - Organiza estrutura do projeto

📖 **Documentação completa**: Ver `scripts/README.md`

## � Python como Linguagem Padrão

Este projeto utiliza **Python 3.7+** como linguagem principal para:
- 🔄 **Automação**: Scripts de integração com APIs
- 🎨 **Geração**: Criação de imagens via DALL-E
- 📝 **Gestão**: Atualização de conteúdo WordPress
- 🛠️ **Portabilidade**: Compatibilidade multiplataforma

## �📋 Configurações

### config.json (WordPress)
```json
{
    "server": "https://wordpress.sufficit.com.br",
    "token": "seu_token_aqui"
}
```

### config-openai.json (OpenAI)
```json
{
    "organization": "org-xxxxxxxxxxxxxxxxxxxx",
    "token": "sk-xxxxxxxxxxxxxxxxxxxx"
}
```

## 🚀 Como Usar

### 1. Instalar Python e Dependências
```bash
# Verificar versão do Python
python --version

# Instalar bibliotecas necessárias
pip install requests
```

### 2. Configurar APIs
- Criar `config/config.json` com credenciais WordPress
- Criar `config/config-openai.json` com credenciais OpenAI

### 3. Workflow Típico
```bash
# Navegar para o diretório do projeto
cd c:\Desenvolvimento\wordpress

# Gerar nova imagem
python scripts/gerar_imagem.py "Descrição da imagem"

# Editar arquivo HTML em postagens/

# Atualizar post no WordPress
python scripts/atualizar_post_python.py
```

### 4. Organizar Projeto
```powershell
.\organizar-projeto.ps1
```

---
*Última atualização: 06/08/2025*
