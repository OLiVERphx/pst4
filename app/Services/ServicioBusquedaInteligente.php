<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * ServicioBusquedaInteligente
 * Servicio que construye un índice TF-IDF sobre el corpus de productos
 * (nombre + descripción + categoría + marca) y calcula similitud del coseno
 * para búsquedas y recomendaciones.
 *
 * Fórmulas aplicadas (comentadas aquí para la sustentación):
 *  - TF (Term Frequency): tf(t,d) = frecuencia_raw(t, d) / max_freq(d)
 *    donde frecuencia_raw es el conteo de ocurrencias del término t en el documento d,
 *    y max_freq(d) es la frecuencia máxima de cualquier término en d (normalización).
 *  - IDF (Inverse Document Frequency): idf(t) = log(N / (1 + df(t)))
 *    donde N es el número total de documentos y df(t) es el número de documentos que contienen t.
 *  - TF-IDF: weight(t,d) = tf(t,d) * idf(t)
 *  - Similitud del coseno entre dos vectores A y B:
 *      cos_sim(A,B) = (A · B) / (||A|| * ||B||)
 *    que se calcula como el producto punto de los pesos TF-IDF dividido por el producto de sus normas.
 *
 * Notas de implementación:
 *  - El índice se recalcula mediante un comando/artisan programado y se cachea en Redis/Cache
 *    para evitar cómputos en cada request. La caché se invalida cuando un producto se crea/edita/elimina.
 *  - Se aplican normalización básica: minúsculas, eliminación de puntuación y stopwords en español.
 *  - Esta implementación prioriza claridad y seguridad (todas las validaciones se hacen en servidor).
 */
class ServicioBusquedaInteligente
{
    /*
     * TODO (Integración futura con microservicio Python de predicción de demanda):
     *  - Punto de integración: cuando exista el microservicio, exponer un adaptador que envíe
     *    un payload con: product_id, nombre, categoria, marca, stock_actual, ventas_ultimos_90d
     *  - Endpoint esperado (ejemplo): POST https://ml.example/api/v1/predict-demand
     *    - Input JSON: { "product_id": 123, "features": { "nombre": "Cargador 20W", "categoria": "Cargadores", "marca": "MarcaX", "stock": 50, "ventas_90d": 10 } }
     *    - Output JSON esperado: { "product_id": 123, "predicted_demand_30d": 12.3, "confidence": 0.82 }
     *  - Uso previsto: la columna "predicted_demand_30d" podría integrarse en la ponderación
     *    de recomendaciones (por ejemplo aumentar peso para productos con alta demanda prevista)
     *  - Observación: no se implementa aquí, solo queda el contrato documentado para el Prompt 7b.
     */
    protected $cacheKey = 'tfidf_index_products_v1';
    protected $cacheTtl = 60 * 60 * 24; // 24 horas

    // Lista básica de stopwords en español (no exhaustiva)
    protected $stopwords = [
        'de','la','que','el','en','y','a','los','del','se','las','por','un','para','con','no','una','su','al','lo','como','más','pero','sus','le','ya','o','este','sí','porque','esta','entre','cuando','muy','sin','sobre','también','me','hasta','hay','donde','quien','desde','todo','nos','durante','todos','uno','les','ni','contra','otros','ese','eso','ante','ellos','e','esto','mí','antes','algunos','qué','unos','yo','otro','otras','otra','él'
    ];

    /**
     * Reconstruye el índice TF-IDF completo y lo guarda en cache.
     * Devuelve la estructura guardada para inspección.
     */
    public function buildIndex(): array
    {
        $products = Product::with(['brand', 'category'])->where('activo', true)->get();
        $N = $products->count();

        $docs = [];
        foreach ($products as $p) {
            $text = trim(implode(' ', [
                $p->nombre ?? '',
                $p->descripcion ?? '',
                $p->category?->nombre ?? '',
                $p->brand?->nombre ?? '',
            ]));
            $docs[$p->id] = $this->tokenize($text);
        }

        // Document frequency df(t)
        $df = [];
        foreach ($docs as $id => $tokens) {
            $unique = array_unique($tokens);
            foreach ($unique as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }

        // IDF
        $idf = [];
        foreach ($df as $term => $count) {
            $idf[$term] = log((1 + $N) / (1 + $count)) + 1;
        }

        // TF-IDF vectors (sparse)
        $vectors = [];
        foreach ($docs as $id => $tokens) {
            $tf = [];
            $maxFreq = 1;
            if (count($tokens) > 0) {
                $freqs = array_count_values($tokens);
                $maxFreq = max($freqs);
                foreach ($freqs as $t => $f) {
                    // tf normalized
                    $tf[$t] = $f / $maxFreq;
                }
            }

            $vec = [];
            foreach ($tf as $t => $tfv) {
                $vec[$t] = $tfv * ($idf[$t] ?? 0);
            }

            // precompute norm for fast cosine calculation
            $norm = sqrt(array_reduce($vec, fn($carry,$v) => $carry + ($v * $v), 0));

            $vectors[$id] = [
                'weights' => $vec,
                'norm' => $norm,
            ];
        }

        $index = [
            'N' => $N,
            'idf' => $idf,
            'vectors' => $vectors,
            'built_at' => now()->toDateTimeString(),
        ];

        Cache::put($this->cacheKey, $index, $this->cacheTtl);

        return $index;
    }

    /**
     * Busca productos relevantes para el término dado usando el índice TF-IDF y similitud del coseno.
     * Retorna array de product IDs ordenados por relevancia.
     */
    public function search(string $query, int $limit = 50): array
    {
        $q = $this->tokenize($query);
        if (empty($q)) return [];

        $index = Cache::get($this->cacheKey);
        if (!$index) {
            // Si no hay índice en cache, construir uno de emergencia (sincronamente)
            $index = $this->buildIndex();
        }

        // TF para query
        $freqs = array_count_values($q);
        $maxFreq = max($freqs);
        $qvec = [];
        foreach ($freqs as $t => $f) {
            $tf = $f / $maxFreq;
            $idf = $index['idf'][$t] ?? 0;
            $qvec[$t] = $tf * $idf;
        }
        $normQ = sqrt(array_reduce($qvec, fn($c,$v) => $c + $v*$v, 0));
        if ($normQ == 0) return [];

        $scores = [];
        foreach ($index['vectors'] as $id => $doc) {
            // dot product
            $dot = 0.0;
            foreach ($qvec as $t => $wq) {
                if (isset($doc['weights'][$t])) {
                    $dot += $wq * $doc['weights'][$t];
                }
            }
            $den = $normQ * ($doc['norm'] ?: 1e-9);
            $scores[$id] = $den > 0 ? ($dot / $den) : 0;
        }

        arsort($scores);
        $scores = array_filter($scores, fn($s) => $s > 0);
        $ids = array_keys(array_slice($scores, 0, $limit, true));

        return $ids;
    }

    /**
     * Devuelve productos relacionados combinando:
     *  - similitud TF-IDF con el producto actual
     *  - productos más buscados (registros_busqueda)
     *  - productos más vendidos (reutilizando ReportController)
     *  - productos con mayor stock
     * Pesos configurables en $weights (array con keys: tfidf, buscado, vendido, stock)
     */
    public function relatedProducts(int $productId, array $weights = null, int $limit = 6): array
    {
        $weights = $weights ?? ['tfidf' => 0.5, 'buscado' => 0.2, 'vendido' => 0.2, 'stock' => 0.1];

        $index = Cache::get($this->cacheKey) ?: $this->buildIndex();

        $allProductIds = array_keys($index['vectors']);

        // TF-IDF similarity scores
        $tfidfScores = [];
        if (isset($index['vectors'][$productId])) {
            $vecA = $index['vectors'][$productId];
            foreach ($index['vectors'] as $id => $doc) {
                if ($id == $productId) continue;
                $dot = 0.0;
                // iterate over the smaller vector
                $smaller = (count($vecA['weights']) < count($doc['weights'])) ? $vecA['weights'] : $doc['weights'];
                foreach ($smaller as $t => $w) {
                    $dot += ($vecA['weights'][$t] ?? 0) * ($doc['weights'][$t] ?? 0);
                }
                $den = ($vecA['norm'] ?: 1e-9) * ($doc['norm'] ?: 1e-9);
                $tfidfScores[$id] = $den > 0 ? ($dot / $den) : 0;
            }
        }

        // Productos más buscados: extraer términos de registros_busqueda y mapear a productos
        $searchTerms = DB::table('registros_busqueda')
            ->select('busqueda', DB::raw('count(*) as cnt'))
            ->groupBy('busqueda')
            ->orderByDesc('cnt')
            ->limit(50)
            ->pluck('busqueda', 'busqueda')
            ->toArray();

        $buscadoScores = [];
        foreach ($searchTerms as $term) {
            $matchedIds = $this->search($term, 20);
            foreach ($matchedIds as $rank => $mid) {
                // higher weight for earlier matches
                $buscadoScores[$mid] = ($buscadoScores[$mid] ?? 0) + (1 / (1 + $rank));
            }
        }

        // Productos más vendidos: reutilizar la consulta de ReportController
        $topSold = \App\Http\Controllers\Admin\ReportController::topSoldProducts(50); // devuelve array id=>total
        $vendidoScores = [];
        $maxSold = $topSold ? max($topSold) : 1;
        foreach ($topSold as $id => $total) {
            $vendidoScores[$id] = $total / max(1, $maxSold);
        }

        // Stock scores: normalizar stock entre 0 y 1
        $stocks = Product::whereIn('id', $allProductIds)->pluck('stock', 'id')->toArray();
        $maxStock = $stocks ? max($stocks) : 1;
        $stockScores = [];
        foreach ($stocks as $id => $s) {
            $stockScores[$id] = $s / max(1, $maxStock);
        }

        // Combine scores
        $combined = [];
        foreach ($allProductIds as $id) {
            if ($id == $productId) continue;
            $s = 0.0;
            $s += ($weights['tfidf'] ?? 0) * ($tfidfScores[$id] ?? 0);
            $s += ($weights['buscado'] ?? 0) * ($buscadoScores[$id] ?? 0);
            $s += ($weights['vendido'] ?? 0) * ($vendidoScores[$id] ?? 0);
            $s += ($weights['stock'] ?? 0) * ($stockScores[$id] ?? 0);
            $combined[$id] = $s;
        }

        arsort($combined);
        $ids = array_keys(array_slice($combined, 0, $limit, true));

        return $ids;
    }

    /**
     * Tokeniza y normaliza texto: minúsculas, quita puntuación, quita stopwords.
     */
    protected function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        // Reemplazar caracteres especiales por espacio
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_filter($tokens, fn($t) => strlen($t) > 1 && !in_array($t, $this->stopwords));
        return array_values($tokens);
    }
}
