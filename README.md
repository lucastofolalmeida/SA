# Sistema de Monitoramento Ferroviario
## Equipe
Heron Machado Soares
João Vitor Batista
Lucas de Tofol de Almeida
Rafael Batistella Neitzel
## O que o sistema faz
Operar uma frota ferroviária sem um sistema central é lidar com informação espalhada: não se sabe ao certo quais trens estão em operação, o que cada sensor está medindo, quais viagens estão em andamento nem quando uma leitura passou do limite seguro. Sem isso, os problemas só aparecem depois que já aconteceram, os gestores decidem sem dados confiáveis, e não há controle de quem pode ver ou alterar cada informação.

O FerroHub resolve isso reunindo tudo em um sistema web com login e perfis (administrador, maquinista e usuário comum). Nele se cadastram trens, sensores e rotas, e os sensores (simulados) geram leituras que o sistema avalia sozinho: se um valor passa do limite, cria um alerta com severidade normal, atenção ou crítico, destacando o crítico em vermelho. Um painel mostra os cinco indicadores da frota calculados direto do banco, e os relatórios de desempenho, consumo, eficiência e manutenção podem ser exportados em CSV ou impressos. Cada perfil enxerga só o que lhe cabe, as senhas ficam protegidas por hash e os usuários são desativados em vez de excluídos, para preservar o histórico.
## Tecnologias
PHP, MySQL, HTML, CSS, JavaScript. Ambiente: XAMPP.
## Como instalar
1. Copiar a pasta para htdocs
2. Iniciar Apache e MySQL no XAMPP
3. Importar banco/script.sql pelo phpMyAdmin
4. Acessar http://localhost/
## Usuarios de teste
admin senha: admin
administrador senha: 
usuario comum senha: 123 
(um de cada perfil que o sistema tiver)
## Telas do sistema
Lista curta: nome do arquivo e o que ele faz.
## Decisoes tomadas
As escolhas dos blocos anteriores, com uma frase de justificativa cada.
## Limitacoes conhecidas
O que nao foi implementado e por que. Seja honesto aqui.
## Uso de inteligencia artificial
Tabela de registro conforme a regra da unidade curricular.