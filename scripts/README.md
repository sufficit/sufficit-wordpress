# Scripts do Projeto WordPress

Este diretório contém os scripts principais para automação e gestão do projeto WordPress.

## � Python como Linguagem Padrão

Todos os novos scripts são desenvolvidos em **Python 3.7+** para garantir:
- **Portabilidade**: Funciona em Windows, Linux e macOS
- **Legibilidade**: Código mais limpo e manutenível  
- **Bibliotecas**: Rico ecossistema de bibliotecas
- **Integração**: Melhor integração com APIs REST

## �📁 Estrutura

- **`/scripts/`** - Scripts principais para uso contínuo (Python)
- **`/temp/`** - Scripts temporários e experimentais
- **`/config/`** - Arquivos de configuração

## 🚀 Scripts Principais

### 📝 **Atualização de Posts**

#### `atualizar_post_3100_audio.py` ⭐ **FUNCIONAL**
- **Função**: Atualiza o post "Áudio e Gravações de Sistema" (ID: 3100)
- **Uso**: `python scripts/atualizar_post_3100_audio.py`
- **Arquivo fonte**: `postagens/3100-audio-gravacoes-sistema-sufficit.html`
- **Status**: ✅ Funcional e testado - PROJETO CONCLUÍDO
- **Criado**: 07/08/2025
- **Características**: 
  - Basic Authentication (funciona corretamente)
  - Logs automáticos com timestamp
  - Estrutura content.raw adequada
  - Validação de erros
- **Conteúdo**: Combina recursos de texto-para-voz e gravações de sistema

#### `atualizar_post_3007_sobre_sufficit.py` ⭐ **FUNCIONAL**
- **Função**: Atualiza o post "Sobre a Sufficit" (ID: 3007)
- **Uso**: `python scripts/atualizar_post_3007_sobre_sufficit.py`
- **Arquivo fonte**: `postagens/3007-sobre-a-sufficit-excelencia-solucoes-tecnologicas.html`
- **Status**: ✅ Funcional e testado - PROJETO CONCLUÍDO
- **Criado**: 07/08/2025
- **Características**: 
  - Basic Authentication (funciona corretamente)
  - Logs automáticos com timestamp
  - Estrutura content.raw adequada
  - Validação de erros

#### `atualizar_imagem_capa.py` ⭐ **ATUALIZADO - FUNCIONAL**
- **Função**: Script genérico para atualizar imagem de capa (featured image) de posts
- **Uso**: 
  - **Atualizar capa com URL**: `python scripts/atualizar_imagem_capa.py 2988 "https://exemplo.com/imagem.jpg"`
  - **Atualizar capa com arquivo local**: `python scripts/atualizar_imagem_capa.py 3007 "capa-nova.png"`
  - **Verificar capa atual**: `python scripts/atualizar_imagem_capa.py --verificar 2988`
  - **Caminho completo**: `python scripts/atualizar_imagem_capa.py 3100 "c:/pasta/imagem.webp"`
- **Status**: ✅ Funcional e testado
- **Atualizado**: 07/08/2025
- **⚠️ ORIENTAÇÃO IMPORTANTE**: 
  - **Se usuário pedir para atualizar imagem de capa, SEMPRE perguntar:**
    1. **ID do post** (número específico)
    2. **URL da imagem** ou caminho do arquivo local
  - **Não assumir valores padrão** - sempre confirmar ambos os parâmetros
  - **Exemplos de pergunta**: "Qual o ID do post?" e "Qual a URL da imagem?"
- **Características**:
  - **Parâmetros flexíveis**: Recebe ID do post e URL/caminho da imagem
  - **Suporte a URLs**: Baixa automaticamente imagens de URLs externas
  - **Arquivos locais**: Suporta caminhos relativos (pasta imagens/) e absolutos
  - **Verificação prévia**: Mostra capa atual antes de substituir
  - **Upload otimizado**: Detecta tipo de arquivo automaticamente
  - **Formatos suportados**: PNG, JPG, JPEG, WebP, GIF
  - **Validação**: Verifica se post existe antes de processar
  - **Logs detalhados**: Relatório completo do processo
- **Funcionalidades**:
  1. **✅ Upload automático**: Faz upload da imagem para biblioteca do WordPress
  2. **✅ Configuração automática**: Define como featured image do post
  3. **✅ Verificação de status**: Modo `--verificar` para consultar capa atual
  4. **✅ Flexibilidade**: Aceita URLs externas ou arquivos locais
  5. **✅ Tratamento de erros**: Validação completa em todas as etapas

### `atualizar_post_generico.py` ⭐ **FUNCIONAL**
**Função**: Script genérico para atualizar qualquer post no WordPress via REST API  
**Linguagem**: Python 3.7+  
**Uso**:
```bash
cd c:\Desenvolvimento\wordpress
python scripts/atualizar_post_generico.py <post_id> <arquivo_html>
```
**Exemplos**:
```bash
# Atualizar post de telefonia IP
python scripts/atualizar_post_generico.py 1515 postagens/como-funciona-telefone-ip.html

# Atualizar qualquer outro post
python scripts/atualizar_post_generico.py 1234 postagens/outro-post.html
```

#### `criar_post_atendimento_3091.py` ⭐ **CONCLUÍDO**
- **Função**: Script utilizado para criar o post "Canais de Atendimento Sufficit" (ID: 3091)
- **Uso**: `python scripts/criar_post_atendimento_3091.py`
- **Arquivo fonte**: `postagens/3091-canais-atendimento-sufficit.html`
- **Status**: ✅ Projeto concluído - Script de referência
- **Criado**: 07/08/2025
- **Características**: 
  - Criação completa de post no WordPress
  - Configuração automática de título e excerpt
  - Publicação direta (status: publish)
  - Renomeação automática do arquivo HTML com ID

#### `atualizar_post_3091.py` ⭐ **FUNCIONAL**
- **Função**: Atualiza o post "Canais de Atendimento Sufficit" (ID: 3091)
- **Uso**: `python scripts/atualizar_post_3091.py`
- **Arquivo fonte**: `postagens/3091-canais-atendimento-sufficit.html`
- **Status**: ✅ Funcional e testado - PROJETO CONCLUÍDO
- **Criado**: 07/08/2025
- **Características**: 
  - Atualização específica do post de canais de atendimento
  - Logs de acompanhamento
  - Tratamento de erros personalizado
  - Validação de conteúdo
**Dependências**: 
- `requests` library: `pip install requests`
- `config/config.json` com credenciais WordPress

**Descrição**: 
- **Genérico**: Aceita qualquer ID de post e arquivo HTML como parâmetros
- **Validações**: Verifica se arquivo existe e se post_id é válido
- **Logs detalhados**: Salva logs únicos com timestamp em `temp/`
- **Tratamento de erros**: Retorna códigos de saída apropriados
- **Estrutura correta**: Usa `{"content": {"raw": "html"}}` para WordPress API

---

## 🏆 PROJETO CONCLUÍDO - POST "SOBRE A SUFFICIT"

### 📊 Status: ✅ ENTREGUE E FUNCIONANDO
**Data de conclusão**: 07/08/2025  
**Post ID**: 3007  
**URL**: https://wordpress.sufficit.com.br/sobre-a-sufficit-excelencia-solucoes-tecnologicas/

### 🎯 Scripts Relacionados
- `atualizar_post_3007_sobre_sufficit.py` (principal)
- `gerar_imagem.py` (para imagens DALL-E)
- `upload_imagens.py` (upload para WordPress)

### 📁 Arquivos Gerados
- `postagens/3007-sobre-a-sufficit-excelencia-solucoes-tecnologicas.html`
- `imagens/post-3007-sobre-empresa-*.png` (3 imagens)
- `temp/relatorio_final_post_3007_completo.txt`

### 🔄 Comandos para Manutenção
```bash
# Atualizar o post
python scripts/atualizar_post_3007_sobre_sufficit.py

# Gerar novas imagens (se necessário)
python scripts/gerar_imagem.py "prompt da imagem"

# Fazer upload de imagens
python scripts/upload_imagens.py
```

---

## 🏆 PROJETO CONCLUÍDO - POST "ÁUDIO E GRAVAÇÕES"

### 📊 Status: ✅ ENTREGUE E FUNCIONANDO
**Data de conclusão**: 07/08/2025  
**Post ID**: 3100  
**URL**: https://wordpress.sufficit.com.br/audio-e-gravacoes-de-sistema-sufficit-recursos-profissionais/

### 🎯 Scripts Relacionados
- `atualizar_post_3100_audio.py` (principal)
- `criar_post_audio.py` (criação inicial)
- `organizar_projeto_audio.py` (organização)
- `gerar_imagem.py` (para imagens DALL-E)

### 📁 Arquivos Gerados
- `postagens/3100-audio-gravacoes-sistema-sufficit.html`
- `imagens/post-3100-audio-sistema-principal.png`
- `temp/log_atualizacao_audio_3100.json`

### 🔄 Comandos para Manutenção
```bash
# Atualizar o post
python scripts/atualizar_post_3100_audio.py

# Organizar arquivos do projeto
python scripts/organizar_projeto_audio.py

# Gerar novas imagens (se necessário)
python scripts/gerar_imagem.py "prompt da imagem"
```

### 📋 Características do Post
- Combina conteúdo de texto-para-voz e gravações de sistema
- Interfaces modernas com Ricardo e Vitória (vozes)
- Recursos de upload MP3/WAV e mixing de áudio
- Padrões visuais WordPress com colunas e espaçamento

---

### `atualizar_post_python.py` (OBSOLETO)
**Função**: Atualiza posts no WordPress via REST API (versão específica)  
**Status**: ⚠️ **Obsoleto** - Use `atualizar_post_generico.py` em vez deste  
**Linguagem**: Python 3.7+  
**Limitações**: 
- Hardcoded para post ID 1515
- Caminho fixo do arquivo
- Menos flexível que a versão genérica

---

### `gerar_imagem.py`
**Função**: Gera imagens usando API DALL-E da OpenAI  
**Linguagem**: Python 3.7+  
**Uso**:
```bash
cd c:\Desenvolvimento\wordpress
python scripts/gerar_imagem.py "Descrição da imagem"
```
**Dependências**: 
- `requests` library: `pip install requests`
- `config/config-openai.json` com credenciais OpenAI

**Descrição**: 
- Normaliza caracteres especiais no prompt automaticamente
- Conecta com API DALL-E 3
- Gera imagem 1024x1024 por padrão
- Salva resultado em `temp/resultado_imagem.txt`
- Retorna URL da imagem gerada

**Argumentos**:
- `prompt` (obrigatório): Descrição da imagem
- `--model`: Modelo a usar (padrão: dall-e-3)
- `--size`: Tamanho da imagem (padrão: 1024x1024)

---

### `upload_imagens.py`
**Função**: Faz upload de imagens para biblioteca de mídia do WordPress  
**Linguagem**: Python 3.7+  
**Uso**:
```bash
cd c:\Desenvolvimento\wordpress
python scripts/upload_imagens.py
```
**Dependências**: 
- `requests` library: `pip install requests`
- `config/config.json` com credenciais WordPress

**Descrição**: 
- Faz upload de todas as imagens da pasta `imagens/`
- **Verifica se imagem já existe** e atualiza ao invés de duplicar
- Suporta PNG, JPG, JPEG, GIF, WEBP
- Retorna ID e URL das imagens na biblioteca de mídia
- Mostra estatísticas de novas vs atualizadas
- Salva resultado em `temp/resultado_upload.txt` e `temp/resumo_uploads.txt`
- Usa autenticação Basic para WordPress REST API

---

### `gerar-imagem-v5.ps1` (Legacy)
**Função**: Versão PowerShell do gerador de imagens  
**Status**: Mantido para compatibilidade  
**Recomendação**: Usar `gerar_imagem.py`

## � Diretrizes de Espaçamento

### `aplicar_diretrizes_espacamento.py`
**Novo script para aplicar padrões visuais em posts**

**Funcionalidades:**
- ✅ Detecta seções que precisam de espaçamento de 20px
- ✅ Aplica correções automaticamente
- ✅ Valida resultado final
- ✅ Atualiza WordPress via API
- ✅ Mantém consistência visual entre posts

**Uso:**
```python
from scripts.aplicar_diretrizes_espacamento import processar_novo_post

# Aplicar diretrizes a um post específico
processar_novo_post(3016)  # Post de pagamentos
processar_novo_post(3007)  # Post sobre Sufficit
```

**Padrão aplicado:**
- 20px entre texto explicativo e colunas organizadas
- Validação automática de consistência
- Backup automático do arquivo original

**Casos de uso:**
- Novos posts criados
- Correção de posts existentes  
- Padronização visual em lote

### 1. Instalar Python
```bash
# Verificar versão
python --version

# Instalar dependências
pip install requests
```

### 2. Configurar APIs
Criar arquivos em `config/`:

**`config/config.json`** (WordPress):
```json
{
    "server": "https://wordpress.sufficit.com.br",
    "username": "seu_usuario@email.com",
    "password": "seu_application_password"
}
```

**`config/config-openai.json`** (OpenAI):
```json
{
    "organization": "org-xxxxxxxxxxxxxxxxxxxx",
    "token": "sk-xxxxxxxxxxxxxxxxxxxx"
}
```

### 3. Workflow Típico
```bash
# 1. Gerar imagem
python scripts/gerar_imagem.py "Telefone VoIP moderno"

# 2. Fazer upload das imagens para biblioteca de mídia
python scripts/upload_imagens.py

# 3. Editar HTML em postagens/ (com IDs das imagens da biblioteca)

# 4. Atualizar WordPress
python scripts/atualizar_post_python.py
```

## 📋 Logs e Resultados

Scripts salvam resultados em `temp/`:
- `temp/resultado_sucesso.txt` - Operações bem-sucedidas
- `temp/resultado_erro.txt` - Logs de erro
- `temp/resultado_imagem.txt` - URLs de imagens geradas
- `temp/resultado_upload.txt` - Detalhes do último upload
- `temp/resumo_uploads.txt` - Resumo de todos os uploads de imagens

## 🛠️ Desenvolvimento

### Padrões de Código Python
```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Descrição do script
"""

import json
import requests
from pathlib import Path

def funcao_principal():
    """Documentação da função"""
    try:
        # Código principal
        pass
    except Exception as e:
        print(f"❌ Erro: {e}")

if __name__ == "__main__":
    funcao_principal()
```

### Adicionar Novo Script
1. Criar em `temp/` para testes
2. Validar funcionalidade
3. Quando solicitado, mover para `scripts/`
4. Documentar neste README
5. Usar Python como padrão

### Migração PowerShell → Python
- **Prioridade**: Novos scripts em Python
- **Legacy**: Manter scripts PowerShell existentes funcionais
- **Gradual**: Migrar conforme necessidade

---

**Última atualização**: 06/08/2025  
**Versão**: 2.0 - Python como padrão
