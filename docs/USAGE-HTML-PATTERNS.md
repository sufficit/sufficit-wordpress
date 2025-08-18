# Guia de Uso - Padrões HTML WordPress

## 🎨 Padrões Visuais WordPress

### Código para Espaçadores

#### Espaçador Padrão 20px
```html
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

#### Espaçador 30px
```html
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

#### Espaçador 40px
```html
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

#### Espaçador 50px
```html
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

### Painéis de Destaque (Call-to-Action)

```html
<!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}},"border":{"radius":"15px"}},"backgroundColor":"custom-background","className":"cta-panel"} -->
<div class="wp-block-group cta-panel has-custom-background-background-color has-background" style="border-radius:15px;padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px;background:linear-gradient(135deg, rgba(0,115,170,0.7) 0%, rgba(0,95,140,0.7) 50%, rgba(0,75,110,0.7) 100%);box-shadow: 0 8px 16px rgba(0,0,0,0.2)">

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"18px","fontWeight":"600","textShadow":"1px 1px 2px rgba(0,0,0,0.5)"}}} -->
<p class="has-text-align-center" style="color:#ffffff;font-size:18px;font-weight:600;text-shadow:1px 1px 2px rgba(0,0,0,0.5)">💡 <strong>Seu texto de destaque aqui</strong></p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
```

### Imagens com Estilo Padrão

#### Imagem Alinhada à Esquerda
```html
<!-- wp:image {"id":1234,"width":"350px","sizeSlug":"large","linkDestination":"none","className":"alignleft"} -->
<figure class="wp-block-image size-large is-resized alignleft">
    <img src="caminho-da-imagem.png" alt="Descrição da imagem" class="wp-image-1234" style="width:350px;border-radius:15px;box-shadow:0 4px 8px rgba(0,0,0,0.1);margin:30px"/>
    <figcaption class="wp-element-caption">Legenda da imagem para SEO</figcaption>
</figure>
<!-- /wp:image -->
```

#### Imagem Alinhada à Direita
```html
<!-- wp:image {"id":1234,"width":"350px","sizeSlug":"large","linkDestination":"none","className":"alignright"} -->
<figure class="wp-block-image size-large is-resized alignright">
    <img src="caminho-da-imagem.png" alt="Descrição da imagem" class="wp-image-1234" style="width:350px;border-radius:15px;box-shadow:0 4px 8px rgba(0,0,0,0.1);margin:30px"/>
    <figcaption class="wp-element-caption">Legenda da imagem para SEO</figcaption>
</figure>
<!-- /wp:image -->
```

#### Imagem de Destaque (400px)
```html
<!-- wp:image {"id":1234,"width":"400px","sizeSlug":"large","linkDestination":"none","className":"aligncenter"} -->
<figure class="wp-block-image size-large is-resized aligncenter">
    <img src="caminho-da-imagem.png" alt="Descrição da imagem" class="wp-image-1234" style="width:400px;border-radius:15px;box-shadow:0 4px 8px rgba(0,0,0,0.1);margin:30px"/>
    <figcaption class="wp-element-caption">Legenda da imagem para SEO</figcaption>
</figure>
<!-- /wp:image -->
```

### Colunas Organizadas

#### 2 Colunas
```html
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- Conteúdo da coluna 1 -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- Conteúdo da coluna 2 -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

#### 3 Colunas
```html
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- Conteúdo da coluna 1 -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- Conteúdo da coluna 2 -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- Conteúdo da coluna 3 -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

## ✅ Checklist para Novos Posts

**Antes de publicar, verificar:**
- [ ] Textos explicativos têm 20px antes de colunas organizadas
- [ ] Seções principais têm 30px de separação
- [ ] Call-to-actions têm espaçamento amplo (40-50px)
- [ ] Consistência visual mantida em todo o post
- [ ] Padrão aplicado em TODAS as seções de tópicos
- [ ] Imagens têm legendas para SEO
- [ ] Alternância left/right para layout dinâmico
