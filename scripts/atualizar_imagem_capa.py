#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script genérico para atualizar imagem de capa (featured image) de posts WordPress
Recebe ID do post e URL/caminho da imagem como parâmetros

FUNCIONALIDADES:
- Redimensionamento automático para padrão de capa (1152x866)
- Processamento em memória (não altera arquivo original)
- Suporte a PNG, JPEG, WebP
- Fallback para imagem original se PIL não disponível

IMPORTANTE: 
- Se o usuário pedir para atualizar imagem de capa, sempre solicitar:
  1. ID do post (número)
  2. URL da imagem ou caminho do arquivo local
- Não assumir valores padrão - sempre confirmar ambos os parâmetros

DEPENDÊNCIAS:
- requests, pathlib (padrão)
- Pillow (opcional - para redimensionamento): pip install Pillow
"""

import json
import requests
import base64
import sys
from pathlib import Path
import argparse
import io
try:
    from PIL import Image
    PIL_DISPONIVEL = True
except ImportError:
    PIL_DISPONIVEL = False
    print("⚠️ PIL/Pillow não disponível - redimensionamento automático desabilitado")
    print("   Para habilitar: pip install Pillow")

def atualizar_imagem_capa(post_id, imagem_url):
    """
    Atualiza a imagem de capa de um post específico
    
    Args:
        post_id (int): ID do post no WordPress
        imagem_url (str): URL da imagem ou caminho do arquivo local
    
    Returns:
        bool: True se sucesso, False se erro
    """
    try:
        # Carrega configuração
        config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # Prepara autenticação
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f'Basic {encoded_credentials}',
        }
        
        print(f"🔄 ATUALIZANDO IMAGEM DE CAPA - POST {post_id}")
        print("=" * 60)
        
        # 1. Verifica se o post existe
        post_url = f"{config['server']}/wp-json/wp/v2/posts/{post_id}"
        post_response = requests.get(post_url, headers={**headers, 'Content-Type': 'application/json'})
        
        if post_response.status_code != 200:
            print(f"❌ Post {post_id} não encontrado ou inacessível")
            return False
        
        post_data = post_response.json()
        print(f"📝 Post encontrado: {post_data['title']['rendered']}")
        print(f"🔗 URL do post: {post_data['link']}")
        
        # 2. Verifica imagem de capa atual
        current_featured = post_data.get('featured_media', 0)
        if current_featured and current_featured != 0:
            print(f"⚠️ Post já tem imagem de capa (Media ID: {current_featured})")
            print(f"   Será substituída pela nova imagem")
        else:
            print(f"ℹ️ Post não tem imagem de capa atual")
        
        # 3. Baixa/prepara a imagem
        print(f"\n� PREPARANDO IMAGEM")
        print(f"Fonte: {imagem_url}")
        
        # Verifica se é URL ou arquivo local
        if imagem_url.startswith(('http://', 'https://')):
            print(f"🌐 Baixando imagem da URL...")
            
            # Download da imagem
            img_response = requests.get(imagem_url, timeout=30)
            if img_response.status_code != 200:
                print(f"❌ Erro ao baixar imagem: {img_response.status_code}")
                return False
            
            image_data = img_response.content
            
            # Extrai nome do arquivo da URL
            filename = imagem_url.split('/')[-1].split('?')[0]
            
            # Define extensão se não tiver
            if '.' not in filename or len(filename.split('.')[-1]) > 4:
                filename += '.png'
                
        else:
            # Arquivo local
            image_path = Path(imagem_url)
            
            # Se não for caminho absoluto, assume que está em imagens/
            if not image_path.is_absolute():
                image_path = Path(f"c:/Desenvolvimento/wordpress/imagens/{imagem_url}")
            
            print(f"📁 Carregando arquivo local: {image_path}")
            
            if not image_path.exists():
                print(f"❌ Arquivo não encontrado: {image_path}")
                return False
            
            with open(image_path, 'rb') as f:
                image_data = f.read()
            
            filename = image_path.name

        # 4. Redimensionamento automático para padrão de capa (1152x866)
        if PIL_DISPONIVEL:
            print(f"\n🔧 REDIMENSIONANDO PARA PADRÃO DE CAPA")
            try:
                # Carrega imagem em memória
                img = Image.open(io.BytesIO(image_data))
                original_size = img.size
                print(f"   Tamanho original: {original_size[0]}x{original_size[1]}")
                
                # Tamanho padrão para capas
                TARGET_WIDTH = 1152
                TARGET_HEIGHT = 866
                
                # Verifica se precisa redimensionar
                if img.size != (TARGET_WIDTH, TARGET_HEIGHT):
                    print(f"   Redimensionando para: {TARGET_WIDTH}x{TARGET_HEIGHT}")
                    
                    # Redimensiona mantendo qualidade
                    img_resized = img.resize((TARGET_WIDTH, TARGET_HEIGHT), Image.Resampling.LANCZOS)
                    
                    # Converte para RGB se necessário (para JPEG)
                    if img_resized.mode in ('RGBA', 'P'):
                        # Mantém PNG para transparência
                        if filename.lower().endswith('.png'):
                            pass  # Mantém RGBA para PNG
                        else:
                            # Converte para RGB para outros formatos
                            img_resized = img_resized.convert('RGB')
                    
                    # Salva em memória
                    output = io.BytesIO()
                    
                    # Determina formato de saída
                    if filename.lower().endswith('.png'):
                        img_resized.save(output, format='PNG', optimize=True)
                        content_type = 'image/png'
                    elif filename.lower().endswith(('.jpg', '.jpeg')):
                        img_resized.save(output, format='JPEG', optimize=True, quality=90)
                        content_type = 'image/jpeg'
                    elif filename.lower().endswith('.webp'):
                        img_resized.save(output, format='WEBP', optimize=True, quality=90)
                        content_type = 'image/webp'
                    else:
                        # Padrão PNG
                        img_resized.save(output, format='PNG', optimize=True)
                        content_type = 'image/png'
                        if not filename.lower().endswith('.png'):
                            filename = filename.rsplit('.', 1)[0] + '.png'
                    
                    image_data = output.getvalue()
                    output.close()
                    
                    print(f"   ✅ Redimensionamento concluído!")
                    print(f"   Novo tamanho: {TARGET_WIDTH}x{TARGET_HEIGHT}")
                    print(f"   Tamanho do arquivo: {len(image_data):,} bytes")
                else:
                    print(f"   ✅ Imagem já está no tamanho correto!")
                    
                    # Define content_type para imagem original
                    if filename.lower().endswith('.png'):
                        content_type = 'image/png'
                    elif filename.lower().endswith(('.jpg', '.jpeg')):
                        content_type = 'image/jpeg'
                    elif filename.lower().endswith('.webp'):
                        content_type = 'image/webp'
                    else:
                        content_type = 'image/png'
                
            except Exception as e:
                print(f"   ⚠️ Erro no redimensionamento: {e}")
                print(f"   Prosseguindo com imagem original...")
                # Define content_type para imagem original
                if filename.lower().endswith('.png'):
                    content_type = 'image/png'
                elif filename.lower().endswith(('.jpg', '.jpeg')):
                    content_type = 'image/jpeg'
                elif filename.lower().endswith('.webp'):
                    content_type = 'image/webp'
                else:
                    content_type = 'image/png'
        else:
            print(f"\n⚠️ Redimensionamento desabilitado - PIL não disponível")
            print(f"   Para habilitar: pip install Pillow")
            print(f"   Prosseguindo com imagem original...")
            # Define content_type para imagem original
            if filename.lower().endswith('.png'):
                content_type = 'image/png'
            elif filename.lower().endswith(('.jpg', '.jpeg')):
                content_type = 'image/jpeg'
            elif filename.lower().endswith('.webp'):
                content_type = 'image/webp'
            else:
                content_type = 'image/png'
        
        # 5. Upload para WordPress
        print(f"\n📤 FAZENDO UPLOAD PARA WORDPRESS")
        upload_url = f"{config['server']}/wp-json/wp/v2/media"
        
        # content_type já foi definido na seção de redimensionamento
        files = {
            'file': (filename, image_data, content_type)
        }
        
        print(f"   Arquivo: {filename}")
        print(f"   Tamanho: {len(image_data):,} bytes")
        print(f"   Tipo: {content_type}")
        
        upload_response = requests.post(upload_url, headers=headers, files=files)
        
        if upload_response.status_code != 201:
            print(f"❌ Erro no upload: {upload_response.status_code}")
            print(upload_response.text)
            return False
        
        media_data = upload_response.json()
        media_id = media_data['id']
        
        print(f"✅ Upload concluído!")
        print(f"   Media ID: {media_id}")
        print(f"   URL: {media_data['source_url']}")
        print(f"   Dimensões: {media_data.get('media_details', {}).get('width', 'N/A')}x{media_data.get('media_details', {}).get('height', 'N/A')}")
        
        # 6. Define como featured image do post
        print(f"\n🖼️ DEFININDO COMO IMAGEM DE CAPA")
        
        update_data = {
            "featured_media": media_id
        }
        
        update_headers = {
            **headers,
            'Content-Type': 'application/json'
        }
        
        update_response = requests.put(post_url, headers=update_headers, json=update_data)
        
        if update_response.status_code == 200:
            updated_post = update_response.json()
            print(f"✅ IMAGEM DE CAPA ATUALIZADA COM SUCESSO!")
            print(f"   Post: {updated_post['title']['rendered']}")
            print(f"   Nova Featured Media ID: {updated_post['featured_media']}")
            print(f"   URL do Post: {updated_post['link']}")
            
            # Remove imagem de capa anterior se houver
            if current_featured and current_featured != 0 and current_featured != media_id:
                print(f"\nℹ️ Imagem anterior (ID: {current_featured}) permanece na biblioteca de mídia")
            
            return True
        else:
            print(f"❌ Erro ao definir featured image: {update_response.status_code}")
            print(update_response.text)
            return False
            
    except Exception as e:
        print(f'❌ Erro: {e}')
        return False

def verificar_capa_atual(post_id):
    """Verifica qual é a imagem de capa atual do post"""
    try:
        config_path = Path("c:/Desenvolvimento/wordpress/config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f'Basic {encoded_credentials}',
            'Content-Type': 'application/json'
        }
        
        print(f"🔍 VERIFICANDO CAPA ATUAL - POST {post_id}")
        print("=" * 50)
        
        post_url = f"{config['server']}/wp-json/wp/v2/posts/{post_id}"
        response = requests.get(post_url, headers=headers)
        
        if response.status_code == 200:
            post_data = response.json()
            print(f"📝 Post: {post_data['title']['rendered']}")
            
            featured_media = post_data.get('featured_media', 0)
            if featured_media and featured_media != 0:
                print(f"✅ Tem imagem de capa (Media ID: {featured_media})")
                
                # Busca detalhes
                media_url = f"{config['server']}/wp-json/wp/v2/media/{featured_media}"
                media_response = requests.get(media_url, headers=headers)
                
                if media_response.status_code == 200:
                    media_data = media_response.json()
                    print(f"📷 URL: {media_data['source_url']}")
                    print(f"📏 Dimensões: {media_data.get('media_details', {}).get('width', 'N/A')}x{media_data.get('media_details', {}).get('height', 'N/A')}")
            else:
                print(f"❌ Não tem imagem de capa configurada")
        else:
            print(f"❌ Post não encontrado: {response.status_code}")
            
    except Exception as e:
        print(f'❌ Erro: {e}')

def main():
    """Função principal com interface de linha de comando"""
    parser = argparse.ArgumentParser(
        description='Atualiza imagem de capa de posts WordPress',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
EXEMPLOS DE USO:

  # Atualizar capa com URL (redimensiona automaticamente para 1152x866)
  python scripts/atualizar_imagem_capa.py 2988 "https://exemplo.com/imagem.jpg"
  
  # Atualizar capa com arquivo local (redimensiona automaticamente)
  python scripts/atualizar_imagem_capa.py 3007 "capa-nova.png"
  
  # Verificar capa atual
  python scripts/atualizar_imagem_capa.py --verificar 2988
  
  # Usar caminho completo
  python scripts/atualizar_imagem_capa.py 3100 "c:/pasta/imagem.webp"

REDIMENSIONAMENTO AUTOMÁTICO:
  - Todas as imagens são redimensionadas para 1152x866 (padrão de capa)
  - Processamento em memória - não altera arquivo original
  - Requer Pillow: pip install Pillow
  - Se Pillow não estiver disponível, usa imagem original
        """
    )
    
    parser.add_argument('post_id', type=int, nargs='?', help='ID do post no WordPress')
    parser.add_argument('imagem_url', nargs='?', help='URL da imagem ou caminho do arquivo local')
    parser.add_argument('--verificar', type=int, metavar='POST_ID', help='Apenas verifica a capa atual do post')
    
    args = parser.parse_args()
    
    # Modo verificação
    if args.verificar:
        verificar_capa_atual(args.verificar)
        return
    
    # Validação de argumentos
    if not args.post_id or not args.imagem_url:
        parser.print_help()
        print(f"\n❌ Erro: Informe o ID do post e a URL/caminho da imagem")
        return
    
    # Execução principal
    sucesso = atualizar_imagem_capa(args.post_id, args.imagem_url)
    
    if sucesso:
        print(f"\n🎉 PROCESSO CONCLUÍDO COM SUCESSO!")
    else:
        print(f"\n❌ PROCESSO FALHOU!")

if __name__ == "__main__":
    main()
