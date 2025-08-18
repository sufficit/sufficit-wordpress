# Instruções para GitHub Copilot - Projeto WordPress

## 🌐 Configurações Gerais
- **Idioma principal**: pt-br
- **Linguagem padrão**: Python 3.7+
- **Autorização**: O assistente pode realizar operações de leitura sem solicitar permissão

## 📁 Estrutura do Projeto

### Organização de Pastas
```
c:\Desenvolvimento\wordpress/
├── 📁 config/                  # 🔧 Configurações
│   ├── config.json             # WordPress API
│   └── config-openai.json      # OpenAI DALL-E
├── 📁 postagens/               # 📝 Arquivos HTML
├── 📁 imagens/                 # 🖼️ Imagens geradas
├── 📁 scripts/                 # 🚀 Scripts permanentes (Python)
├── 📁 temp/                    # 🧪 Scripts temporários
└── 📁 .github/                 # Instruções e docs
```

### Regras de Organização

#### 📝 Postagens HTML
- **Local**: `postagens/`
- **Formato**: HTML pronto para WordPress com blocos Gutenberg
- **Nomenclatura**: 
  - **Posts novos**: `nome-do-post.html` (sem espaços, usar hífens)
  - **Posts publicados**: `{id}-nome-do-post.html` (incluir ID após primeira publicação)
- **Exemplos**: 
  - `postagens/como-funciona-telefone-ip.html` (antes da publicação)
  - `postagens/2988-como-funciona-telefone-ip.html` (após publicação)
- **Regra importante**: Sempre renomear o arquivo incluindo o ID do post após criação/atualização inicial no WordPress

#### 🖼️ Imagens
- **Local**: `imagens/` (raiz do projeto)
- **Tipos**: PNG, JPG geradas via DALL-E
- **Organização**: Todas as imagens diretamente em `imagens/` (não usar subpastas)
- **Nomenclatura**: 
  - Posts novos: `post-{nome-descritivo}`
  - Posts já publicados: `post-{id}-{nome-descritivo}`
  - **Capas**: `post-{id}-featured.*` (dimensões 1152x866)
  - Geral: Descritiva e sem espaços
- **Exemplos**: 
  - `imagens/post-telefone-voip-funcionamento.png`
  - `imagens/post-2988-telefonia-ip-vantagens.png`
  - `imagens/post-3007-featured.png` (capa 1152x866)

#### 🚀 Scripts Permanentes
- **Local**: `scripts/`
- **Linguagem**: Python (padrão)
- **Documentação**: Sempre atualizar `scripts/README.md`
- **Critério**: Apenas quando explicitamente solicitado para uso futuro

#### 🧪 Scripts Temporários
- **Local**: `temp/`
- **Uso**: Experimentação e testes
- **Limpeza**: Manter organizado, remover não utilizados
- **Linguagens**: Python, PowerShell, Batch conforme necessário

#### 🔧 Configurações
- **Local**: `config/`
- **Arquivos**: 
  - `config/config.json` (WordPress API)
  - `config/config-openai.json` (OpenAI DALL-E)

## 🐍 Python como Linguagem Padrão

### Princípios
- **Priorizar Python** para novos scripts
- **Usar PowerShell** apenas quando necessário (Windows específico)
- **Estrutura modular** com funções bem definidas
- **Tratamento de erros** robusto
- **Documentação** clara em português
- **Encoding UTF-8** sempre
- **Consultar**: `docs/USAGE-PYTHON.md` para exemplos de código

## 🔄 Workflow de Desenvolvimento

### 1. Criação de Conteúdo
1. Criar HTML em `postagens/`
2. Gerar imagens em `imagens/` via Python
3. Atualizar WordPress via script Python

### 2. Gestão de Scripts
1. **Desenvolvimento**: Criar em `temp/`
2. **Validação**: Testar funcionalidade
3. **Produção**: Mover para `scripts/` (quando solicitado)
4. **Documentação**: Atualizar `scripts/README.md`

### 3. Organização Periódica
- Executar `organizar-projeto.ps1`
- Limpar pasta `temp/`
- Atualizar documentação

## 🎨 Padrões Visuais WordPress

### Painéis de Destaque (Call-to-Action)
- **Estilo padrão**: Gradiente azul com sombra
- **Cores**: Linear gradient azul (0,115,170) → (0,95,140) → (0,75,110)
- **Sombra**: `box-shadow: 0 8px 16px rgba(0,0,0,0.2)`
- **Texto**: Branco com `text-shadow` para legibilidade
- **Bordas**: `border-radius:15px`
- **Padding**: `30px` em todas as direções
- **Ícone**: 💡 para destaque visual
- **Consultar**: `docs/USAGE-HTML-PATTERNS.md` para código HTML

### Imagens no Post
- **Alinhamento**: Alternar left/right para layout dinâmico
- **Dimensões**: 350px width para imagens menores, 400px para destaque
- **Estilo**: `border-radius: 15px` + `box-shadow: 0 4px 8px rgba(0,0,0,0.1)`
- **Espaçamento**: `margin: 30px` entre imagem e texto
- **Captions**: Sempre incluir para SEO e acessibilidade
- **Consultar**: `docs/USAGE-HTML-PATTERNS.md` para código HTML

### Espaçadores - PADRÃO ATUALIZADO ⭐
- **20px**: Entre texto explicativo e tópicos organizados (NOVO PADRÃO)
- **30px**: Entre seções principais do post
- **40px**: Antes de seções importantes
- **50px**: Antes do painel final de call-to-action
- **Consultar**: `docs/USAGE-HTML-PATTERNS.md` para código HTML

### Regra de Espaçamento 20px 📐
**Sempre usar 20px entre:**
- Texto introdutório ➜ Colunas com tópicos/ícones
- Parágrafo explicativo ➜ Lista organizada em grid
- Descrição de seção ➜ Cards informativos
- Contextualização ➜ Elementos visuais estruturados

**Exemplos de aplicação:**
- "Nossa missão é clara..." ➜ Colunas de valores (Excelência, Parceria, Inovação)
- "Oferecemos um portfólio completo..." ➜ Cartões de pagamento (Crédito, Débito, Boleto)
- "Nossa experiência abrange..." ➜ Setores de atuação (Serviços, Indústria, Comércio)
- "A segurança das informações..." ➜ Recursos de segurança (SSL, PCI DSS, etc.)

### ✅ Checklist para Novos Posts
**Antes de publicar, verificar:**
- [ ] Textos explicativos têm 20px antes de colunas organizadas
- [ ] Seções principais têm 30px de separação
- [ ] Call-to-actions têm espaçamento amplo (40-50px)
- [ ] Consistência visual mantida em todo o post
- [ ] Padrão aplicado em TODAS as seções de tópicos

## 📋 Padrões de Codificação

### Nomenclatura
- **Arquivos**: `snake_case.py` para Python
- **Funções**: `snake_case()`
- **Variáveis**: `snake_case`
- **Constantes**: `UPPER_CASE`

### Configurações
- **Paths**: Usar `Path()` do pathlib
- **Encoding**: Sempre UTF-8
- **JSON**: Indentação de 4 espaços
- **Comentários**: Em português
- **Consultar**: `docs/USAGE-PYTHON.md` para exemplos

## 🎯 Scripts Principais

### 🖼️ **ORIENTAÇÃO CRÍTICA - Atualização de Imagem de Capa**
**⚠️ SE USUÁRIO PEDIR PARA ATUALIZAR IMAGEM DE CAPA:**
1. **SEMPRE PERGUNTAR** o ID do post (número específico)
2. **SEMPRE PERGUNTAR** a URL da imagem ou caminho do arquivo
3. **NÃO ASSUMIR** valores baseados no contexto
4. **NÃO EXECUTAR** sem confirmar ambos os parâmetros

**✅ Script**: `scripts/atualizar_imagem_capa.py POST_ID URL_IMAGEM`
**📚 Documentação**: `docs/orientacoes-assistentes-capa.md`

### `scripts/atualizar_post_python.py`
- ⚠️ **PROBLEMA**: Atualiza post ID fixo (1515), não recebe parâmetro
- Usa estrutura correta do campo `content`
- Salva logs em `temp/`

### **✅ SCRIPT CORRETO - Atualização de Imagem de Capa**
- **Local**: `scripts/atualizar_imagem_capa.py` (script genérico funcional)
- **Uso**: Permite especificar qualquer post ID e URL/caminho da imagem
- **Autenticação**: Basic auth (funcionou)
- **Redimensionamento**: Automático para 1152x866 (padrão de capa)
- **Processamento**: Em memória - não altera arquivo original
- **Dependências**: Pillow opcional (pip install Pillow)
- **Comando**: `python scripts/atualizar_imagem_capa.py POST_ID URL_IMAGEM`
- **Verificação**: `python scripts/atualizar_imagem_capa.py --verificar POST_ID`

### `scripts/gerar_imagem.py`
- Gera imagens via DALL-E 3
- Normaliza caracteres especiais
- Interface via argumentos de linha de comando
- **Consultar**: `docs/USAGE-DALLE-API.md` para exemplos

### **🔧 COMANDOS QUE FUNCIONAM**
- **Consultar**: `docs/USAGE-PYTHON.md` para comandos completos

**⚠️ EVITAR:**
- `scripts/atualizar_post_python.py` (ID fixo errado)
- Autenticação Bearer (usar Basic)
- Posts ID 1515 (lixeira)

## 📚 Recursos e APIs

### WordPress REST API
- **Endpoint**: `/wp-json/wp/v2/posts/{id}`
- **Método**: PUT para atualizações
- **Estrutura content**: `{"content": {"raw": "html"}}`
- **Headers**: Authorization Basic auth
- **Consultar**: `docs/USAGE-WORDPRESS-API.md` para exemplos completos

### OpenAI DALL-E API
- **Endpoint**: `https://api.openai.com/v1/images/generations`
- **Modelo**: dall-e-3
- **Tamanho padrão**: 1024x1024
- **Rate limits**: Respeitar limites da API
- **Consultar**: `docs/USAGE-DALLE-API.md` para exemplos completos

---

**Última atualização**: 08/08/2025  
**Versão**: 3.0 - Documentação modularizada

## 📚 Documentação Técnica

### Guias de Uso (docs/USAGE-*.md)
- **USAGE-PYTHON.md**: Exemplos e padrões de código Python
- **USAGE-WORDPRESS-API.md**: Conexão e uso da API WordPress
- **USAGE-DALLE-API.md**: Geração de imagens com OpenAI DALL-E
- **USAGE-HTML-PATTERNS.md**: Padrões visuais e código HTML

### Orientações Específicas (docs/orientacoes-*.md)
- **orientacoes-assistentes-capa.md**: Protocolo para atualização de capas

### Diretrizes e Templates (docs/*)
- **diretrizes-espacamento-completo.md**: Regras de espaçamento
- **template-posts-tecnologia.md**: Modelo para posts técnicos
- **melhores-praticas-tecnicas.md**: Boas práticas gerais

## Internal Documentation Guidelines
* **Directory**: `/docs`, use this directory to store internal documentation files;
* **File Name**: use the following format for internal documentation files: `YYYYMMDDHHMM-description.md`, like `202507151109-migration.md`;
* **Content**: include detailed information about the internal processes, architecture decisions, and other relevant information that can help developers understand the system better;

## Posts
* **Featured Image**: always ask for post ID and image URL/path before updating the featured image of a post;
* **Featured Image File Name**: `post-{id}-featured.*`;
* **Featured Image Dimensions**: always resize featured images to 1152x866 (landscape) before uploading;
