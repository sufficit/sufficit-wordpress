#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import requests
import sys
import base64
from pathlib import Path

def atualizar_post_wordpress():
    try:
        # Carrega configuração
        config_path = Path('config/config.json')
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        print("✅ Configuração carregada")
        print(f"Server: {config['server']}")
        print(f"Usuário: {config['username']}")
        
        # Carrega conteúdo HTML
        html_path = Path('postagens/como-funciona-telefone-ip.html')
        with open(html_path, 'r', encoding='utf-8') as f:
            html_content = f.read()
        
        print("✅ Conteúdo HTML carregado")
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
        api_url = f"{config['server']}/wp-json/wp/v2/posts/1515"
        
        print("🔄 Enviando requisição para WordPress...")
        print(f"URL: {api_url}")
        
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
            with open('temp/resultado_sucesso.txt', 'w', encoding='utf-8') as f:
                f.write(f"SUCESSO! Post atualizado!\n")
                f.write(f"Título: {result['title']['rendered']}\n")
                f.write(f"Status: {result['status']}\n")
                f.write(f"Modificado em: {result['modified']}\n")
                f.write(f"URL: {result['link']}\n")
            
        else:
            print(f"❌ Erro na requisição: {response.status_code}")
            print(f"Resposta: {response.text}")
            
            # Salva erro em arquivo
            with open('temp/resultado_erro.txt', 'w', encoding='utf-8') as f:
                f.write(f"ERRO: {response.status_code}\n")
                f.write(f"Resposta: {response.text}\n")
                
    except FileNotFoundError as e:
        print(f"❌ Arquivo não encontrado: {e}")
    except json.JSONDecodeError as e:
        print(f"❌ Erro ao decodificar JSON: {e}")
    except requests.exceptions.RequestException as e:
        print(f"❌ Erro na requisição: {e}")
    except Exception as e:
        print(f"❌ Erro inesperado: {e}")

if __name__ == "__main__":
    atualizar_post_wordpress()
