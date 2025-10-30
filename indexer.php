<?php
// indexer.php
/**
 * indexer.php
 *
 * Contiene la lógica principal para procesar archivos de texto y construir el índice invertido.
 * Lee el contenido completo de un archivo y lo almacena en la tabla `documents`
 * para que sea indexado por el motor FULLTEXT de MySQL.
 * @package    DocumentSearchEngine
 */

/**
 * Indexa un archivo de texto, poblando las tablas documents, terms y postings.
 *
 * @param string $filepath La ruta completa al archivo a indexar.
 * @param mysqli $conn La conexión a la base de datos.
 * @return void
 */
function index_file($filepath, $conn) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        echo "Error: El archivo no existe o no se puede leer: $filepath\n";
        return;
    }

    $filename = basename($filepath);
    echo "Iniciando indexación para: $filename\n";

    // Iniciar transacción para asegurar la integridad de los datos
    $conn->begin_transaction();

    try {
        // --- 1. Limpieza de datos antiguos si el archivo ya fue indexado ---
        $stmt = $conn->prepare("DELETE FROM documents WHERE filename = ?");
        $stmt->bind_param('s', $filename);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "El archivo ya existía. Re-indexando...\n";
        }
        $stmt->close();

        // --- 2. Procesar el contenido del archivo ---
        $content = file_get_contents($filepath);

        if (empty(trim($content))) {
            echo "El archivo está vacío. Saltando.\n";
            $conn->rollback(); // No hay nada que hacer
            return;
        }

        // --- 3. Insertar el nuevo documento en la tabla `documents` ---
        $snippet = mb_substr($content, 0, 250, 'UTF-8');
        $stmt = $conn->prepare("INSERT INTO documents (filename, filepath, snippet, full_content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $filename, $filepath, $snippet, $content);
        $stmt->execute();
        $doc_id = $conn->insert_id;
        $stmt->close();
        echo "Documento insertado con ID: $doc_id\n";

        // Si todo fue bien, confirmar la transacción
        $conn->commit();
        echo "Indexación completada con éxito para: $filename\n";

    } catch (Exception $e) {
        // Si algo falla, revertir todos los cambios
        $conn->rollback();
        echo "Error durante la indexación. Transacción revertida. Mensaje: " . $e->getMessage() . "\n";
    }
}

?>