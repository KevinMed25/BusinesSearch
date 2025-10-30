<?php
// search_engine.php
/**
 * search_engine.php - Versión refactorizada para MySQL FULLTEXT
 *
 * Contiene la lógica del motor de búsqueda utilizando las capacidades FULLTEXT de MySQL.
 * Traduce la consulta parseada a la sintaxis de MATCH...AGAINST en modo booleano
 * y ejecuta la búsqueda para obtener resultados ordenados por relevancia.
 * @package    DocumentSearchEngine
 */

/**
 * Traduce los tokens de la consulta a una cadena para MATCH...AGAINST en modo booleano.
 * - AND: prefijo '+' (requerido)
 * - OR: sin prefijo (opcional)
 * - NOT: prefijo '-' (excluir)
 * - CADENA: se encierra entre comillas dobles
 * - PATRON: se añade un asterisco al final
 *
 * @param array $tokens Los tokens generados por el parser.
 * @return string La cadena de consulta para el modo booleano de MySQL.
 */
function build_fulltext_query(array $tokens) {
    $query_parts = [];
    $next_term_operator = ''; // Almacena el operador para el siguiente término (+, -)

    foreach ($tokens as $token) {
        $part = '';

        switch ($token['type']) {
            case 'term':
                $part = $next_term_operator . $token['value'];
                $next_term_operator = '+'; // Por defecto, el siguiente término será AND
                break;
            case 'cadena':
                $part = $next_term_operator . '"' . $token['value'] . '"';
                $next_term_operator = '+';
                break;
            case 'patron':
                $part = $next_term_operator . $token['value'] . '*';
                $next_term_operator = '+';
                break;
            case 'operator':
                switch ($token['value']) {
                    case 'AND':
                        $next_term_operator = '+';
                        break;
                    case 'NOT':
                        $next_term_operator = '-';
                        break;
                    case 'OR':
                        // OR es el comportamiento por defecto (espacio), así que reseteamos el operador.
                        $next_term_operator = '';
                        break;
                }
                break;
        }

        // Solo añadimos la parte si es un término (no un operador)
        if ($token['type'] !== 'operator') {
            $query_parts[] = $part;
        }
    }

    return implode(' ', $query_parts);
}

/**
 * Ejecuta la búsqueda FULLTEXT en la base de datos.
 *
 * @param array $tokens Los tokens de la consulta parseados.
 * @param mysqli $conn La conexión a la base de datos.
 * @return array Un array con los resultados de la búsqueda y la consulta de depuración.
 */
function execute_search(array $tokens, $conn) {
    $boolean_query = build_fulltext_query($tokens);

    if (empty(trim($boolean_query))) {
        return ['results' => [], 'debug' => 'Consulta vacía.'];
    }

    $sql = "SELECT 
                doc_id, 
                filename, 
                filepath, 
                snippet,
                MATCH(full_content) AGAINST(? IN BOOLEAN MODE) AS score
            FROM documents
            WHERE MATCH(full_content) AGAINST(? IN BOOLEAN MODE)
            ORDER BY score DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $boolean_query, $boolean_query);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();

    $debug_info = "Consulta MySQL generada:\n" . htmlspecialchars($boolean_query);

    return ['results' => $results, 'debug' => $debug_info];
}
?>