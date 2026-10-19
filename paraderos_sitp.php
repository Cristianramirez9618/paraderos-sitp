
<?php
$api = "https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8/query";

$localidades = [
    "ANTONIO NARIÑO",
    "BARRIOS UNIDOS",
    "BOSA",
    "CANDELARIA",
    "CHAPINERO",
    "CIUDAD BOLIVAR",
    "ENGATIVA",
    "FONTIBON",
    "KENNEDY",
    "LOS MARTIRES",
    "PUENTE ARANDA",
    "RAFAEL URIBE URIBE",
    "SAN CRISTOBAL",
    "SANTA FE",
    "SUBA",
    "SUMAPAZ",
    "TEUSAQUILLO",
    "TUNJUELITO",
    "USAQUEN",
    "USME"
];

function normalizarTexto($texto) {
    $texto = trim((string)$texto);

    if (function_exists("iconv")) {
        $resultado = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
        if ($resultado !== false) {
            $texto = $resultado;
        }
    }

    $texto = strtoupper($texto);
    $texto = preg_replace('/\s+/', ' ', $texto);

    return trim($texto);
}

$localidad = $_GET["localidad"] ?? "CHAPINERO";

if (!in_array($localidad, $localidades, true)) {
    $localidad = "CHAPINERO";
}

$paraderos = [];
$error = "";
$localidadesEncontradas = [];
$offset = 0;
$limite = 1000;
$hayMas = true;
$maxPaginas = 100;

while ($hayMas && ($offset / $limite) < $maxPaginas) {
    $parametros = [
        "where" => "1=1",
        "outFields" => "CENEFA,NOMBRE,VIA,DIRECCION_,LOCALIDAD,LATITUD,LONGITUD,ZONA_SITP,TIPO_M_S,CONSOLA,PANEL,AUDIO,OBJECTID_1",
        "returnGeometry" => "false",
        "f" => "json",
        "resultOffset" => $offset,
        "resultRecordCount" => $limite,
        "orderByFields" => "OBJECTID_1 ASC"
    ];

    $url = $api . "?" . http_build_query($parametros);

    $contexto = stream_context_create([
        "http" => [
            "timeout" => 45,
            "ignore_errors" => true
        ]
    ]);

    $respuesta = @file_get_contents($url, false, $contexto);

    if ($respuesta === false && function_exists("curl_init")) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 45);

        $respuesta = curl_exec($curl);

        if ($respuesta === false) {
            $error = "No se pudo conectar con el servicio de paraderos.";
        }

        curl_close($curl);
    }

    if ($respuesta === false) {
        if ($error === "") {
            $error = "No se pudo obtener información de la API. Revisa la conexión a internet.";
        }
        break;
    }

    $datos = json_decode($respuesta, true);

    if (!is_array($datos)) {
        $error = "La API devolvió una respuesta que no se pudo interpretar.";
        break;
    }

    if (isset($datos["error"])) {
        $error = "Error de la API: " .
            ($datos["error"]["message"] ?? "Error desconocido");
        break;
    }

    if (!isset($datos["features"]) || !is_array($datos["features"])) {
        $error = "La API no devolvió una lista de paraderos válida.";
        break;
    }

    $cantidad = count($datos["features"]);

    foreach ($datos["features"] as $elemento) {
        if (!isset($elemento["attributes"])) {
            continue;
        }

        $registro = $elemento["attributes"];
        $nombreLocalidad = trim((string)($registro["LOCALIDAD"] ?? ""));

        if ($nombreLocalidad !== "") {
            $localidadesEncontradas[$nombreLocalidad] = true;
        }

        // Comparar ignorando tildes, mayúsculas y espacios.
        if (
            normalizarTexto($nombreLocalidad) ===
            normalizarTexto($localidad)
        ) {
            $paraderos[] = $registro;
        }
    }

    $offset += $cantidad;
    $hayMas = ($cantidad === $limite);

    if ($cantidad === 0) {
        $hayMas = false;
    }
}

if ($hayMas && $error === "") {
    $error = "La consulta superó el límite de páginas configurado. Intenta nuevamente o reduce la consulta.";
}

if ($error !== "") {
    $paraderos = [];
}

$localidadesEncontradas = array_keys($localidadesEncontradas);
sort($localidadesEncontradas, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Paraderos SITP por localidad</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .contenedor {
            max-width: 1250px;
            margin: auto;
            padding: 28px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,.08);
        }

        h1 {
            color: #075985;
            margin-top: 0;
        }

        .descripcion {
            color: #475569;
            line-height: 1.5;
        }

        form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
        }

        label {
            font-weight: bold;
        }

        select, button {
            padding: 11px 14px;
            font-size: 15px;
            border-radius: 6px;
        }

        select {
            min-width: 230px;
            border: 1px solid #cbd5e1;
        }

        button {
            background: #0369a1;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #075985;
        }

        .resumen {
            background: #e0f2fe;
            padding: 14px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .tabla-contenedor {
            overflow-x: auto;
            width: 100%;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 14px;
        }

        th, td {
            padding: 11px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #075985;
            color: white;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #e0f2fe;
        }

        .error {
            padding: 14px;
            color: #991b1b;
            background: #fee2e2;
            border-radius: 6px;
            overflow-wrap: anywhere;
        }

        .aviso {
            padding: 14px;
            background: #fff7ed;
            border-radius: 6px;
            color: #9a3412;
            overflow-wrap: anywhere;
        }

        .fuente {
            margin-top: 24px;
            color: #64748b;
            font-size: 13px;
        }

        .fuente a {
            color: #0369a1;
        }
    </style>
</head>
<body>
<div class="contenedor">

    <h1>Paraderos SITP de Bogotá</h1>

    <p class="descripcion">
        Selecciona una localidad para consultar sus paraderos zonales
        registrados en el servicio geográfico de Catastro Bogotá.
    </p>

    <form method="GET">
        <label for="localidad">Localidad:</label>

        <select name="localidad" id="localidad">
            <?php foreach ($localidades as $nombre): ?>
                <option
                    value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $localidad === $nombre ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Consultar paraderos</button>
    </form>

    <?php if ($error !== ""): ?>

        <div class="error">
            <strong>No fue posible completar la consulta.</strong>
            <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

    <?php else: ?>

        <div class="resumen">
            <strong>Localidad seleccionada:</strong>
            <?= htmlspecialchars($localidad, ENT_QUOTES, 'UTF-8') ?>
            &nbsp; | &nbsp;
            <strong>Paraderos encontrados:</strong>
            <?= count($paraderos) ?>
        </div>

        <?php if (count($paraderos) === 0): ?>

            <div class="aviso">
                <strong>No se encontraron coincidencias.</strong>
                <p>
                    La API respondió, pero no se encontraron registros cuyo
                    campo LOCALIDAD coincida con la selección.
                </p>

                <p>
                    <strong>Valores de localidad que la API sí devolvió:</strong>
                </p>

                <?php if (count($localidadesEncontradas) > 0): ?>
                    <ul>
                        <?php foreach ($localidadesEncontradas as $nombre): ?>
                            <li>
                                <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>
                        No se recibieron valores para el campo LOCALIDAD.
                        Es posible que el servicio no esté entregando ese campo.
                    </p>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Vía</th>
                            <th>Dirección</th>
                            <th>Localidad</th>
                            <th>Latitud</th>
                            <th>Longitud</th>
                            <th>Zona SITP</th>
                            <th>Tipo</th>
                            <th>Consola</th>
                            <th>Panel</th>
                            <th>Audio</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paraderos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($p["CENEFA"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["NOMBRE"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["VIA"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["DIRECCION_"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["LOCALIDAD"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["LATITUD"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["LONGITUD"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["ZONA_SITP"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["TIPO_M_S"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["CONSOLA"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["PANEL"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p["AUDIO"] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    <?php endif; ?>

    <p class="fuente">
        Fuente de datos:
        <a
            href="https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8"
            target="_blank"
            rel="noopener noreferrer"
        >
            Catastro Bogotá — Paraderos Zonales SITP
        </a>
    </p>

</div>
</body>
</html>
