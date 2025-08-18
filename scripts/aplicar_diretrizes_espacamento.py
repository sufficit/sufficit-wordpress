#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script template para aplicar diretrizes de espaçamento em novos posts
"""

import json
import requests
import base64
import re
from pathlib import Path

def aplicar_espacamento_padrao(conteudo_html):
    """
    Aplica espaçamento de 20px entre texto explicativo e colunas organizadas
    
    Args:
        conteudo_html (str): Conteúdo HTML do post
    
    Returns:
        str: HTML com espaçamentos corrigidos
    """
    
    # Padrão: parágrafo seguido diretamente por colunas (sem espaçador)
    padrao_colunas = r'(<!-- /wp:paragraph -->\s*)(<!-- wp:columns -->)'
    
    # Substituição: adiciona espaçador de 20px
    espacador_20px = r'''\1
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

\2'''
    
    # Aplica a correção
    conteudo_corrigido = re.sub(padrao_colunas, espacador_20px, conteudo_html)
    
    return conteudo_corrigido

def validar_espacamento(conteudo_html):
    """
    Valida se o espaçamento está aplicado corretamente
    
    Args:
        conteudo_html (str): Conteúdo HTML do post
    
    Returns:
        dict: Resultado da validação
    """
    
    # Verifica padrões que precisam de correção
    problemas = []
    
    # Procura por parágrafos seguidos diretamente por colunas
    padrao_problema = r'<!-- /wp:paragraph -->\s*<!-- wp:columns -->'
    ocorrencias = re.findall(padrao_problema, conteudo_html)
    
    if ocorrencias:
        problemas.append(f"Encontradas {len(ocorrencias)} seções sem espaçamento de 20px")
    
    # Conta espaçadores existentes de 20px
    espacadores_20px = re.findall(r'{"height":"20px"}', conteudo_html)
    
    return {
        "problemas": problemas,
        "espacadores_20px": len(espacadores_20px),
        "precisa_correcao": len(problemas) > 0
    }

def atualizar_post_wordpress(post_id, conteudo_corrigido):
    """
    Atualiza post no WordPress com espaçamento corrigido
    
    Args:
        post_id (int): ID do post a ser atualizado
        conteudo_corrigido (str): HTML corrigido
    
    Returns:
        bool: True se sucesso, False se erro
    """
    try:
        # Configuração
        config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # URL da API
        url = f"{config['server']}/wp-json/wp/v2/posts/{post_id}"
        
        # Autenticação Basic
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
            'Content-Type': 'application/json; charset=utf-8',
            'User-Agent': 'WordPress-API-Client/1.0'
        }
        
        data = {
            "content": {
                "raw": conteudo_corrigido
            }
        }
        
        print(f"🔄 Atualizando post {post_id} com espaçamentos padronizados...")
        
        # Requisição
        response = requests.put(url, headers=headers, json=data)
        
        if response.status_code == 200:
            result = response.json()
            print(f"✅ POST {post_id} ATUALIZADO COM SUCESSO!")
            print(f"📰 Título: {result.get('title', {}).get('rendered', 'N/A')}")
            print(f"🔗 URL: {result.get('link', 'N/A')}")
            print(f"✏️ Modificado: {result.get('modified', 'N/A')}")
            print(f"📐 Espaçamento de 20px aplicado conforme diretrizes!")
            
            return True
        else:
            print(f"❌ Erro: {response.status_code}")
            print(f"📝 Resposta: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Erro inesperado: {e}")
        return False

def processar_novo_post(post_id):
    """
    Processa um novo post aplicando as diretrizes de espaçamento
    
    Args:
        post_id (int): ID do post a ser processado
    """
    
    print(f"📐 APLICANDO DIRETRIZES DE ESPAÇAMENTO - POST {post_id}")
    print("=" * 60)
    
    # Caminho do arquivo
    arquivo_post = Path(f"c:/Desenvolvimento/wordpress/postagens/{post_id}-*.html")
    arquivos = list(Path("c:/Desenvolvimento/wordpress/postagens/").glob(f"{post_id}-*.html"))
    
    if not arquivos:
        print(f"❌ Arquivo do post {post_id} não encontrado!")
        return False
    
    arquivo_post = arquivos[0]
    print(f"📄 Arquivo: {arquivo_post.name}")
    
    # Lê o conteúdo atual
    with open(arquivo_post, 'r', encoding='utf-8') as f:
        conteudo_original = f.read()
    
    # Valida o estado atual
    validacao = validar_espacamento(conteudo_original)
    print(f"🔍 Validação inicial:")
    print(f"   📊 Espaçadores 20px existentes: {validacao['espacadores_20px']}")
    
    if validacao['problemas']:
        print(f"   ⚠️ Problemas encontrados:")
        for problema in validacao['problemas']:
            print(f"      • {problema}")
    else:
        print(f"   ✅ Nenhum problema encontrado")
    
    # Aplica correções se necessário
    if validacao['precisa_correcao']:
        print(f"\n🔧 Aplicando correções de espaçamento...")
        conteudo_corrigido = aplicar_espacamento_padrao(conteudo_original)
        
        # Valida após correção
        validacao_final = validar_espacamento(conteudo_corrigido)
        print(f"📊 Após correção: {validacao_final['espacadores_20px']} espaçadores 20px")
        
        # Salva arquivo corrigido
        with open(arquivo_post, 'w', encoding='utf-8') as f:
            f.write(conteudo_corrigido)
        
        print(f"💾 Arquivo local atualizado")
        
        # Atualiza no WordPress
        sucesso = atualizar_post_wordpress(post_id, conteudo_corrigido)
        
        if sucesso:
            print(f"\n🎉 PROCESSO CONCLUÍDO COM SUCESSO!")
            print(f"📐 Diretrizes de espaçamento aplicadas ao post {post_id}")
        else:
            print(f"\n❌ Erro ao atualizar WordPress")
            
    else:
        print(f"\n✅ Post {post_id} já está conforme as diretrizes!")
        print(f"📐 Nenhuma correção necessária")

# Exemplo de uso
if __name__ == "__main__":
    # Para processar um post específico:
    # processar_novo_post(3016)  # Post de pagamentos
    # processar_novo_post(3007)  # Post sobre a Sufficit
    
    print("📐 SCRIPT DE APLICAÇÃO DE DIRETRIZES DE ESPAÇAMENTO")
    print("=" * 60)
    print("Para usar este script:")
    print("1. Chame processar_novo_post(ID_DO_POST)")
    print("2. O script irá:")
    print("   • Validar espaçamentos existentes")
    print("   • Aplicar correções necessárias")
    print("   • Atualizar arquivo local")
    print("   • Atualizar WordPress")
    print("\nExemplo: processar_novo_post(3016)")
