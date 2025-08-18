#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script genérico para atualizar posts no WordPress via REST API
Uso: python atualizar_post_generico.py <post_id> <arquivo_html>
Exemplo: python atualizar_post_generico.py 1515 postagens/como-funciona-telefone-ip.html
"""

import json
import requests
import sys
import base64
from pathlib import Path

def atualizar_post_wordpress(post_id, arquivo_html):
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
            html_content = f.read()
        
        print("✅ Conteúdo HTML carregado")
        print(f"Arquivo: {arquivo_html}")
        print(f"Tamanho do conteúdo: {len(html_content)} caracteres")
        
        # Headers para autenticação Basic
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
            'Content-Type': 'application/json; charset=utf-8',
            'User-Agent': 'WordPress-API-Client/1.0'
        }
        
        # Dados para atualização (estrutura correta baseada na documentação)
        update_data = {
            'content': {
                'raw': html_content
            }
        }
        
        # URL da API
        api_url = f"{config['server']}/wp-json/wp/v2/posts/{post_id}"
        
        print("🔄 Enviando requisição para WordPress...")
        print(f"URL: {api_url}")
        print(f"Post ID: {post_id}")
        
        # Faz a requisição PUT
        response = requests.put(
            api_url,
            headers=headers,
            json=update_data,
            timeout=30
        )
        
        print(f"Status da resposta: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print("🎉 SUCESSO! Post atualizado!")
            print(f"Título: {result['title']['rendered']}")
            print(f"Status: {result['status']}")
            print(f"Modificado em: {result['modified']}")
            print(f"URL: {result['link']}")
            
            # Salva resultado em arquivo
            timestamp = result['modified'].replace(':', '-').replace('T', '_')
            log_file = f"temp/post_{post_id}_sucesso_{timestamp}.txt"
            with open(log_file, 'w', encoding='utf-8') as f:
                f.write(f"SUCESSO! Post {post_id} atualizado!\n")
                f.write(f"Arquivo: {arquivo_html}\n")
                f.write(f"Título: {result['title']['rendered']}\n")
                f.write(f"Status: {result['status']}\n")
                f.write(f"Modificado em: {result['modified']}\n")
                f.write(f"URL: {result['link']}\n")
            
            return True
            
        else:
            print(f"❌ Erro na requisição: {response.status_code}")
            print(f"Resposta: {response.text}")
            
            # Salva erro em arquivo
            log_file = f"temp/post_{post_id}_erro.txt"
            with open(log_file, 'w', encoding='utf-8') as f:
                f.write(f"ERRO: {response.status_code}\n")
                f.write(f"Post ID: {post_id}\n")
                f.write(f"Arquivo: {arquivo_html}\n")
                f.write(f"Resposta: {response.text}\n")
            
            return False
                
    except FileNotFoundError as e:
        print(f"❌ Arquivo não encontrado: {e}")
        return False
    except json.JSONDecodeError as e:
        print(f"❌ Erro ao decodificar JSON: {e}")
        return False
    except requests.exceptions.RequestException as e:
        print(f"❌ Erro na requisição: {e}")
        return False
    except Exception as e:
        print(f"❌ Erro inesperado: {e}")
        return False

def main():
    # Verifica argumentos
    if len(sys.argv) != 3:
        print("❌ Uso incorreto!")
        print("📋 Uso: python atualizar_post_generico.py <post_id> <arquivo_html>")
        print("📝 Exemplo: python atualizar_post_generico.py 1515 postagens/como-funciona-telefone-ip.html")
        sys.exit(1)
    
    post_id = sys.argv[1]
    arquivo_html = sys.argv[2]
    
    # Valida se post_id é número
    try:
        int(post_id)
    except ValueError:
        print(f"❌ Post ID deve ser um número: {post_id}")
        sys.exit(1)
    
    print(f"🚀 Iniciando atualização do post {post_id}")
    print(f"📄 Arquivo: {arquivo_html}")
    print("-" * 50)
    
    sucesso = atualizar_post_wordpress(post_id, arquivo_html)
    
    if sucesso:
        print("-" * 50)
        print("✅ Operação concluída com sucesso!")
    else:
        print("-" * 50)
        print("❌ Operação falhou!")
        sys.exit(1)

if __name__ == "__main__":
    main()
