#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Upload de imagens para biblioteca de mídia do WordPress
"""

import json
import requests
import base64
import os
from pathlib import Path

def verificar_imagem_existente(nome_arquivo, server, headers):
    """Verifica se uma imagem já existe na biblioteca de mídia"""
    try:
        # Busca por imagens com o mesmo nome
        url_search = f"{server}/wp-json/wp/v2/media"
        params = {
            'search': nome_arquivo.split('.')[0],  # Nome sem extensão
            'per_page': 100
        }
        
        response = requests.get(url_search, headers=headers, params=params)
        
        if response.status_code == 200:
            medias = response.json()
            
            # Procura por correspondência exata no nome do arquivo
            for media in medias:
                # Verifica se o nome do arquivo original corresponde
                media_filename = media.get('media_details', {}).get('file', '')
                if media_filename and nome_arquivo.lower() in media_filename.lower():
                    return media
                    
                # Também verifica o slug/título
                if nome_arquivo.split('.')[0].lower() in media['slug'].lower():
                    return media
        
        return None
        
    except Exception as e:
        print(f"   ⚠️ Erro ao verificar imagem existente: {e}")
        return None

def upload_imagem_wordpress(caminho_imagem, titulo="", descricao=""):
    """Faz upload de uma imagem para a biblioteca de mídia do WordPress"""
    try:
        # Carrega configuração
        config_path = Path('config/config.json')
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        server = config["server"]
        username = config["username"]
        password = config["password"]
        
        # Autenticação Basic
        credentials = f"{username}:{password}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        # Lê o arquivo de imagem
        with open(caminho_imagem, 'rb') as f:
            imagem_data = f.read()
        
        nome_arquivo = os.path.basename(str(caminho_imagem))
        mime_type = "image/png" if str(caminho_imagem).endswith('.png') else "image/jpeg"
        
        print(f"📤 Processando imagem: {nome_arquivo}")
        print(f"   Tamanho: {len(imagem_data)} bytes")
        
        # Headers para autenticação (usado tanto para verificação quanto upload)
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
        }
        
        # Verifica se a imagem já existe
        imagem_existente = verificar_imagem_existente(nome_arquivo, server, headers)
        
        if imagem_existente:
            print(f"   🔄 Imagem já existe (ID: {imagem_existente['id']})")
            print(f"   📝 Atualizando imagem existente...")
            
            # Atualiza a imagem existente
            url = f"{server}/wp-json/wp/v2/media/{imagem_existente['id']}"
            
            headers.update({
                'Content-Disposition': f'attachment; filename="{nome_arquivo}"',
                'Content-Type': mime_type
            })
            
            # Usa POST para substituir o arquivo
            response = requests.post(url, headers=headers, data=imagem_data)
            acao = "atualizada"
            
        else:
            print(f"   ➕ Nova imagem - fazendo upload...")
            
            # Faz upload de nova imagem
            headers.update({
                'Content-Disposition': f'attachment; filename="{nome_arquivo}"',
                'Content-Type': mime_type
            })
            
            # URL da API de mídia
            url = f"{server}/wp-json/wp/v2/media"
            
            # Faz o upload
            response = requests.post(url, headers=headers, data=imagem_data)
            acao = "enviada"
        
        if response.status_code in [200, 201]:
            media = response.json()
            print(f"   ✅ Imagem {acao} com sucesso!")
            print(f"   📍 ID: {media['id']}")
            print(f"   🔗 URL: {media['source_url']}")
            print(f"   📝 Título: {media['title']['rendered']}")
            
            # Salva resultado
            result_path = Path('temp/resultado_upload.txt')
            with open(result_path, 'w', encoding='utf-8') as f:
                f.write(f"SUCESSO! Imagem {acao} na biblioteca de mídia\n")
                f.write(f"Arquivo: {nome_arquivo}\n")
                f.write(f"Ação: {acao}\n")
                f.write(f"ID: {media['id']}\n")
                f.write(f"URL: {media['source_url']}\n")
                f.write(f"Data: {media['date']}\n")
            
            return {
                'id': media['id'],
                'url': media['source_url'],
                'title': media['title']['rendered'],
                'acao': acao
            }
        else:
            print(f"❌ Erro no upload: {response.status_code}")
            print(f"Resposta: {response.text}")
            return None
            
    except Exception as e:
        print(f"❌ Erro: {e}")
        return None

def upload_todas_imagens():
    """Faz upload de todas as imagens da pasta imagens/"""
    try:
        imagens_path = Path('imagens')
        
        if not imagens_path.exists():
            print("❌ Pasta 'imagens/' não encontrada")
            return
        
        # Lista todas as imagens
        extensoes = ['.png', '.jpg', '.jpeg', '.gif', '.webp']
        imagens = []
        
        for ext in extensoes:
            imagens.extend(list(imagens_path.glob(f'*{ext}')))
            imagens.extend(list(imagens_path.glob(f'*{ext.upper()}')))
        
        if not imagens:
            print("❌ Nenhuma imagem encontrada na pasta 'imagens/'")
            return
        
        print(f"📁 Encontradas {len(imagens)} imagens:")
        for img in imagens:
            print(f"   - {img.name}")
        
        print("\n🚀 Iniciando processamento...")
        uploads_success = []
        novas = 0
        atualizadas = 0
        
        for imagem in imagens:
            resultado = upload_imagem_wordpress(imagem)
            if resultado:
                uploads_success.append(resultado)
                if resultado['acao'] == 'enviada':
                    novas += 1
                else:
                    atualizadas += 1
                print()
        
        print(f"\n✅ {len(uploads_success)} imagens processadas com sucesso!")
        print(f"   📤 {novas} novas imagens enviadas")
        print(f"   🔄 {atualizadas} imagens atualizadas")
        
        # Salva resumo
        resumo_path = Path('temp/resumo_uploads.txt')
        with open(resumo_path, 'w', encoding='utf-8') as f:
            f.write(f"RESUMO DOS UPLOADS - {len(uploads_success)} imagens processadas\n")
            f.write(f"Novas: {novas} | Atualizadas: {atualizadas}\n")
            f.write("=" * 60 + "\n\n")
            for i, upload in enumerate(uploads_success, 1):
                f.write(f"{i}. ID: {upload['id']} ({upload['acao']})\n")
                f.write(f"   URL: {upload['url']}\n")
                f.write(f"   Título: {upload['title']}\n\n")
        
        return uploads_success
        
    except Exception as e:
        print(f"❌ Erro: {e}")
        return []

if __name__ == "__main__":
    upload_todas_imagens()
