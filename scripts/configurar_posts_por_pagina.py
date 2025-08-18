#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para configurar a quantidade de posts exibidos por página no WordPress.
Altera o setting 'posts_per_page' através da WordPress REST API.
"""

import requests
import json
import sys
import os

def load_config():
    """Load WordPress connection configuration."""
    config_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'config', 'config.json')
    try:
        with open(config_path, 'r', encoding='utf-8') as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"❌ Arquivo de configuração não encontrado: {config_path}")
        sys.exit(1)
    except json.JSONDecodeError:
        print(f"❌ Erro ao decodificar o arquivo de configuração JSON")
        sys.exit(1)

def update_posts_per_page(config, posts_count):
    """Update posts per page setting in WordPress."""
    # WordPress REST API endpoint for settings
    url = f"{config['server']}/wp-json/wp/v2/settings"
    
    # Authentication
    auth = (config['username'], config['password'])
    
    # Headers
    headers = {
        'Content-Type': 'application/json',
    }
    
    # Data to update
    data = {
        'posts_per_page': posts_count
    }
    
    try:
        print(f"🔄 Atualizando configuração para {posts_count} posts por página...")
        
        # Make the request
        response = requests.post(url, auth=auth, headers=headers, json=data)
        
        if response.status_code == 200:
            result = response.json()
            current_posts_per_page = result.get('posts_per_page', 'N/A')
            print(f"✅ Configuração atualizada com sucesso!")
            print(f"📊 Posts por página: {current_posts_per_page}")
            return True
        else:
            print(f"❌ Erro ao atualizar configuração: {response.status_code}")
            print(f"Resposta: {response.text}")
            return False
            
    except requests.RequestException as e:
        print(f"❌ Erro de conexão: {e}")
        return False

def get_current_settings(config):
    """Get current WordPress settings."""
    url = f"{config['server']}/wp-json/wp/v2/settings"
    auth = (config['username'], config['password'])
    
    try:
        response = requests.get(url, auth=auth)
        if response.status_code == 200:
            result = response.json()
            return result
        else:
            print(f"❌ Erro ao obter configurações atuais: {response.status_code}")
            return None
    except requests.RequestException as e:
        print(f"❌ Erro de conexão: {e}")
        return None

def main():
    """Main function."""
    print("=== Configurador de Posts por Página - WordPress ===\n")
    
    # Load configuration
    config = load_config()
    print(f"🌐 Servidor: {config['server']}")
    print(f"👤 Usuário: {config['username']}\n")
    
    # Get current settings
    print("📋 Obtendo configurações atuais...")
    current_settings = get_current_settings(config)
    
    if current_settings:
        current_posts_per_page = current_settings.get('posts_per_page', 'N/A')
        print(f"📊 Posts por página atual: {current_posts_per_page}\n")
    
    # Ask for new value or use default (3)
    if len(sys.argv) > 1:
        try:
            posts_count = int(sys.argv[1])
        except ValueError:
            print("❌ Valor inválido. Use um número inteiro.")
            sys.exit(1)
    else:
        posts_count = 3  # Default value
    
    # Validate input
    if posts_count < 1 or posts_count > 50:
        print("❌ O número de posts deve estar entre 1 e 50.")
        sys.exit(1)
    
    # Update setting
    success = update_posts_per_page(config, posts_count)
    
    if success:
        print(f"\n🎉 Configuração alterada com sucesso!")
        print(f"📱 Agora a página principal mostrará {posts_count} posts.")
        print("💡 Dica: Limpe o cache do site se estiver usando algum plugin de cache.")
    else:
        print(f"\n❌ Falha ao alterar a configuração.")
        sys.exit(1)

if __name__ == "__main__":
    main()
