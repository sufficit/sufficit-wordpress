# Guia de Uso - Scripts Python

## 🐍 Estrutura Padrão dos Scripts Python

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Descrição do script
"""

import json
import requests
from pathlib import Path

def funcao_principal():
    """Documentação da função"""
    pass

if __name__ == "__main__":
    funcao_principal()
```

## 📋 Padrões de Codificação

### Tratamento de Erros
```python
try:
    # operação
    pass
except SpecificError as e:
    print(f"❌ Erro específico: {e}")
except Exception as e:
    print(f"❌ Erro inesperado: {e}")
```

## 🔧 Comandos que Funcionam

### Para atualizar post específico:
```bash
cd "c:\Desenvolvimento\wordpress"
python temp\atualizar_post_2988_correto.py
```

### Para gerar imagens:
```bash
cd "c:\Desenvolvimento\wordpress" 
python scripts\gerar_imagem.py "prompt da imagem"
```

### Para atualizar imagem de capa:
```bash
# Atualizar capa com URL (redimensiona automaticamente para 1152x866)
python scripts\atualizar_imagem_capa.py 2988 "https://exemplo.com/imagem.jpg"

# Atualizar capa com arquivo local (redimensiona automaticamente)
python scripts\atualizar_imagem_capa.py 3007 "capa-nova.png"

# Verificar capa atual
python scripts\atualizar_imagem_capa.py --verificar 2988

# Usar caminho completo
python scripts\atualizar_imagem_capa.py 3100 "c:/pasta/imagem.webp"
```

## ⚠️ Comandos a Evitar

- `scripts/atualizar_post_python.py` (ID fixo errado)
- Autenticação Bearer (usar Basic)
- Posts ID 1515 (lixeira)

## 📚 Dependências

### Obrigatórias
- `requests` - Para chamadas de API
- `pathlib` - Para manipulação de caminhos
- `json` - Para configurações
- `base64` - Para autenticação

### Opcionais
- `Pillow` - Para redimensionamento de imagens
  ```bash
  pip install Pillow
  ```
