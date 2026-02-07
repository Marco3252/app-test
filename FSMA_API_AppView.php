<?php
require_once __DIR__ . "/../DB/config/database.php";
require_once __DIR__ . "/../DB/classes/CRUD.php";
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/El_Salvador');

// conectar DB
$db = (new Database())->connect();
$crud = new CRUD($db);

// procesar datos
if (isset($_POST['Action'])) {
    $action = trim($_POST['Action']);
    
/* Consulta A_01 => Obtener alumno por id */
    if ($action === 'ObtenerAlumno') {

    if (!isset($_POST['AlumnoId'])) {
        echo json_encode([
            'success' => false,
            'message' => 'AlumnoId no enviado'
        ]);
        exit;
    }

    $alumnoId = intval($_POST['AlumnoId']);

    if ($alumnoId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'AlumnoId inválido'
        ]);
        exit;
    }

    $alumno = $crud->getById('alumnos', 'ID', $alumnoId);

    if ($alumno) {
        echo json_encode([
            'success' => true,
            'data' => $alumno
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Alumno no encontrado'
        ]);
    }
    exit;
}
/* Consulta A_02 => Obtener cursos inscritos del alumno */
elseif ($action === 'CursosAlumno') {

    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_POST['AlumnoId'])) {
        echo json_encode([
            'success' => false,
            'message' => 'AlumnoId no enviado'
        ]);
        exit;
    }

    $alumnoId = intval($_POST['AlumnoId']);

    if ($alumnoId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'AlumnoId inválido'
        ]);
        exit;
    }

    /* ===============================
       1️⃣ Obtener cursos inscritos
       =============================== */
    $sql = "
        SELECT c.*
        FROM inscripciones i
        INNER JOIN cursos c ON c.ID = i.CursoId
        WHERE i.AlumnoId = :alumnoId
        AND i.estado = 'Ins'
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['alumnoId' => $alumnoId]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$cursos) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }

    /* ===============================
       2️⃣ Fecha y hora actual (El Salvador)
       =============================== */
    date_default_timezone_set('America/El_Salvador');

    $horaActual = date('H:i');

    $dias = [
        'Monday'    => 'Lunes',
        'Tuesday'   => 'Martes',
        'Wednesday' => 'Miercoles',
        'Thursday'  => 'Jueves',
        'Friday'    => 'Viernes',
        'Saturday'  => 'Sabado',
        'Sunday'    => 'Domingo'
    ];

    $diaActual = $dias[date('l')];

    /* ===============================
       3️⃣ Buscar curso prioritario
       =============================== */
    $indexDiaHora = null;
    $indexSoloDia = null;

    foreach ($cursos as $index => $curso) {

        if (empty($curso['Horarios'])) continue;

        $horarios = json_decode($curso['Horarios'], true);
        if (!$horarios || !isset($horarios[$diaActual])) continue;

        // Coincide el día
        if ($indexSoloDia === null) {
            $indexSoloDia = $index;
        }

        // Coincide día + hora
        $entrada = $horarios[$diaActual]['HoraEntrada'];
        $salida  = $horarios[$diaActual]['HoraSalida'];

        if ($horaActual >= $entrada && $horaActual <= $salida) {
            $indexDiaHora = $index;
            break; // máxima prioridad, salimos
        }
    }

    /* ===============================
       4️⃣ Mover curso a posición 0
       =============================== */
    $indexFinal = $indexDiaHora ?? $indexSoloDia;

    if ($indexFinal !== null) {
        $cursoPrioritario = $cursos[$indexFinal];
        unset($cursos[$indexFinal]);
        array_unshift($cursos, $cursoPrioritario);
        $cursos = array_values($cursos);
    }

    /* ===============================
       5️⃣ Respuesta
       =============================== */
    echo json_encode([
        'success' => true,
        'data' => $cursos
    ]);
    exit;
}
/* Consulta A_03 => Registrar la asistencia */
elseif ($action == 'NuevaAsistencia') {

        if (!isset($_POST['AlumnoId'], $_POST['CursoId'])) {
            throw new Exception("Datos incompletos");
        }

        $db = (new Database())->connect();

        $alumnoId = intval($_POST['AlumnoId']);
        $cursoId  = intval($_POST['CursoId']);
        $force    = isset($_POST['ForceOverwrite']);

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        /* ======================================
           CONTAR REGISTROS DEL DÍA
           ====================================== */
        $stmt = $db->prepare("
            SELECT estado
            FROM asistencias
            WHERE CursoId = :curso
              AND AlumnoId = :alumno
              AND Fecha = :fecha
        ");
        $stmt->execute([
            'curso'  => $cursoId,
            'alumno' => $alumnoId,
            'fecha'  => $fecha
        ]);

        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cantidad  = count($registros);

        /* ======================================
           CASO 3 → YA HAY ENTRADA Y SALIDA
           ====================================== */
        if ($cantidad >= 2 && !$force) {
            echo json_encode([
                'success' => false,
                'needs_confirm' => true,
                'message' => 'Ya se registró entrada y salida'
            ]);
            exit;
        }

        /* ======================================
           FORZAR SOBREESCRITURA DE SALIDA
           ====================================== */
        if ($cantidad >= 2 && $force) {

            $stmt = $db->prepare("
                DELETE FROM asistencias
                WHERE CursoId = :curso
                  AND AlumnoId = :alumno
                  AND Fecha = :fecha
                  AND estado = 'salida'
            ");
            $stmt->execute([
                'curso'  => $cursoId,
                'alumno' => $alumnoId,
                'fecha'  => $fecha
            ]);

            $stmt = $db->prepare("
                INSERT INTO asistencias
                (CursoId, AlumnoId, Fecha, Hora, estado)
                VALUES (:curso, :alumno, :fecha, :hora, 'salida')
            ");
            $stmt->execute([
                'curso'  => $cursoId,
                'alumno' => $alumnoId,
                'fecha'  => $fecha,
                'hora'   => $hora
            ]);

            echo json_encode([
                'success' => true,
                'tipo' => 'salida_actualizada'
            ]);
            exit;
        }

        /* ======================================
           CASO 2 → REGISTRAR SALIDA
           ====================================== */
        if ($cantidad === 1) {

            $stmt = $db->prepare("
                INSERT INTO asistencias
                (CursoId, AlumnoId, Fecha, Hora, estado)
                VALUES (:curso, :alumno, :fecha, :hora, 'salida')
            ");
            $stmt->execute([
                'curso'  => $cursoId,
                'alumno' => $alumnoId,
                'fecha'  => $fecha,
                'hora'   => $hora
            ]);

            echo json_encode([
                'success' => true,
                'tipo' => 'salida'
            ]);
            exit;
        }

        /* ======================================
           CASO 1 → REGISTRAR ENTRADA
           ====================================== */
        $estado = 'a_tiempo';

        $stmt = $db->prepare("SELECT Horarios FROM cursos WHERE ID = :id");
        $stmt->execute(['id' => $cursoId]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($curso && !empty($curso['Horarios'])) {

            $horarios = json_decode($curso['Horarios'], true);

            if (json_last_error() === JSON_ERROR_NONE) {

                $dias = [
                    'Monday'    => 'Lunes',
                    'Tuesday'   => 'Martes',
                    'Wednesday' => 'Miercoles',
                    'Thursday'  => 'Jueves',
                    'Friday'    => 'Viernes',
                    'Saturday'  => 'Sabado',
                    'Sunday'    => 'Domingo'
                ];

                $dia = $dias[date('l')];

                if (isset($horarios[$dia]['HoraEntrada'])) {
                    if ($hora > $horarios[$dia]['HoraEntrada']) {
                        $estado = 'tarde';
                    }
                }
                // Si no existe el día → a_tiempo (regla tuya)
            }
        }

        $stmt = $db->prepare("
            INSERT INTO asistencias
            (CursoId, AlumnoId, Fecha, Hora, estado)
            VALUES (:curso, :alumno, :fecha, :hora, :estado)
        ");
        $stmt->execute([
            'curso'  => $cursoId,
            'alumno' => $alumnoId,
            'fecha'  => $fecha,
            'hora'   => $hora,
            'estado' => $estado
        ]);

        echo json_encode([
            'success' => true,
            'tipo' => 'entrada',
            'estado' => $estado
        ]);
        exit;
    }

    else {
        http_response_code(400); 
            echo json_encode(['success' => false, 'message' => 'Accion no valida']); 
            exit;
    }
}
?>