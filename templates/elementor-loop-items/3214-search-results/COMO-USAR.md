# COMO USAR O SCRIPT GENÉRICO

## SINTAXE SIMPLES:
```bash
python update-template.py [template_id] [arquivo.json] [opções]
```

## EXEMPLOS BÁSICOS:

### 1. Usar arquivo completo:
```bash
python update-template.py 3214 elementor-structure-flexible-height.json
```

### 2. Usar nome curto (adiciona .json automaticamente):
```bash
python update-template.py 3214 meu-template
python update-template.py 3214 versao-nova
```

### 3. Com descrição personalizada:
```bash
python update-template.py 3214 layout-final.json --description "Aplicando layout final"
```

### 4. Sem backup:
```bash
python update-template.py 3214 teste.json --no-backup
```

### 5. Com backup personalizado:
```bash
python update-template.py 3214 importante.json --backup "antes-mudanca-critica"
```

## FUNCIONALIDADES:
✅ **Totalmente genérico** - funciona com qualquer arquivo JSON  
✅ **Backup automático** - cria backup antes de aplicar  
✅ **Descrição automática** - gera descrição baseada no nome do arquivo  
✅ **Validação** - mostra arquivos disponíveis se errar o nome  
✅ **Flexível** - aceita nomes com ou sem .json

## ARQUIVO USADO SERÁ:
- Se termina com `.json` → usa exatamente como digitado
- Se não termina com `.json` → adiciona `.json` automaticamente
- Lista todos os arquivos `elementor-*.json` se arquivo não for encontrado
