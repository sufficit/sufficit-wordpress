#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Gerador de Imagens via API DALL-E da OpenAI
Versão Python - Script Principal
"""

import json
import requests
import sys
import argparse
from pathlib import Path
import unicodedata
import re

def normalizar_prompt(prompt):
    """Normaliza caracteres especiais do prompt para evitar problemas de codificação"""
    # Normaliza para remover acentos
    prompt_normalizado = unicodedata.normalize('NFD', prompt)
    # Remove caracteres especiais mantendo apenas letras, números, espaços e pontuação básica
    prompt_limpo = re.sub(r'[^\w\s.,!?-]', '', prompt_normalizado)
    return prompt_limpo

def carregar_config():
    """Carrega configurações da OpenAI"""
    config_path = Path('config/config-openai.json')
    try:
        with open(config_path, 'r', encoding='utf-8') as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"❌ Arquivo de configuração não encontrado: {config_path}")
        print("Certifique-se de que config/config-openai.json existe")
        sys.exit(1)
    except json.JSONDecodeError:
        print(f"❌ Erro ao decodificar JSON em: {config_path}")
        sys.exit(1)

def gerar_imagem(prompt, config):
    """Gera imagem usando API DALL-E 3"""
    
    # Normaliza o prompt
    prompt_normalizado = normalizar_prompt(prompt)
    print(f"Prompt normalizado: '{prompt_normalizado}'")
    
    # URL da API e headers
    api_url = "https://api.openai.com/v1/images/generations"
    headers = {
        "Authorization": f"Bearer {config['token']}",
        "OpenAI-Organization": config['organization'],
        "Content-Type": "application/json"
    }
    
    # Dados da requisição
    dados = {
        "model": "dall-e-3",
        "prompt": prompt_normalizado,
        "n": 1,
        "size": "1024x1024"
    }
    
    print(f"🎨 Enviando prompt para DALL-E 3...")
    
    try:
        response = requests.post(api_url, headers=headers, json=dados, timeout=60)
        
        if response.status_code == 200:
            resultado = response.json()
            url_imagem = resultado['data'][0]['url']
            
            print("✅ Imagem gerada com sucesso!")
            print(f"URL: {url_imagem}")
            
            # Salva resultado em arquivo
            with open('temp/resultado_imagem.txt', 'w', encoding='utf-8') as f:
                f.write(f"Imagem gerada com sucesso!\n")
                f.write(f"Prompt: {prompt}\n")
                f.write(f"Prompt normalizado: {prompt_normalizado}\n")
                f.write(f"URL: {url_imagem}\n")
                f.write(f"Modelo: dall-e-3\n")
                f.write(f"Tamanho: 1024x1024\n")
            
            return url_imagem
            
        else:
            print(f"❌ Erro na API: {response.status_code}")
            print(f"Resposta: {response.text}")
            
            # Salva erro em arquivo
            with open('temp/erro_imagem.txt', 'w', encoding='utf-8') as f:
                f.write(f"Erro na geração de imagem\n")
                f.write(f"Status: {response.status_code}\n")
                f.write(f"Prompt: {prompt}\n")
                f.write(f"Resposta: {response.text}\n")
            
            return None
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Erro na requisição: {e}")
        return None
    except Exception as e:
        print(f"❌ Erro inesperado: {e}")
        return None

def main():
    """Função principal"""
    parser = argparse.ArgumentParser(description='Gera imagens usando DALL-E 3')
    parser.add_argument('prompt', help='Descrição da imagem a ser gerada')
    parser.add_argument('--model', default='dall-e-3', help='Modelo a usar (padrão: dall-e-3)')
    parser.add_argument('--size', default='1024x1024', help='Tamanho da imagem (padrão: 1024x1024)')
    
    args = parser.parse_args()
    
    print("🎨 Gerador de Imagens DALL-E - Versão Python")
    print("=" * 50)
    
    # Carrega configurações
    config = carregar_config()
    print("✅ Configurações carregadas")
    
    # Gera imagem
    url = gerar_imagem(args.prompt, config)
    
    if url:
        print(f"\n🎉 Sucesso! Imagem disponível em:")
        print(f"📎 {url}")
    else:
        print(f"\n❌ Falha na geração da imagem")
        sys.exit(1)

if __name__ == "__main__":
    main()
