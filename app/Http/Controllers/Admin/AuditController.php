<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controlador para la visualización de la bitácora de auditoría en el panel administrativo.
 */
class AuditController extends Controller
{
    /**
     * Muestra la vista principal de la bitácora de auditoría.
     */
    public function index(Request $request)
    {
        // Validación de permiso Spatie específico
        if (!auth()->user() || !auth()->user()->can('auditoria.ver')) {
            abort(403, 'No tienes permiso para consultar la bitácora de auditoría.');
        }

        return view('admin.auditoria.index');
    }
}
