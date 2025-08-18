# Melhores Práticas Técnicas - WordPress

**Atualizado**: 07/08/2025  
**Fonte**: Sessão de otimização post telefonia IP

## 🔧 Scripts Python Essenciais

### **Script Genérico de Atualização**
```python
# Uso: python scripts/atualizar_post_generico.py <post_id> <arquivo_html>
# Benefícios: Flexível, logs detalhados, validações automáticas
# Exemplo: python scripts/atualizar_post_generico.py 2988 postagens/2988-como-funciona-telefone-ip.html
```

**Características importantes:**
- Validação de argumentos
- Verificação de arquivo existente
- Logs com timestamp únicos
- Tratamento robusto de erros
- Códigos de saída apropriados

### **Upload de Imagens**
```python
# Uso: python scripts/upload_imagens.py <caminho_imagem1> <caminho_imagem2>
# Detecta duplicatas e atualiza automaticamente
# Mantém IDs existentes
```

## 🎨 CSS Patterns Eficazes

### **Imagens com Estilo Moderno**
```css
/* Aplicar em todas as imagens */
border-radius: 15px; 
box-shadow: 0 4px 8px rgba(0,0,0,0.1);

/* Para imagens alinhadas à direita */
margin-left: 30px; 
margin-bottom: 20px; 
margin-top: 10px;
```

### **Call-to-Action com Gradiente**
```css
/* Background com transparência */
background: linear-gradient(135deg, rgba(0,115,170,0.7) 0%, rgba(0,95,140,0.7) 50%, rgba(0,75,110,0.7) 100%);

/* Text-shadow para contraste */
text-shadow: 2px 2px 4px rgba(0,0,0,0.3);

/* Box-shadow para profundidade */
box-shadow: 0 8px 16px rgba(0,0,0,0.2);
```

### **Espaçamentos Otimizados**
```html
<!-- Espaçamento padrão entre seções -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>

<!-- Espaçamento maior antes de CTA -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>

<!-- Espaçamento menor para detalhes -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
```

## 📁 Convenções de Nomenclatura

### **Arquivos HTML**
```
Antes da publicação: nome-do-post.html
Após primeira publicação: {id}-nome-do-post.html

Exemplos:
como-funciona-telefone-ip.html → 2988-como-funciona-telefone-ip.html
```

### **Imagens**
```
Antes da publicação: post-{nome-descritivo}.png
Após primeira publicação: post-{id}-{nome-descritivo}.png

Exemplos:
post-telefone-voip-funcionamento.png → post-2988-funcionamento-voip.png
```

### **URLs WordPress**
```
Estrutura padrão: /wp-content/uploads/2025/08/post-{id}-{descricao}.png
Sempre usar no HTML: src="/wp-content/uploads/2025/08/..."
```

## 🔄 Workflow de Publicação

### **1. Criação Inicial**
```bash
# 1. Criar arquivo HTML
touch postagens/nome-do-post.html

# 2. Criar imagens
python scripts/gerar_imagem.py "Descrição da imagem"
# Salvar como: post-{nome-descritivo}.png
```

### **2. Primeira Publicação**
```bash
# 1. Upload imagens iniciais
python scripts/upload_imagens.py imagens/post-*.png

# 2. Publicar post (obter ID via WordPress admin)
# Anotar ID retornado (ex: 2988)
```

### **3. Atualização com ID**
```bash
# 1. Renomear arquivo HTML
ren "nome-do-post.html" "{id}-nome-do-post.html"

# 2. Renomear imagens
ren "post-nome.png" "post-{id}-nome.png"

# 3. Atualizar URLs no HTML
# Alterar todas as referências para post-{id}-

# 4. Re-upload imagens
python scripts/upload_imagens.py imagens/post-{id}-*.png

# 5. Atualizar post
python scripts/atualizar_post_generico.py {id} postagens/{id}-nome-do-post.html
```

## 🎯 Templates de Estrutura

### **Posts Técnicos (Tecnologia)**
```html
1. H2: O que é [conceito]?
2. H2: Como funciona [tecnologia]
3. H2: Diferenças/Comparação (+ tabela)
4. H2: Vantagens/Benefícios
5. H2: Requisitos técnicos
6. H2: Casos de uso
   - H3: Por segmento/público
7. H2: Dicas de implementação
8. Call-to-action final
```

### **Layout de Imagens**
```html
<!-- Imagem 1: Conceito central (centro) -->
<figure class="wp-block-image aligncenter">

<!-- Imagem 2: Processo/funcionamento (centro) -->
<figure class="wp-block-image aligncenter">

<!-- Imagem 3: Aplicação específica (direita) -->
<figure class="wp-block-image alignright">
```

## 🛠️ Comandos Úteis

### **PowerShell**
```powershell
# Renomear arquivo
ren "arquivo-antigo.html" "novo-nome.html"

# Navegar e executar
cd "c:\Desenvolvimento\wordpress"; python scripts/script.py
```

### **Python**
```python
# Validar argumentos
if len(sys.argv) != 3:
    print("Uso: script.py <param1> <param2>")
    sys.exit(1)

# Verificar arquivo existe
if not Path(arquivo).exists():
    print(f"❌ Arquivo não encontrado: {arquivo}")
    return False
```

## 📊 Validações de Qualidade

### **Checklist Pré-Publicação**
- [ ] Estrutura H2/H3 correta
- [ ] Imagens com alt text descritivo
- [ ] URLs das imagens corretas
- [ ] Espaçamentos adequados
- [ ] Call-to-action presente
- [ ] Nomenclatura consistente
- [ ] Links funcionando
- [ ] Tamanho do conteúdo adequado (>15k chars)

### **Checklist Pós-Publicação**
- [ ] Arquivo renomeado com ID
- [ ] Imagens renomeadas com ID
- [ ] URLs atualizadas no HTML
- [ ] Re-upload de imagens concluído
- [ ] Post atualizado no WordPress
- [ ] URL final funcionando
- [ ] Visual conforme esperado

## 🚀 Automações Futuras

### **Scripts para Implementar**
1. **Auto-rename após obter ID**: Script que renomeia automaticamente após publicação
2. **Validador de nomenclatura**: Verifica se convenções estão sendo seguidas
3. **Template generator**: Cria estrutura base para novos posts
4. **Metrics tracker**: Acompanha performance dos posts

### **Melhorias de Workflow**
1. Integração com WordPress API para obter ID automaticamente
2. Validação de imagens antes do upload
3. Backup automático antes de mudanças
4. Verificação de links quebrados

---

**Última atualização**: 07/08/2025  
**Próxima revisão**: Próximo projeto de post
