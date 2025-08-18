#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para criar novo post sobre Canais de Atendimento Sufficit
"""

import json
import requests
import base64
from pathlib import Path

def criar_post_atendimento():
    """Cria um novo post sobre canais de atendimento no WordPress"""
    try:
        # Configuração
        config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # Conteúdo HTML
        html_path = Path("c:/Desenvolvimento/wordpress/postagens/canais-atendimento-sufficit.html")
        with open(html_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # URL da API para criar novo post
        url = f"{config['server']}/wp-json/wp/v2/posts"
        
        # Autenticação Basic
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
            'Content-Type': 'application/json; charset=utf-8',
            'User-Agent': 'WordPress-API-Client/1.0'
        }
        
        # Dados do novo post
        data = {
            "title": "Canais de Atendimento Sufficit - Suporte Completo e Especializado",
            "content": {
                "raw": content
            },
            "status": "publish",  # ou "draft" para rascunho
            "excerpt": {
                "raw": "Conheça todos os canais de atendimento da Sufficit. WhatsApp, telefone, HelpDesk online e contatos diretos por ramal. Suporte especializado para suas necessidades técnicas, comerciais e financeiras."
            }
        }
        
        print("🚀 Criando novo post no WordPress...")
        print(f"Título: {data['title']}")
        
        # Requisição para criar post
        response = requests.post(url, headers=headers, json=data)
        
        if response.status_code == 201:  # 201 = Created
            result = response.json()
            post_id = result.get('id')
            post_url = result.get('link')
            
            print(f"✅ POST criado com sucesso!")
            print(f"ID do Post: {post_id}")
            print(f"URL: {post_url}")
            
            # Renomeia o arquivo HTML para incluir o ID
            if post_id:
                new_filename = f"{post_id}-canais-atendimento-sufficit.html"
                new_path = html_path.parent / new_filename
                html_path.rename(new_path)
                print(f"📝 Arquivo renomeado para: {new_filename}")
            
            return post_id
            
        else:
            print(f"❌ Erro ao criar post: {response.status_code}")
            print(f"Resposta: {response.text}")
            return None
            
    except Exception as e:
        print(f"❌ Erro inesperado: {e}")
        return None

if __name__ == "__main__":
    criar_post_atendimento()
