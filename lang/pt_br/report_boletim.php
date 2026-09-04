<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings de idioma (português do Brasil) para o plugin report_boletim.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Boletim Consolidado';
$string['boletimde'] = 'Boletim de {$a}';
$string['notasporcategoria'] = 'Notas por categoria';
$string['categoria'] = 'Categoria';
$string['nota'] = 'Nota';
$string['frequenciaporatividade'] = 'Frequência por atividade';
$string['atividade'] = 'Atividade';
$string['sessoes'] = 'Sessões';
$string['presencas'] = 'Presenças';
$string['ausencias'] = 'Ausências';
$string['situacao'] = 'Situação';
$string['semdados'] = 'Nenhum curso encontrado para exibir o boletim.';
$string['boletim:view'] = 'Visualizar o próprio boletim consolidado';
$string['boletim:configure'] = 'Configurar a classificação de status e o modo de exibição de notas do boletim';
$string['situacao_suficiente'] = 'Frequência suficiente (ausência abaixo de {$a}%)';
$string['situacao_insuficiente'] = 'Frequência insuficiente (ausência igual ou acima de {$a}%)';
$string['situacao_semdados'] = 'Sem dados de frequência registrados';
$string['privacy:metadata'] = 'O plugin Boletim Consolidado apenas lê e agrega dados já armazenados pelo módulo de notas e pelo mod_attendance; a única tabela própria do plugin (classificação global de status de frequência) é uma configuração administrativa, sem dados pessoais de usuários.';
$string['statusconfig'] = 'Classificação global dos status';
$string['statusheader'] = 'Status globais';
$string['classification_presence'] = 'Presença';
$string['classification_absence'] = 'Ausência';
$string['classification_neutral'] = 'Não contabilizar';
$string['changessaved'] = 'Classificações salvas.';
$string['synctask'] = 'Sincronizar status de frequência do Boletim consolidado';
// Cabeçalho do bloco de notas.
$string['gradesheader'] = 'Configuração das notas exibidas';
// Rótulo do seletor.
$string['grademode'] = 'Que notas devem aparecer no boletim?';

// Opções.
$string['grademode_categories_idnumber'] = 'Somente categorias com identificador (idnumber)';
$string['grademode_categories_all']      = 'Todas as categorias e subcategorias';
$string['grademode_all_items']           = 'Categorias, subcategorias e itens de nota (manual e atividades)';
$string['riskthreshold'] = 'Percentual de faltas para considerar risco';
$string['riskthreshold_range'] = 'Informe um valor entre 0 e 100.';
$string['coursesheader'] = 'Configuração dos cursos exibidos';
$string['courselistmode'] = 'Quais cursos devem aparecer no boletim?';
$string['courselistmode_help'] = 'Escolha se o boletim deve exibir todos os cursos em que o aluno está matriculado ou apenas os cursos ainda em andamento (data final maior que a data atual, ou sem data final definida).';
$string['courselistmode_all'] = 'Todos os cursos matriculados';
$string['courselistmode_inprogress'] = 'Somente cursos em andamento';