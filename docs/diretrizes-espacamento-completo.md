# 📐 Diretrizes de Espaçamento para Posts WordPress - GUIA COMPLETO

**Data**: 07/08/2025  
**Status**: ✅ Padrão validado e implementado com sucesso  
**Aplicado em**: Posts 3016 (Pagamentos) e 3007 (Sobre a Sufficit)

## 🎯 Objetivo

Estabelecer **padrão visual consistente** em todos os posts WordPress da Sufficit, garantindo fluxo de leitura otimizado e layout profissional.

## 📏 Regras de Espaçamento

### 🔹 Espaçamento de 20px - REGRA PRINCIPAL
**Usar entre texto explicativo e seções organizadas em colunas/tópicos**

#### Quando aplicar:
- ✅ Após parágrafo introdutório de uma seção
- ✅ Antes de colunas com ícones e tópicos
- ✅ Entre descrição e cards informativos
- ✅ Antes de listas organizadas em grid

#### Exemplos práticos aplicados:

**Post Pagamentos (3016):**
```
"Oferecemos um portfólio completo de opções..."
[20px]
💳 Cartões de Crédito | 💰 Cartões de Débito | 📋 Boleto Bancário
```

**Post Sobre a Sufficit (3007):**
```
"Nossa missão é clara e objetiva..."
[20px]
🎯 Excelência | 🤝 Parceria | 🚀 Inovação
```

### 🔹 Outros Espaçamentos

- **30px**: Entre seções principais diferentes
- **40px**: Antes de seções importantes ou destacadas
- **50px**: Antes do painel final de call-to-action

## 💻 Código Padrão

### Para 20px (Regra Principal):
```html
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

### Template para implementação:
```html
<!-- wp:paragraph -->
<p>Texto explicativo da seção...</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- Colunas com tópicos -->
</div>
<!-- /wp:columns -->
```

## 🎨 Resultados Visuais

### ❌ Antes (Problemático):
- Textos "colados" nos tópicos
- Fluxo visual prejudicado
- Layout inconsistente
- Aparência menos profissional

### ✅ Depois (Correto):
- **Respiração visual adequada**
- **Fluxo de leitura otimizado**
- **Consistência em todas as seções**
- **Layout harmonioso e profissional**

## 🔍 Identificação de Seções que Precisam de Ajuste

### Padrões que indicam necessidade de 20px:

1. **Texto + Colunas com ícones**
   ```
   Parágrafo explicativo...
   [PRECISA DE 20px]
   🔹 Tópico 1 | 🔹 Tópico 2 | 🔹 Tópico 3
   ```

2. **Descrição + Cards organizados**
   ```
   "Nossa empresa oferece..."
   [PRECISA DE 20px]
   💼 Card 1 | 🎯 Card 2 | 🚀 Card 3
   ```

3. **Introdução + Lista estruturada**
   ```
   "Os principais benefícios incluem:"
   [PRECISA DE 20px]
   ✅ Benefício A | ✅ Benefício B | ✅ Benefício C
   ```

## 📋 Checklist para Novos Posts

### ✅ Antes de Publicar:
- [ ] Identificar todas as seções com texto + tópicos organizados
- [ ] Aplicar espaçador de 20px em CADA ocorrência
- [ ] Verificar consistência visual em todo o post
- [ ] Testar fluxo de leitura
- [ ] Confirmar layout profissional

### ✅ Durante a Criação:
- [ ] Planejar espaçamentos desde o início
- [ ] Seguir template padrão estabelecido
- [ ] Manter consistência com posts existentes
- [ ] Aplicar regra em TODAS as seções similares

## 🔧 Automação

### Script Python para aplicar correções:
```python
def adicionar_espacamento_topicos(conteudo_html):
    """Adiciona espaçamento de 20px antes de colunas organizadas"""
    import re
    
    # Padrão: parágrafo seguido diretamente por colunas
    padrao = r'(<!-- /wp:paragraph -->\s*)(<!-- wp:columns -->)'
    substituicao = r'\1\n<!-- wp:spacer {"height":"20px"} -->\n<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>\n<!-- /wp:spacer -->\n\n\2'
    
    return re.sub(padrao, substituicao, conteudo_html)
```

### Comando para aplicar em posts existentes:
```python
# Usar scripts em temp/ para correções pontuais
python temp/corrigir_espacamento_post.py [POST_ID]
```

## 📊 Casos de Sucesso

### Posts já corrigidos:
1. **✅ Post 3016** - Formas de Pagamento
   - 3 seções ajustadas
   - Resultado: Layout mais profissional
   
2. **✅ Post 3007** - Sobre a Sufficit  
   - 3 seções ajustadas
   - Resultado: Fluxo visual otimizado

### Feedback visual:
- **Melhoria imediata** na aparência
- **Leitura mais fluida**
- **Consistência entre posts**
- **Padrão profissional estabelecido**

## 🚀 Próximos Passos

1. **Aplicar em novos posts** seguindo o checklist
2. **Revisar posts antigos** quando necessário
3. **Manter documentação atualizada**
4. **Treinar equipe** no novo padrão
5. **Automatizar verificações** quando possível

## 📝 Observações Importantes

- ⚠️ **SEMPRE aplicar em seções similares** - consistência é fundamental
- ⚠️ **Testar visualmente** antes de publicar
- ⚠️ **Não aplicar espaçamento excessivo** - 20px é suficiente
- ⚠️ **Manter padrão** mesmo em posts diferentes

---

**Esta diretriz é OBRIGATÓRIA para todos os novos posts e deve ser consultada sempre que houver dúvidas sobre espaçamento.**

**Última atualização**: 07/08/2025  
**Próxima revisão**: Quando necessário

**Responsável**: Equipe de Conteúdo  
**Aprovação**: ✅ Validado com sucesso em ambiente de produção
