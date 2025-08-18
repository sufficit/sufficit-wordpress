# Guia de Uso - API WordPress

## 🔧 Como Conectar na API do WordPress

### Configuração Básica

As informações de conexão estão no arquivo `config/config.json`:

```json
{
    "server": "https://exemplo.com",
    "username": "usuario",
    "password": "senha"
}
```

### ✅ Método que Funciona - Python

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import requests
import base64
from pathlib import Path

def atualizar_post_wordpress(post_id):
    """Atualiza um post específico no WordPress"""
    try:
        # Configuração
        config_path = Path("./config/config.json")
        with open(config_path, 'r', encoding='utf-8') as f:
            config = json.load(f)
        
        # Conteúdo HTML
        html_path = Path(f"./postagens/{post_id}-nome-do-arquivo.html")
        with open(html_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # URL da API
        url = f"{config['server']}/wp-json/wp/v2/posts/{post_id}"
        
        # IMPORTANTE: Usar autenticação Basic, não Bearer
        credentials = f"{config['username']}:{config['password']}"
        encoded_credentials = base64.b64encode(credentials.encode()).decode()
        
        headers = {
            'Authorization': f"Basic {encoded_credentials}",
            'Content-Type': 'application/json; charset=utf-8',
            'User-Agent': 'WordPress-API-Client/1.0'
        }
        
        data = {
            "content": {
                "raw": content
            }
        }
        
        # Requisição
        response = requests.put(url, headers=headers, json=data)
        
        if response.status_code == 200:
            result = response.json()
            print(f"✅ POST {post_id} atualizado com sucesso!")
            print(f"URL: {result.get('link', 'N/A')}")
        else:
            print(f"❌ Erro: {response.status_code}")
            
    except Exception as e:
        print(f"❌ Erro: {e}")

if __name__ == "__main__":
    # Exemplo: atualizar_post_wordpress(2988)
    pass
```

### ✅ Método que Funciona - PowerShell

```powershell
# Carrega a configuração do arquivo JSON
$config = Get-Content -Raw -Path './config/config.json' | ConvertFrom-Json

# Extrai e prepara os dados
$serverUrl = $config.server
$username = $config.username
$password = $config.password

# Constrói a URL da API (exemplo para listar posts)
$apiUrl = "$serverUrl/wp-json/wp/v2/posts" 

# Monta o cabeçalho de autorização Basic
$credentials = "$username`:$password"
$encodedCredentials = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($credentials))
$headers = @{
    "Authorization" = "Basic $encodedCredentials"
}

# Faz a requisição para a API
try {
    $response = Invoke-RestMethod -Uri $apiUrl -Headers $headers -Method Get
    Write-Host "Conexão bem-sucedida!"
    # Exibe a resposta
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Falha na conexão com a API."
    Write-Host "Erro: $($_.Exception.Message)"
}
```

## ⚠️ Lições Aprendidas

1. **Sempre usar autenticação Basic** com `username:password` em base64
2. **Não usar Bearer token** - causa erro 401
3. **Especificar post_id correto** na URL da API
4. **Usar `Content-Type: application/json; charset=utf-8`**
5. **Estrutura de dados**: `{"content": {"raw": "html_content"}}`

## 📚 Endpoints Principais

- **Endpoint**: `/wp-json/wp/v2/posts/{id}`
- **Método**: PUT para atualizações
- **Estrutura content**: `{"content": {"raw": "html"}}`
- **Headers**: Authorization Basic auth
