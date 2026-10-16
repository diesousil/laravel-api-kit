<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function with(Request $request): array
    {
        $data = ['success' => true];

        if ($this->resource !== true && property_exists($this->resource, 'currentPage')) {

            $data['meta'] = [
                'current_page' => $this->currentPage(),
                'last_page'    => $this->lastPage(),
                'per_page'     => $this->perPage(),
                'total'        => $this->total(),
            ];

        }

        return $data;
    }
}
