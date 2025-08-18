import requests
import json
import os
import sys
import argparse
from datetime import datetime

def load_config():
    """Carregar configuração do WordPress"""
    config_path = os.path.join('..', '..', '..', 'config', 'config.json')
    with open(config_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def load_structure_file(filename):
    """Carregar estrutura de um arquivo JSON"""
    if not os.path.exists(filename):
        print(f'❌ Arquivo não encontrado: {filename}')
        return None
    
    with open(filename, 'r', encoding='utf-8') as f:
        return json.load(f)

def update_template(template_id, structure_data, config, description=""):
    """Atualizar template no WordPress"""
    update_data = {
        'meta': {
            '_elementor_data': json.dumps(structure_data)
        }
    }
    
    url = f'{config["server"]}/wp-json/wp/v2/elementor_library/{template_id}'
    auth = (config['username'], config['password'])
    
    print(f'🔄 Atualizando template {template_id}...')
    if description:
        print(f'📝 Descrição: {description}')
    
    response = requests.post(url, auth=auth, json=update_data)
    
    if response.status_code == 200:
        print(f'✅ Template {template_id} atualizado com sucesso!')
        return True
    else:
        print(f'❌ Erro ao atualizar template {template_id}: {response.status_code}')
        print(f'Resposta: {response.text}')
        return False

def create_backup(structure_data, backup_name=""):
    """Criar backup da estrutura aplicada"""
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    
    if backup_name:
        filename = f'backup_{backup_name}_{timestamp}.json'
    else:
        filename = f'backup_{timestamp}.json'
    
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(structure_data, f, indent=2, ensure_ascii=False)
    
    print(f'💾 Backup salvo: {filename}')
    return filename

def main():
    parser = argparse.ArgumentParser(description='Script genérico de atualização para templates Elementor')
    
    # Argumentos obrigatórios
    parser.add_argument('template_id', type=int, help='ID do template (ex: 3214)')
    parser.add_argument('filename', type=str, help='Nome do arquivo JSON (ex: meu-template.json, elementor-structure-novo.json)')
    
    # Argumentos opcionais
    parser.add_argument('-d', '--description', type=str, default='', 
                       help='Descrição da alteração')
    parser.add_argument('-b', '--backup', type=str, default='', 
                       help='Nome personalizado para o backup')
    parser.add_argument('--no-backup', action='store_true', 
                       help='Não criar backup automático')
    
    args = parser.parse_args()
    
    print('=== SCRIPT GENÉRICO DE ATUALIZAÇÃO TEMPLATE ELEMENTOR ===')
    print(f'🎯 Template ID: {args.template_id}')
    print(f'📁 Arquivo: {args.filename}')
    
    # Carregar configuração
    try:
        config = load_config()
        print(f'🌐 Servidor: {config["server"]}')
    except Exception as e:
        print(f'❌ Erro ao carregar config: {e}')
        return
    
    # Determinar arquivo a usar
    if args.filename.endswith('.json'):
        filename = args.filename
        print(f'📋 Usando arquivo: {filename}')
    else:
        # Se não termina com .json, adiciona automaticamente
        filename = f'{args.filename}.json' if not args.filename.startswith('elementor-') else f'{args.filename}.json'
        print(f'📋 Tentando arquivo: {filename}')
    
    # Carregar estrutura
    structure_data = load_structure_file(filename)
    if not structure_data:
        print('\n❌ Arquivo não encontrado!')
        print('📋 Arquivos JSON disponíveis:')
        for file in os.listdir('.'):
            if file.endswith('.json') and file.startswith('elementor-'):
                print(f'  • {file}')
        return
    
    # Criar backup antes da atualização (se solicitado)
    if not args.no_backup:
        backup_name = args.backup if args.backup else args.filename
        create_backup(structure_data, backup_name)
    
    # Determinar descrição automática se não fornecida
    if not args.description:
        # Gera descrição baseada no nome do arquivo
        base_name = filename.replace('elementor-structure-', '').replace('.json', '')
        args.description = f'Aplicando template: {base_name}'
    
    # Atualizar template
    success = update_template(args.template_id, structure_data, config, args.description)
    
    if success:
        print('\n🎉 ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!')
        print(f'📋 Alteração: {args.description}')
    else:
        print('\n💥 FALHA NA ATUALIZAÇÃO!')

if __name__ == '__main__':
    main()
