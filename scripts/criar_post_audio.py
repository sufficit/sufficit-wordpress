#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para criar post sobre áudio e gravações de sistema no WordPress
"""

import json
import requests
import base64
from pathlib import Path

def criar_post_audio():
    """Cria um novo post sobre áudio e gravações de sistema"""
    try:
        # Configuração
        config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # Lê o conteúdo HTML
        html_path = Path("c:/Desenvolvimento/wordpress/postagens/3100-audio-gravacoes-sistema-sufficit.html")
        with open(html_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # URL da API
        url = f"{config['server']}/wp-json/wp/v2/posts"
        
        # Autenticação Basic
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
            'Content-Type': 'application/json; charset=utf-8',
            'User-Agent': 'WordPress-API-Client/1.0'
        }
        
        data = {
            "title": "Áudio e Gravações de Sistema Sufficit: Recursos Profissionais",
            "content": {
                "raw": content
            },
            "status": "publish",
            "categories": [18],  # Categoria padrão
            "featured_media": 0
        }
        
        # Requisição
        response = requests.post(url, headers=headers, json=data)
        
        if response.status_code == 201:
            result = response.json()
            post_id = result.get('id', 'N/A')
            url_link = result.get('link', 'N/A')
            
            print(f"✅ POST sobre áudio criado com sucesso!")
            print(f"ID: {post_id}")
            print(f"URL: {url_link}")
            
            # Renomear arquivo para incluir ID
            if post_id != 'N/A':
                new_filename = f"{post_id}-audio-gravacoes-sistema-sufficit.html"
                new_path = Path(f"c:/Desenvolvimento/wordpress/postagens/{new_filename}")
                html_path.rename(new_path)
                print(f"📁 Arquivo renomeado para: {new_filename}")
            
            return post_id
            
        else:
            print(f"❌ Erro: {response.status_code}")
            print(f"Resposta: {response.text}")
            return None
            
    except Exception as e:
        print(f"❌ Erro: {e}")
        return None

if __name__ == "__main__":
    criar_post_audio()
