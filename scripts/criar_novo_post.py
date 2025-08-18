#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para criar um novo post no WordPress
"""

import json
import requests
import base64
from pathlib import Path

def criar_novo_post(arquivo_html, titulo):
    try:
        # Carrega configuração
        config_path = Path('config/config.json')
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        print("✅ Configuração carregada")
        print(f"Server: {config['server']}")
        print(f"Usuário: {config['username']}")
        
        # Carrega conteúdo HTML
        html_path = Path(arquivo_html)
        if not html_path.exists():
            print(f"❌ Arquivo não encontrado: {arquivo_html}")
            return False
            
        with open(html_path, 'r', encoding='utf-8') as f:
            conteudo = f.read()
        
        print(f"✅ Conteúdo HTML carregado")
        print(f"Arquivo: {arquivo_html}")
        print(f"Tamanho do conteúdo: {len(conteudo)} caracteres")
        
        # Prepara dados do post
        data = {
            'title': titulo,
            'content': conteudo,
            'status': 'publish',
            'excerpt': 'Guia completo sobre o Painel do Cliente Sufficit - sua central de comando empresarial para gerenciar toda comunicação de forma intuitiva e eficiente.'
        }
        
        # Configuração de autenticação
        credentials = f"{config['username']}:{config['password']}"
        token = base64.b64encode(credentials.encode()).decode('utf-8')
        
        headers = {
            'Authorization': f'Basic {token}',
            'Content-Type': 'application/json'
        }
        
        # URL para criar novo post
        url = f"{config['server']}/wp-json/wp/v2/posts"
        
        print("🔄 Enviando requisição para criar novo post...")
        print(f"URL: {url}")
        
        # Enviar requisição
        response = requests.post(url, headers=headers, json=data)
        
        print(f"Status da resposta: {response.status_code}")
        
        if response.status_code == 201:  # 201 = Created
            response_data = response.json()
            print("🎉 SUCESSO! Novo post criado!")
            print(f"ID do novo post: {response_data['id']}")
            print(f"Título: {response_data['title']['rendered']}")
            print(f"Status: {response_data['status']}")
            print(f"Data de criação: {response_data['date']}")
            print(f"URL: {response_data['link']}")
            return response_data['id']
        else:
            print(f"❌ Erro na criação: {response.status_code}")
            print(f"Resposta: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Erro: {e}")
        return False

if __name__ == "__main__":
    import sys
    if len(sys.argv) < 3:
        print("Uso: python script.py <arquivo_html> <titulo_do_post>")
        sys.exit(1)
    
    arquivo = sys.argv[1]
    titulo = sys.argv[2]
    
    print(f"🚀 Criando novo post: {titulo}")
    print(f"📄 Arquivo: {arquivo}")
    print("--------------------------------------------------")
    
    post_id = criar_novo_post(arquivo, titulo)
    
    if post_id:
        print("--------------------------------------------------")
        print("✅ Operação concluída com sucesso!")
        print(f"Novo post ID: {post_id}")
    else:
        print("--------------------------------------------------")
        print("❌ Operação falhou!")
