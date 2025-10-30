<?php
// index.php - Versión final y corregida
/**
 * index.php
 *
 * Es el punto de entrada principal y la interfaz de usuario de la aplicación.
 * Muestra el formulario de búsqueda y el de subida de archivos.
 * Orquesta el proceso de búsqueda: recibe la consulta, la pasa al parser y al motor de búsqueda,
 * y finalmente muestra los resultados ordenados por relevancia.
 * @package    DocumentSearchEngine
 */
require_once 'db_connection.php';
require_once 'parser.php';
require_once 'utils.php'; // Aún necesario para el parser

require_once 'search_engine.php'; // Nuevo motor de búsqueda

$query_string = isset($_GET['q']) ? $_GET['q'] : '';
$results = [];
$error_message = '';
$debug_info = '';

if (!empty($query_string)) {
    try {
        // 1. Parsear la consulta a tokens
        $tokens = parse_query_to_tokens($query_string);

        // 2. Ejecutar la búsqueda con el motor FULLTEXT
        // Esta función ahora devuelve los resultados completos y ordenados
        $search_result = execute_search($tokens, $conn);

        // 3. Asignar resultados y depuración
        $results = $search_result['results'];
        $debug_info = $search_result['debug'];

    } catch (Exception $e) {
        $error_message = "Error al procesar la consulta: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de Documentos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Buscador de Documentos</h1>
        <form action="index.php" method="get" class="search-form">
            <input type="text" name="q" class="search-input" placeholder="Escribe tu consulta aquí..." value="<?php echo htmlspecialchars($query_string); ?>">
            <button type="submit" class="search-button">Buscar</button>
        </form>

        <div class="upload-section">
            <h2>Indexar Nuevos Documentos</h2>
            <p>Sube archivos de texto (.txt) para añadirlos al índice de búsqueda.</p>
            <form action="upload_handler.php" method="post" enctype="multipart/form-data" class="upload-form">
                <label for="files_to_upload">Selecciona uno o varios archivos:</label>
                <input type="file" name="files_to_upload[]" id="files_to_upload" multiple accept=".txt" required>
                <button type="submit" class="search-button" style="align-self: flex-start;">Cargar e Indexar</button>
            </form>
        </div>

        <?php if ($debug_info): ?>
            <h3>Información de Depuración:</h3>
            <div class="debug-sql"><?php echo nl2br(htmlspecialchars($debug_info)); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (!empty($query_string)): ?>
            <h2>Resultados de la Búsqueda</h2>
            <?php if (!empty($results)): ?>
                <div class="results-list">
                    <?php foreach ($results as $doc): ?>
                        <div class="result-item">
                            <a href="<?php echo 'uploads/' . rawurlencode(htmlspecialchars($doc['filename'])); ?>" class="result-title" download>
                                <?php echo htmlspecialchars($doc['filename']); ?>
                            </a>
                            <p class="result-snippet"><?php echo htmlspecialchars($doc['snippet']); ?>...</p>
                            <div class="scores">
                                <span class="result-score">Relevancia: <?php echo number_format($doc['score'], 4); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-results">No se encontraron resultados para tu consulta.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>