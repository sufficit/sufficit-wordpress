#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script genérico para atualizar imagem de capa (featured image) de posts WordPress
"""

import json
import requests
import base64
import re
from pathlib import Path

def carregar_config():
    """Carrega configuração do WordPress"""
    config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
    with open(config_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def preparar_headers(config):
    """Prepara headers de autenticação"""
    credentials = f"{config['username']}:{config['password']}"
    encoded_credentials = base64.b64encode(credentials.encode()).decode()
    
    return {
        'Authorization': f'Basic {encoded_credentials}',
        'Content-Type': 'application/json; charset=utf-8'
    }

def buscar_post_por_slug(slug, config, headers):
    """Busca post pelo slug"""
    url = f"{config['server']}/wp-json/wp/v2/posts?slug={slug}"
    response = requests.get(url, headers=headers)
    
    if response.status_code == 200:
        posts = response.json()
        if posts:
            return posts[0]
    return None

def extrair_primeira_imagem_do_conteudo(post_content):
    """Extrai a primeira imagem do conteúdo HTML do post"""
    # Padrão para encontrar tags img com src
    padrao_img = r'<img[^>]+src=["\']([^"\']+)["\'][^>]*>'
    matches = re.findall(padrao_img, post_content, re.IGNORECASE)
    
    if matches:
        return matches[0]
    return None

def buscar_media_por_url(image_url, config, headers):
    """Busca o ID da mídia pela URL"""
    # Remove query parameters da URL
    clean_url = image_url.split('?')[0]
    
    # Busca na biblioteca de mídia
    url = f"{config['server']}/wp-json/wp/v2/media?search={clean_url.split('/')[-1]}&per_page=50"
    response = requests.get(url, headers=headers)
    
    if response.status_code == 200:
        media_items = response.json()
        for media in media_items:
            if clean_url in media['source_url']:
                return media['id']
    
    # Se não encontrou, tenta buscar pelo nome do arquivo
    filename = clean_url.split('/')[-1]
    url = f"{config['server']}/wp-json/wp/v2/media?per_page=100"
    response = requests.get(url, headers=headers)
    
    if response.status_code == 200:
        media_items = response.json()
        for media in media_items:
            if filename in media['source_url']:
                return media['id']
    
    return None

def atualizar_featured_image(post_id, media_id, config, headers):
    """Atualiza a featured image do post"""
    url = f"{config['server']}/wp-json/wp/v2/posts/{post_id}"
    data = {
        "featured_media": media_id
    }
    
    response = requests.put(url, headers=headers, json=data)
    return response.status_code == 200, response

def atualizar_capa_com_primeira_imagem(slug_ou_id):
    """Função principal para atualizar capa com primeira imagem do conteúdo"""
    try:
        print("🚀 ATUALIZADOR DE IMAGEM DE CAPA - SUFFICIT")
        print("=" * 60)
        
        # Carrega configuração
        config = carregar_config()
        headers = preparar_headers(config)
        
        # Busca o post
        if str(slug_ou_id).isdigit():
            # Se for número, busca por ID
            url = f"{config['server']}/wp-json/wp/v2/posts/{slug_ou_id}"
            response = requests.get(url, headers=headers)
            post = response.json() if response.status_code == 200 else None
        else:
            # Se for texto, busca por slug
            post = buscar_post_por_slug(slug_ou_id, config, headers)
        
        if not post:
            print(f"❌ Post não encontrado: {slug_ou_id}")
            return False
        
        print(f"📝 POST ENCONTRADO:")
        print(f"   ID: {post['id']}")
        print(f"   Título: {post['title']['rendered']}")
        print(f"   Slug: {post['slug']}")
        print(f"   URL: {post['link']}")
        
        # Verifica capa atual
        current_featured = post.get('featured_media', 0)
        print(f"\n🖼️ CAPA ATUAL: {'ID ' + str(current_featured) if current_featured else 'Nenhuma'}")
        
        # Extrai primeira imagem do conteúdo
        print(f"\n🔍 ANALISANDO CONTEÚDO DO POST...")
        content = post['content']['rendered']
        primeira_imagem_url = extrair_primeira_imagem_do_conteudo(content)
        
        if not primeira_imagem_url:
            print("❌ Nenhuma imagem encontrada no conteúdo do post")
            return False
        
        print(f"✅ PRIMEIRA IMAGEM ENCONTRADA:")
        print(f"   URL: {primeira_imagem_url}")
        
        # Busca o ID da mídia
        print(f"\n🔍 BUSCANDO ID DA MÍDIA...")
        media_id = buscar_media_por_url(primeira_imagem_url, config, headers)
        
        if not media_id:
            print("❌ Não foi possível encontrar o ID da mídia na biblioteca")
            print("   A imagem pode não estar na biblioteca do WordPress")
            return False
        
        print(f"✅ MÍDIA ENCONTRADA: ID {media_id}")
        
        # Verifica se já é a capa atual
        if current_featured == media_id:
            print("ℹ️ Esta imagem já é a capa atual do post")
            return True
        
        # Atualiza a featured image
        print(f"\n🔄 ATUALIZANDO IMAGEM DE CAPA...")
        sucesso, response = atualizar_featured_image(post['id'], media_id, config, headers)
        
        if sucesso:
            print(f"✅ CAPA ATUALIZADA COM SUCESSO!")
            print(f"   Post ID: {post['id']}")
            print(f"   Nova capa: Mídia ID {media_id}")
            print(f"   URL do post: {post['link']}")
            return True
        else:
            print(f"❌ ERRO ao atualizar capa: {response.status_code}")
            if hasattr(response, 'text'):
                print(f"   Detalhes: {response.text}")
            return False
            
    except Exception as e:
        print(f'❌ ERRO: {e}')
        return False

if __name__ == "__main__":
    # Exemplo de uso: atualizar post específico
    slug_post = "porque-escolher-sufficit-diferenciais-competitivos"
    atualizar_capa_com_primeira_imagem(slug_post)
