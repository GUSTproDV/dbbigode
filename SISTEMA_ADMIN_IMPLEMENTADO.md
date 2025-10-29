# Sistema de Administração - DB Bigode

## 🚀 Sistema de Email Admin Implementado!

Agora o seu sistema possui um **painel administrativo completo** com diferentes níveis de acesso para admins e clientes.

## 📋 O que foi criado:

### 1. **Sistema de Níveis de Usuário**
- Clientes normais: Acesso limitado ao sistema básico
- Administradores: Acesso total ao painel administrativo

### 2. **Painel Administrativo** (`/admin/`)
- **Dashboard Principal**: Estatísticas e visão geral
- **Gerenciar Usuários**: Promover/rebaixar usuários, ativar/desativar contas
- **Gerenciar Agendamentos**: Visualizar, filtrar e excluir agendamentos
- **Relatórios**: Gráficos e estatísticas do sistema
- **Configurações**: Manutenção e configurações do sistema

### 3. **Arquivos Criados**:
```
admin/
├── index.php          (Dashboard principal)
├── usuarios.php       (Gerenciar usuários)
├── agendamentos.php   (Gerenciar agendamentos)
├── relatorios.php     (Relatórios e gráficos)
└── configuracoes.php  (Configurações do sistema)

include/
└── admin_middleware.php (Middleware de segurança)

setup_admin.sql        (Script de configuração do banco)
```

## ⚡ Como ativar o sistema:

### Passo 1: Importar o Banco Completo
O sistema administrativo já está **integrado no arquivo principal**! Basta:

1. **Abra o phpMyAdmin**
2. **Importe o arquivo `dbbigode.sql`** completo (que já inclui tudo)
3. **Pronto!** O sistema admin já está configurado automaticamente

**OU se já tem o banco criado, execute apenas:**

```sql
-- Adicionar sistema admin ao banco existente
ALTER TABLE `usuario` ADD COLUMN `tipo_usuario` ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente';

-- Promover seu usuário existente (substitua o email)
UPDATE `usuario` SET `tipo_usuario` = 'admin' WHERE `email` = 'SEU_EMAIL_AQUI@gmail.com';
```

### Passo 2: Fazer Login
1. Acesse `http://localhost/dbbigode/dbbigode/`
2. Faça login com:
   - **Se importou o banco novo**: Email `admin@barbearia.com` / Senha `admin123`
   - **Se promoveu seu usuário**: Seu email e senha normais

### Passo 3: Acessar o Painel Admin
Depois do login como admin, você será redirecionado automaticamente para `/admin/` ou pode clicar no botão "👑 Admin" no header.

## 🔥 Funcionalidades do Admin:

### Dashboard
- **Estatísticas em tempo real**: Total de usuários, agendamentos
- **Próximos agendamentos**: Visualização dos próximos horários
- **Menu de navegação**: Acesso rápido a todas as funcionalidades

### Gerenciar Usuários
- **Promover para Admin**: Dar permissões administrativas
- **Rebaixar para Cliente**: Remover permissões administrativas
- **Ativar/Desativar**: Bloquear ou desbloquear usuários
- **Criar novos usuários**: Diretamente pelo painel

### Gerenciar Agendamentos
- **Visualizar todos os agendamentos**: Com filtros por data e nome
- **Estatísticas**: Agendamentos hoje, futuros, passados
- **Excluir agendamentos**: Cancelar horários se necessário
- **Cores indicativas**: Passado (cinza), hoje (amarelo), futuro (azul)

### Relatórios
- **Gráficos interativos**: Agendamentos por dia
- **Estatísticas de serviços**: Quais cortes são mais populares
- **Exportação**: Funcionalidade para exportar relatórios

### Configurações
- **Informações do sistema**: Versão PHP, tamanho do banco
- **Manutenção**: Backup, limpeza de arquivos antigos
- **Logs do sistema**: Histórico de ações administrativas

## 🛡️ Segurança Implementada:

1. **Middleware de Autenticação**: Verificação automática de permissões
2. **Redirecionamento Automático**: Admins vão direto para o painel admin
3. **Proteção de Páginas**: Páginas admin só acessíveis por administradores
4. **Botão Admin no Header**: Visível apenas para administradores

## 🎨 Interface Visual:

- **Design responsivo**: Funciona em desktop e mobile
- **Cores diferenciadas**: Cada seção tem sua identidade visual
- **Ícones FontAwesome**: Interface moderna e intuitiva
- **Bootstrap**: Componentes profissionais
- **Gráficos Chart.js**: Visualização avançada de dados

## 📧 Como Diferenciar Admin de Cliente:

### No Sistema:
- **Clientes**: Login normal → Redirecionamento para `/home/`
- **Admins**: Login → Redirecionamento automático para `/admin/`

### No Header:
- **Clientes**: Veem apenas o menu normal
- **Admins**: Veem botão "👑 Admin" vermelho para acesso rápido

### No Banco de Dados:
- Campo `tipo_usuario` na tabela `usuario`:
  - `'cliente'`: Usuário normal
  - `'admin'`: Administrador com acesso total

## 🚀 Próximos Passos:

1. **Execute o script SQL** para criar a estrutura
2. **Faça login como admin** para testar
3. **Promova usuários** conforme necessário
4. **Explore todas as funcionalidades** do painel

---

**🎉 Agora você tem um sistema completo de administração!**

Os administradores podem:
- ✅ Gerenciar todos os usuários
- ✅ Visualizar e controlar agendamentos
- ✅ Acessar relatórios e estatísticas
- ✅ Fazer manutenção do sistema
- ✅ Configurar parâmetros administrativos

Enquanto os clientes têm acesso apenas às funcionalidades básicas de agendamento e perfil!