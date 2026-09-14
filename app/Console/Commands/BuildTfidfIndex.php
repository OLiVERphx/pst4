<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ServicioBusquedaInteligente;

class BuildTfidfIndex extends Command
{
    protected $signature = 'tfidf:build {--force : Forzar reconstrucción aunque exista cache}';
    protected $description = 'Construye y cachea el índice TF-IDF para búsquedas inteligentes';

    public function handle()
    {
        $this->info('Construyendo índice TF-IDF...');
        $svc = new ServicioBusquedaInteligente();
        $index = $svc->buildIndex();
        $this->info('Índice construido con ' . ($index['N'] ?? 0) . ' documentos.');
        return 0;
    }
}
