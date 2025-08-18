import requests
import json
import os

# Carregar config
config_path = os.path.join('..', '..', '..', 'config', 'config.json')
with open(config_path, 'r', encoding='utf-8') as f:
    config = json.load(f)

print('=== ELIMINAR PADDING INTERNO TEMPLATE 3214 ===')
print(f'🌐 Servidor: {config["server"]}')

# Carregar estrutura atual
with open('elementor-structure-perfect.json', 'r', encoding='utf-8') as f:
    current_structure = json.load(f)

# ATACAR DIRETAMENTE O PROBLEMA DO PADDING INTERNO

# 1. Container principal - forçar padding zero em TUDO
current_structure[0]["settings"]["padding"] = {
    "unit": "px",
    "top": "0",
    "right": "0",
    "bottom": "0",
    "left": "0",
    "isLinked": True
}

# 2. Adicionar CSS específico no container principal para eliminar qualquer padding
if "custom_css" not in current_structure[0]["settings"]:
    current_structure[0]["settings"]["custom_css"] = ""

current_structure[0]["settings"]["custom_css"] = """selector {
    padding: 0 !important;
    margin-top: 1px !important;
    margin-bottom: 1px !important;
    align-items: flex-start !important;
}

selector * {
    box-sizing: border-box !important;
}"""

# 3. Container de texto - CSS ainda mais agressivo
current_structure[0]["elements"][1]["settings"]["padding"] = {
    "unit": "px",
    "top": "0",
    "right": "0",
    "bottom": "0",
    "left": "0",
    "isLinked": True
}

# Adicionar CSS específico para o container de texto
if "custom_css" not in current_structure[0]["elements"][1]["settings"]:
    current_structure[0]["elements"][1]["settings"]["custom_css"] = ""

current_structure[0]["elements"][1]["settings"]["custom_css"] = """selector {
    padding: 0 !important;
    margin: 0 !important;
    align-self: flex-start !important;
    justify-content: flex-start !important;
    align-items: flex-start !important;
    align-content: flex-start !important;
}

selector > * {
    margin-top: 0 !important;
    padding-top: 0 !important;
}"""

# 4. CSS mais específico para a imagem - eliminar qualquer padding
current_structure[0]["elements"][0]["settings"]["custom_css"] = """selector {
    max-width: 120px !important;
    width: 15% !important;
    height: 90px !important;
    flex-shrink: 0;
    align-self: flex-start !important;
    margin: 0 8px 0 0 !important;
    padding: 0 !important;
}

selector img {
    width: 100% !important;
    height: 90px !important;
    max-width: 120px !important;
    object-fit: cover;
    display: block;
    margin: 0 !important;
    padding: 0 !important;
    vertical-align: top !important;
    border: none !important;
    outline: none !important;
}

selector .elementor-widget-container {
    padding: 0 !important;
    margin: 0 !important;
}

@media (max-width: 768px) {
    selector {
        width: 30% !important;
        max-width: 100px !important;
        height: 75px !important;
    }
    selector img {
        height: 75px !important;
        max-width: 100px !important;
    }
}"""

# 5. CSS ultra-específico para o título
current_structure[0]["elements"][1]["elements"][0]["settings"]["custom_css"] = """selector {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.3 !important;
    vertical-align: top !important;
}

selector h3,
selector .elementor-heading-title {
    margin: 0 0 2px 0 !important;
    padding: 0 !important;
    line-height: 1.3 !important;
    vertical-align: top !important;
}

selector .elementor-widget-container {
    padding: 0 !important;
    margin: 0 !important;
}"""

# 6. CSS para a descrição
current_structure[0]["elements"][1]["elements"][1]["settings"]["custom_css"] = """selector {
    margin: 0 !important;
    padding: 0 !important;
}

selector .elementor-widget-container {
    padding: 0 !important;
    margin: 0 !important;
}

selector p {
    margin: 0 !important;
    padding: 0 !important;
}"""

print('🔥 Correções agressivas aplicadas:')
print('  🎯 Padding zerado em TODOS os containers')
print('  🎯 CSS específico para .elementor-widget-container')
print('  🎯 Margin/padding forçados com !important')
print('  🎯 Box-sizing: border-box forçado')
print('  🎯 Align-items: flex-start em todos níveis')

# Preparar dados para update
update_data = {
    'meta': {
        '_elementor_data': json.dumps(current_structure)
    }
}

url = f'{config["server"]}/wp-json/wp/v2/elementor_library/3214'
auth = (config['username'], config['password'])

print('\n🔄 Aplicando eliminação total de padding...')
response = requests.post(url, auth=auth, json=update_data)

if response.status_code == 200:
    print('✅ PADDING INTERNO ELIMINADO COM SUCESSO!')
    print('\n📋 Correções ultra-específicas:')
    print('  • Padding zerado em container principal')
    print('  • CSS específico para .elementor-widget-container')
    print('  • Align-items: flex-start forçado')
    print('  • Box-sizing: border-box aplicado')
    print('  • Margin/padding com !important em tudo')
    
    # Backup da estrutura sem padding
    with open('elementor-structure-no-padding.json', 'w', encoding='utf-8') as f:
        json.dump(current_structure, f, indent=2, ensure_ascii=False)
    print('\n💾 Backup salvo: elementor-structure-no-padding.json')
    
else:
    print(f'❌ Erro ao aplicar: {response.status_code}')
    print(f'Resposta: {response.text}')
