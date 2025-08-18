#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para remover posts antigos de áudio que foram unificados no post 3100
- Post "Audio e Gravações de Sistema" 
- Post "Texto para Voz"
"""

import json
import requests
import base64
from pathlib import Path

def remover_posts_audio_antigos():
    """Remove os posts antigos de áudio que foram combinados no post 3100"""
    try:
        # Configuração
        config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # URLs dos posts antigos para identificar IDs
        posts_para_remover = [
            "https://wordpress.sufficit.com.br/audio-e-gravacoes-de-sistema/",
            "https://wordpress.sufficit.com.br/texto-para-voz/"
        ]
        
        # Autenticação Basic
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
            'Content-Type': 'application/json; charset=utf-8',
            'User-Agent': 'WordPress-API-Client/1.0'
        }
        
        print("🔍 Procurando posts antigos de áudio para remoção...")
        
        # Buscar posts com palavras-chave relacionadas
        search_terms = ["audio", "gravacoes", "texto para voz", "voz"]
        posts_encontrados = []
        
        for term in search_terms:
            search_url = f"{config['server']}/wp-json/wp/v2/posts?search={term}&per_page=20"
            response = requests.get(search_url, headers=headers)
            
            if response.status_code == 200:
                posts = response.json()
                for post in posts:
                    post_id = post.get('id')
                    title = post.get('title', {}).get('rendered', '')
                    link = post.get('link', '')
                    
                    # Verificar se é um dos posts antigos (não é o post 3100)
                    if post_id != 3100 and any(keyword in title.lower() for keyword in ['audio', 'gravações', 'texto para voz', 'voz']):
                        posts_encontrados.append({
                            'id': post_id,
                            'title': title,
                            'link': link
                        })
        
        # Remover duplicatas
        posts_unicos = []
        ids_processados = set()
        for post in posts_encontrados:
            if post['id'] not in ids_processados:
                posts_unicos.append(post)
                ids_processados.add(post['id'])
        
        print(f"\n📋 Posts encontrados para análise:")
        for post in posts_unicos:
            print(f"   - ID: {post['id']} | {post['title']}")
            print(f"     URL: {post['link']}")
        
        # Confirmar quais remover (verificar URLs)
        posts_para_deletar = []
        for post in posts_unicos:
            if any(url_antiga in post['link'] for url_antiga in posts_para_remover):
                posts_para_deletar.append(post)
        
        if not posts_para_deletar:
            print("\n⚠️ Nenhum post antigo encontrado com as URLs especificadas.")
            print("Verificando posts com títulos relacionados...")
            
            # Verificar por títulos similares
            for post in posts_unicos:
                title_lower = post['title'].lower()
                if ('áudio' in title_lower and 'gravações' in title_lower) or 'texto para voz' in title_lower:
                    posts_para_deletar.append(post)
        
        if posts_para_deletar:
            print(f"\n🗑️ Posts identificados para remoção:")
            for post in posts_para_deletar:
                print(f"   - ID: {post['id']} | {post['title']}")
            
            print(f"\n🚀 Removendo {len(posts_para_deletar)} post(s)...")
            
            for post in posts_para_deletar:
                delete_url = f"{config['server']}/wp-json/wp/v2/posts/{post['id']}?force=true"
                delete_response = requests.delete(delete_url, headers=headers)
                
                if delete_response.status_code == 200:
                    print(f"✅ Post {post['id']} removido: {post['title']}")
                else:
                    print(f"❌ Erro ao remover post {post['id']}: {delete_response.status_code}")
        else:
            print("\n⚠️ Nenhum post antigo identificado para remoção.")
            print("Os posts podem já ter sido removidos ou ter URLs diferentes.")
        
        print(f"\n📝 Post unificado mantido: ID 3100")
        print(f"🌐 URL: https://wordpress.sufficit.com.br/audio-e-gravacoes-de-sistema-sufficit-recursos-profissionais/")
        
    except Exception as e:
        print(f"❌ Erro: {e}")

if __name__ == "__main__":
    remover_posts_audio_antigos()
