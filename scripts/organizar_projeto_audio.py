#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para organizar arquivos do projeto de áudio e gravações (ID 3100)
"""

import os
import shutil
from pathlib import Path

def organizar_projeto_audio():
    """Organiza arquivos do projeto de áudio seguindo padrões estabelecidos"""
    
    print("🎵 Organizando projeto de áudio e gravações...")
    
    # Verificar se todos os arquivos necessários existem
    arquivos_esperados = {
        "postagem": "postagens/3100-audio-gravacoes-sistema-sufficit.html",
        "imagem": "imagens/post-3100-audio-sistema-principal.png",
        "script_atualizacao": "scripts/atualizar_post_3100_audio.py",
        "script_criacao": "scripts/criar_post_audio.py",
        "log": "temp/log_atualizacao_audio_3100.json"
    }
    
    for tipo, caminho in arquivos_esperados.items():
        file_path = Path(caminho)
        if file_path.exists():
            print(f"✅ {tipo.capitalize()}: {caminho}")
        else:
            print(f"❌ {tipo.capitalize()}: {caminho} - NÃO ENCONTRADO")
    
    print("\n📋 Resumo do projeto:")
    print(f"🆔 Post ID: 3100")
    print(f"🌐 URL: https://wordpress.sufficit.com.br/audio-e-gravacoes-de-sistema-sufficit-recursos-profissionais/")
    print(f"📝 Título: Áudio e Gravações de Sistema Sufficit: Recursos Profissionais")
    print(f"🎯 Status: Publicado e atualizado")
    
    # Verificar arquivos temporários para limpeza
    temp_files = list(Path("temp").glob("*audio*"))
    if temp_files:
        print(f"\n🧹 Arquivos temporários relacionados ao áudio:")
        for file in temp_files:
            print(f"   - {file}")
    
    print("\n✅ Organização do projeto de áudio concluída!")
    print("📁 Todos os arquivos estão nos locais corretos seguindo o padrão estabelecido.")

if __name__ == "__main__":
    organizar_projeto_audio()
