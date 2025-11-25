<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientAccountExport implements FromView, ShouldAutoSize
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function view(): View
    {
        // Reutilizamos la vista show pero optimizada para excel si es necesario,
        // o creamos una vista simple solo con las tablas.
        // Para rapidez, usaremos una vista nueva simplificada.
        return view('exports.client-account', [
            'client' => $this->client
        ]);
    }
}