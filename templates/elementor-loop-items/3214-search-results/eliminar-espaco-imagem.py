import requests
import json
import os

# Carregar config
config_path = os.path.join('..', '..', '..', 'config', 'config.json')
with open(config_path, 'r', encoding='utf-8') as f:
    config = json.load(f)

print('=== ELIMINAR ESPAÇO EMBAIXO DA IMAGEM TEMPLATE 3214 ===')
print(f'🌐 Servidor: {config["server"]}')

# Carregar estrutura atual
with open('elementor-structure-no-padding.json', 'r', encoding='utf-8') as f:
    current_structure = json.load(f)

# CORRIGIR ESPAÇO EMBAIXO DA IMAGEM

# 1. Ajustar o container principal para altura mínima
current_structure[0]["settings"]["min_height"] = {
    "unit": "px",
    "size": 90,
    "sizes": []
}

# 2. CSS mais específico para a imagem - eliminar espaço embaixo
current_structure[0]["elements"][0]["settings"]["custom_css"] = """selector {
    max-width: 120px !important;
    width: 15% !important;
    height: 90px !important;
    min-height: 90px !important;
    max-height: 90px !important;
    flex-shrink: 0;
    align-self: flex-start !important;
    margin: 0 8px 0 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
}

selector img {
    width: 100% !important;
    height: 90px !important;
    min-height: 90px !important;
    max-height: 90px !important;
    max-width: 120px !important;
    object-fit: cover;
    display: block;
    margin: 0 !important;
    padding: 0 !important;
    vertical-align: top !important;
    border: none !important;
    outline: none !important;
    line-height: 0 !important;
}

selector .elementor-widget-container {
    padding: 0 !important;
    margin: 0 !important;
    height: 90px !important;
    overflow: hidden !important;
}

selector .elementor-image {
    height: 90px !important;
    overflow: hidden !important;
}

@media (max-width: 768px) {
    selector {
        width: 30% !important;
        max-width: 100px !important;
        height: 75px !important;
        min-height: 75px !important;
        max-height: 75px !important;
    }
    selector img {
        height: 75px !important;
        min-height: 75px !important;
        max-height: 75px !important;
        max-width: 100px !important;
    }
    selector .elementor-widget-container {
        height: 75px !important;
    }
}"""

# 3. Ajustar container de texto para alinhar com altura da imagem
current_structure[0]["elements"][1]["settings"]["min_height"] = {
    "unit": "px",
    "size": 90,
    "sizes": []
}

# 4. CSS para container de texto - altura controlada
current_structure[0]["elements"][1]["settings"]["custom_css"] = """selector {
    padding: 0 !important;
    margin: 0 !important;
    align-self: flex-start !important;
    justify-content: flex-start !important;
    align-items: flex-start !important;
    align-content: flex-start !important;
    min-height: 90px !important;
    height: auto !important;
}

selector > * {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

@media (max-width: 768px) {
    selector {
        min-height: 75px !important;
    }
}"""

# 5. Reduzir ainda mais o espaçamento entre itens
current_structure[0]["settings"]["margin"] = {
    "unit": "px",
    "top": "0",
    "right": "0",
    "bottom": "0",
    "left": "0",
    "isLinked": True
}

# 6. Divisor ainda mais discreto
current_structure[1]["settings"]["_margin"] = {
    "unit": "px",
    "top": "0",
    "right": "0",
    "bottom": "0",
    "left": "0",
    "isLinked": True
}

current_structure[1]["elements"][0]["settings"]["gap"] = {
    "unit": "px",
    "size": 1,
    "sizes": []
}

print('🔧 Correções para espaço embaixo da imagem:')
print('  📏 Altura fixa da imagem: 90px (desktop) / 75px (mobile)')
print('  📏 Min/max height forçados')
print('  📏 Overflow: hidden para cortar qualquer espaço extra')
print('  📏 Line-height: 0 na imagem')
print('  📏 Container de texto com altura mínima correspondente')
print('  📏 Margins zeradas no container principal')

# Preparar dados para update
update_data = {
    'meta': {
        '_elementor_data': json.dumps(current_structure)
    }
}

url = f'{config["server"]}/wp-json/wp/v2/elementor_library/3214'
auth = (config['username'], config['password'])

print('\n🔄 Aplicando correção de altura e espaçamento...')
response = requests.post(url, auth=auth, json=update_data)

if response.status_code == 200:
    print('✅ ESPAÇO EMBAIXO DA IMAGEM ELIMINADO!')
    print('\n📋 Resultado:')
    print('  • Imagem com altura fixa sem espaço embaixo')
    print('  • Container de texto alinhado com altura da imagem')
    print('  • Overflow hidden para eliminar espaços extras')
    print('  • Espaçamento mínimo entre itens')
    print('  • Layout mais compacto e limpo')
    
    # Backup da estrutura otimizada
    with open('elementor-structure-optimized.json', 'w', encoding='utf-8') as f:
        json.dump(current_structure, f, indent=2, ensure_ascii=False)
    print('\n💾 Backup salvo: elementor-structure-optimized.json')
    
else:
    print(f'❌ Erro ao aplicar: {response.status_code}')
    print(f'Resposta: {response.text}')
