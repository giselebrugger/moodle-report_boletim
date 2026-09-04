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
 * Funções de biblioteca do plugin report_boletim (boletim consolidado de notas e frequência).
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Verifica se o plugin mod_attendance está instalado no site.
 *
 * @return bool
 */
function report_boletim_has_attendance(): bool {
    // Retorna o diretório do plugin se existir, ou null caso contrário.
    return \core_component::get_plugin_directory('mod', 'attendance') !== null;
}

/**
 * Adiciona um nó "Boletim consolidado" à árvore de navegação do perfil do usuário.
 *
 * @param core_user\output\myprofile\tree $tree Árvore de navegação do perfil.
 * @param stdClass $user Usuário cujo perfil está sendo exibido.
 * @param bool $iscurrentuser Se o perfil exibido é o do usuário autenticado.
 * @param stdClass|null $course Curso de contexto, quando aplicável.
 * @return bool
 */
function report_boletim_myprofile_navigation(
    core_user\output\myprofile\tree $tree,
    $user,
    $iscurrentuser,
    $course
): bool {
    if (!has_capability('report/boletim:view', context_system::instance())) {
        return false;
    }

    if (!$iscurrentuser) {
        return false;
    }

    $tree->add_node(
        new core_user\output\myprofile\node(
            'reports',
            'reportboletim',
            get_string('pluginname', 'report_boletim'),
            null,
            new moodle_url('/report/boletim/index.php')
        )
    );

    return true;
}

/**
 * Normaliza acrônimo e descrição de um status de frequência para uso como
 * chave de busca/gravação na tabela report_boletim_status.
 *
 * @param string $acronym Acrônimo do status (ex.: "P", "F").
 * @param string $description Descrição do status.
 * @return array Array associativo com as chaves 'acronym' e 'description'.
 */
function report_boletim_status_key($acronym, $description): array {
    $description = strip_tags((string)$description);
    $description = preg_replace('/\s+/', ' ', trim($description));

    return [
        'acronym' => core_text::strtolower(trim((string)$acronym)),
        'description' => $description,
    ];
}

/**
 * Retorna todos os status de frequência (mod_attendance) distintos existentes no site.
 *
 * @return stdClass[]
 */
function report_boletim_get_all_attendance_statuses(): array {
    global $DB;

    // Se o mod_attendance não estiver instalado, as tabelas attendance_*
    // não existem no banco; não consulta nada.
    if (!report_boletim_has_attendance()) {
        return [];
    }

    $sql = "SELECT st.id,
                   st.acronym,
                   st.description,
                   a.name AS attendancename,
                   a.course,
                   c.fullname AS coursename
              FROM {attendance_statuses} st
              JOIN {attendance} a ON a.id = st.attendanceid
              JOIN {course} c ON c.id = a.course
             WHERE st.deleted = 0
               AND st.visible = 1
          ORDER BY st.acronym, st.description";

    return array_values($DB->get_records_sql($sql));
}

/**
 * Cria, para cada status de frequência ainda não conhecido, um registro
 * "neutral" em report_boletim_status, para que possa ser classificado
 * posteriormente pelo administrador.
 *
 * @return void
 */
function report_boletim_sync_statuses(): void {
    global $DB, $USER;

    $table = new xmldb_table('report_boletim_status');
    if (!$DB->get_manager()->table_exists($table)) {
        return;
    }

    $now = time();

    foreach (report_boletim_get_all_attendance_statuses() as $status) {
        $key = report_boletim_status_key($status->acronym, $status->description);

        if (!$DB->record_exists('report_boletim_status', $key)) {
            $record = (object)array_merge($key, [
                'classification' => 'neutral',
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => $USER->id,
            ]);

            $DB->insert_record('report_boletim_status', $record);
        }
    }
}

/**
 * Retorna a classificação (presence/absence/neutral) configurada para um
 * status de frequência específico.
 *
 * @param string $acronym Acrônimo do status.
 * @param string $description Descrição do status.
 * @return string
 */
function report_boletim_get_status_classification($acronym, $description): string {
    global $DB;

    $record = $DB->get_record(
        'report_boletim_status',
        report_boletim_status_key($acronym, $description)
    );

    return $record ? $record->classification : 'neutral';
}

/**
 * Retorna as atividades mod_attendance visíveis de um curso.
 *
 * @param int $courseid ID do curso.
 * @return stdClass[]
 */
function report_boletim_get_attendance_activities(int $courseid): array {
    global $DB;

    // Se o mod_attendance não estiver instalado, não consulta nada.
    if (!report_boletim_has_attendance()) {
        return [];
    }

    $sql = "SELECT a.id,
                   a.name,
                   cm.id AS cmid
              FROM {attendance} a
              JOIN {course_modules} cm ON cm.instance = a.id
              JOIN {modules} m ON m.id = cm.module
                               AND m.name = 'attendance'
             WHERE a.course = :courseid
               AND cm.deletioninprogress = 0
               AND cm.visible = 1
          ORDER BY cm.section, cm.id";

    return array_values($DB->get_records_sql($sql, ['courseid' => $courseid]));
}

/**
 * Calcula o resumo de presença/ausência de um usuário em uma atividade
 * mod_attendance, conforme a classificação global de status configurada.
 *
 * @param int $userid ID do usuário.
 * @param int $attendanceid ID da instância mod_attendance.
 * @return stdClass Objeto com sessions, presence, absence, percentuais, icon,
 *     iconlabel e hasdata.
 */
function report_boletim_get_attendance_summary(
    int $userid,
    int $attendanceid
): stdClass {
    global $DB;

    $summary = (object)[
        'sessions' => 0,
        'presence' => 0,
        'absence' => 0,
        'presencepercent' => null,
        'absencepercent' => null,
        'icon' => '🟢',
        'iconlabel' => get_string('situacao_semdados', 'report_boletim'),
        'hasdata' => false,
    ];

    $statuses = $DB->get_records(
        'attendance_statuses',
        [
            'attendanceid' => $attendanceid,
            'deleted' => 0,
            'visible' => 1,
        ],
        'setnumber ASC, id ASC'
    );

    $presenceids = [];
    $absenceids = [];

    foreach ($statuses as $status) {
        $classification = report_boletim_get_status_classification(
            $status->acronym,
            $status->description
        );

        if ($classification === 'presence') {
            $presenceids[] = (int)$status->id;
        } else if ($classification === 'absence') {
            $absenceids[] = (int)$status->id;
        }
    }

    $statusids = array_merge($presenceids, $absenceids);
    if (empty($statusids)) {
        return $summary;
    }

    list($insql, $params) = $DB->get_in_or_equal(
        $statusids,
        SQL_PARAMS_NAMED,
        'status'
    );
    $params['attendanceid'] = $attendanceid;
    $params['userid'] = $userid;

    $sql = "SELECT l.id,
                   l.statusid
              FROM {attendance_log} l
              JOIN {attendance_sessions} s ON s.id = l.sessionid
             WHERE s.attendanceid = :attendanceid
               AND l.studentid = :userid
               AND l.statusid $insql";

    $recordset = $DB->get_recordset_sql($sql, $params);

    foreach ($recordset as $log) {
        if (in_array((int)$log->statusid, $presenceids, true)) {
            $summary->presence++;
        } else if (in_array((int)$log->statusid, $absenceids, true)) {
            $summary->absence++;
        }
    }

    $recordset->close();

    $summary->sessions = $summary->presence + $summary->absence;
    $summary->hasdata = $summary->sessions > 0;

    if ($summary->sessions > 0) {
        $summary->presencepercent = round(
            $summary->presence / $summary->sessions * 100,
            1
        );
        $summary->absencepercent = round(
            $summary->absence / $summary->sessions * 100,
            1
        );

        // Lê o percentual de faltas configurado pelo admin (padrão 25%).
        $riskthreshold = (int)get_config('report_boletim', 'riskthreshold');
        if (!$riskthreshold) {
            $riskthreshold = 25;
        }

        // Se a % de ausência for maior ou igual ao limiar, marca como risco.
        if ($summary->absencepercent >= $riskthreshold) {
            $summary->icon = '🔴';
            $summary->iconlabel = get_string('situacao_insuficiente', 'report_boletim', $riskthreshold);
        } else {
            $summary->icon = '🟢';
            $summary->iconlabel = get_string('situacao_suficiente', 'report_boletim', $riskthreshold);
        }
    }

    return $summary;
}

/**
 * Retorna itens de nota (categorias e/ou itens) já na ordem da grade_tree.
 *
 * grademode:
 *  1 = só categorias com idnumber (como hoje)
 *  2 = todas as categorias e subcategorias
 *  3 = categorias + itens de nota (manual e mod)
 *
 * Regras de visibilidade (campo "hidden" do Moodle):
 *  hidden = 0        -> mostrar.
 *  hidden = 1        -> nunca mostrar.
 *  hidden > 1 (timestamp) -> esconder até essa data; só mostrar se hidden <= time().
 *
 * @param int $courseid ID do curso.
 * @param int $userid ID do usuário.
 * @return array[]
 */
function report_boletim_get_category_items(
    int $courseid,
    int $userid
): array {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');

    $grademode = (int)get_config('report_boletim', 'grademode');
    if (!$grademode) {
        $grademode = 1;
    }

    // Monta a árvore completa de categorias + grade_items.
    $gtree = new grade_tree($courseid, false, false);

    $items = [];

    // top_element é o array raiz da árvore; 'children' são os elementos de topo
    // (categorias raiz do curso).
    foreach ($gtree->top_element['children'] as $element) {
        report_boletim_collect_grade_elements($items, $element, $userid, $grademode, 1);
    }
    return $items;
}

/**
 * Percorre recursivamente um elemento da grade_tree e adiciona categorias/itens
 * ao array $items, já na ordem hierárquica, com nível (depth) calculado.
 *
 * @param array $items Array acumulador, passado por referência.
 * @param array $element Elemento atual da grade_tree (categoria ou item).
 * @param int $userid ID do usuário.
 * @param int $grademode Modo de exibição de notas (1, 2 ou 3).
 * @param int $level Nível de profundidade atual na árvore.
 * @return void
 */
function report_boletim_collect_grade_elements(
    array &$items,
    array $element,
    int $userid,
    int $grademode,
    int $level
): void {
    global $DB, $CFG;

    require_once($CFG->libdir . '/gradelib.php');
    $now    = time();
    $object = $element['object'];    // grade_category ou grade_item.
    $type   = $element['type'];      // 'category' ou 'item'.

    // Determina se este nó entra na lista, conforme grademode.
    $include = false;

    if ($type === 'category') {
        // Categoria sempre entra em 2 e 3; em 1 só se tiver idnumber.
        $gradeitem = $object->get_grade_item(); // Total da categoria.

        // Visibilidade da categoria (campo hidden em mdl_grade_categories).
        $cathidden = (int)$object->hidden;
        $catvisible =
            $cathidden === 0 ||
            ($cathidden > 1 && $cathidden <= $now); // Timestamp já passou.

        if ($catvisible) {
            if ($grademode === 1) {
                $include = !empty($gradeitem->idnumber);
            } else if ($grademode === 2 || $grademode === 3) {
                $include = true;
            }
        }
    } else if ($type === 'item') {
        // Itens de nota só aparecem no modo 3.
        if ($grademode === 3) {
            $gradeitem = $object; // Já é grade_item.

            // Visibilidade do item (campo hidden em mdl_grade_items).
            $hidden = (int)$gradeitem->hidden;
            $itemvisible =
                $hidden === 0 ||
                ($hidden > 1 && $hidden <= $now); // Timestamp já passou.

            if ($itemvisible) {
                $include = true;
            }
        }
    }

    // Se este nó (categoria ou item) deve ser incluído e está visível agora:
    if (!empty($include) && $gradeitem) {
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]);

        if ($grade && $grade->finalgrade !== null) {
            $display = grade_format_gradevalue(
                (float)$grade->finalgrade,
                $gradeitem,
                true,
                $gradeitem->get_displaytype(),
                $gradeitem->get_decimals()
            );

            // Range formatado pelo próprio grade_item; se for escala, usa os
            // textos da escala (ex.: IAM-AM).
            $range = $gradeitem->get_formatted_range();

            if ($type === 'category') {
                $name = format_string($object->fullname);
            } else {
                $name = format_string($gradeitem->itemname);
            }

            // depth = nível atual na árvore; report_boletim_get_data usa isso para indent.
            $depth = $level;

            $items[] = [
                'name'     => $name,
                'display'  => $display,
                'range'    => $range,
                'itemtype' => $type === 'category' ? 'category' : $gradeitem->itemtype,
                'depth'    => $depth,
            ];
        }
    }

    // Percorre filhos (subcategorias / itens dentro da categoria).
    if (!empty($element['children']) && is_array($element['children'])) {
        foreach ($element['children'] as $child) {
            report_boletim_collect_grade_elements(
                $items,
                $child,
                $userid,
                $grademode,
                $level + 1
            );
        }
    }
}

/**
 * Monta a estrutura completa do boletim (notas por categoria e frequência por
 * atividade) para todos os cursos em que o usuário está matriculado.
 *
 * @param int $userid ID do usuário.
 * @return stdClass[] Um item por curso, com as chaves usadas pelo template
 *     report_boletim/boletim.
 */
function report_boletim_get_data(int $userid): array {
    global $DB, $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    
     // Lê o modo de listagem de cursos configurado pelo admin.
    $courselistmode = (int)get_config('report_boletim', 'courselistmode');
    if (!$courselistmode) {
        $courselistmode = 2; // Padrão: cursos em andamento
    }

    // Pede também o enddate, necessário para filtrar cursos "em andamento". 
    $courses = enrol_get_all_users_courses(
        $userid,
        true,
        'id, fullname, enddate'
    );

    $now = time();

    // Se configurado para "somente em andamento", filtra por enddate.
    // enddate = 0 significa "sem data de término" (sempre em andamento). 
    if ($courselistmode === 2) {
        $courses = array_filter($courses, function ($course) use ($now) {
            return (int)$course->enddate === 0 || (int)$course->enddate > $now;
        });
    }

    $result = [];
    $hasattendance = report_boletim_has_attendance();

    foreach ($courses as $course) {
        // Config global: grade_report_user_showrange (0=Ocultar, 1=Mostrar).
        $sitedefaultshowrange = (int)$CFG->grade_report_user_showrange;

        // Config por curso: 'report_user_showrange', valor em {-1, 0, 1}.
        // -1 = usa o padrão do site, 0 = ocultar, 1 = mostrar.
        $coursesetting = grade_get_setting(
            $course->id,
            'report_user_showrange',
            -1
        );

        // Resolve precedência: curso -1 usa o default do site; 0/1 prevalece.
        if ((int)$coursesetting === -1) {
            $showrangeflag = $sitedefaultshowrange;
        } else {
            $showrangeflag = (int)$coursesetting;
        }

        // Boolean que o Mustache vai usar.
        $showrange = ($showrangeflag === 1);

        $entry = (object)[
            'coursename' => format_string($course->fullname),
            'courseurl' => (new moodle_url(
                '/grade/report/user/index.php',
                ['id' => $course->id]
            ))->out(false),
            'grades' => [],
            'attendances' => [],
            'showrange' => $showrange, // Valor por curso para o template.
        ];

        foreach (report_boletim_get_category_items($course->id, $userid) as $grade) {
            $indent = max(0, (int)$grade['depth'] - 2);

            // Se não for categoria, recua mais um nível.
            if (!empty($grade['itemtype']) && $grade['itemtype'] !== 'category') {
                $indent++;
            }
            $entry->grades[] = [
                'name' => format_string($grade['name']),
                'grade' => s($grade['display']), // Ok escapar a nota.
                'range' => $grade['range'],       // NÃO usar s() aqui: já vem formatado pelo core.
                'indent' => $indent,
            ];
        }

        if ($hasattendance) {
            foreach (report_boletim_get_attendance_activities($course->id) as $activity) {
                $summary = report_boletim_get_attendance_summary(
                    $userid,
                    $activity->id
                );

                $entry->attendances[] = [
                    'activityname' => format_string($activity->name),
                    'activityurl' => (new moodle_url(
                        '/mod/attendance/view.php',
                        ['mode' => 0, 'id' => $activity->cmid]
                    ))->out(false),
                    'sessions' => $summary->hasdata ? (string)$summary->sessions : '-',
                    'presence' => $summary->hasdata ? (string)$summary->presence : '-',
                    'presencepercent' => $summary->hasdata
                        ? $summary->presencepercent . '%'
                        : '-',
                    'absence' => $summary->hasdata ? (string)$summary->absence : '-',
                    'absencepercent' => $summary->hasdata
                        ? $summary->absencepercent . '%'
                        : '-',
                    'icon' => $summary->icon,
                    'iconlabel' => $summary->iconlabel,
                ];
            }
        }

        $entry->hasgrades = !empty($entry->grades);
        $entry->hasattendances = !empty($entry->attendances);
        $result[] = $entry;
    }

    return $result;
}
