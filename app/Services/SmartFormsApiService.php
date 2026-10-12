<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartFormsApiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.smartforms.api_url');
    }

    public function getTransactions(array $filters = [], int $page = 1, int $limit = 10): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/transactions', array_merge([
                'page'  => $page,
                'limit' => $limit,
            ], $filters));

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'data'         => $data['data'] ?? [],
                    'total'        => $data['meta']['total'] ?? 0,
                    'current_page' => $data['meta']['current_page'] ?? 1,
                    'last_page'    => $data['meta']['last_page'] ?? 1,
                ];
            }

            Log::error('SmartForms API Error: ' . $response->body());
            return $this->emptyResult();
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception: ' . $e->getMessage());
            return $this->emptyResult();
        }
    }

    public function getTransaction(string $uuid): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/transactions/' . $uuid);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getStatistics(): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/transactions/statistics');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /* =========================================================
     | PACKAGES (Pricelist)
     |=========================================================*/

    public function getPackages(): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/pricelist');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::error('SmartForms API Error (packages): ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (packages): ' . $e->getMessage());
            return [];
        }
    }

    public function createPackage(array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/pricelist', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            Log::error('SmartForms API Error (createPackage): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (createPackage): ' . $e->getMessage());
            return null;
        }
    }

    public function updatePackage(string $id, array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->put($this->baseUrl . '/pricelist/' . $id, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            Log::error('SmartForms API Error (updatePackage): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (updatePackage): ' . $e->getMessage());
            return null;
        }
    }

    public function deletePackage(string $id): bool
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->delete($this->baseUrl . '/pricelist/' . $id);

            if ($response->successful()) {
                return true;
            }

            Log::error('SmartForms API Error (deletePackage): ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (deletePackage): ' . $e->getMessage());
            return false;
        }
    }

    /* =========================================================
     | DISCOUNTS
     |=========================================================*/

    public function getDiscounts(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/discounts', array_merge([
                'page'  => $page,
                'limit' => $limit,
            ], $filters));

            if ($response->successful()) {
                $data    = $response->json();
                $payload = $data['data'] ?? [];

                return [
                    'data'         => $payload['data'] ?? [],
                    'total'        => $payload['total'] ?? 0,
                    'current_page' => $payload['current_page'] ?? 1,
                    'last_page'    => $payload['last_page'] ?? 1,
                ];
            }

            Log::error('SmartForms API Error (discounts): ' . $response->body());
            return $this->emptyResult();
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (discounts): ' . $e->getMessage());
            return $this->emptyResult();
        }
    }

    public function getDiscount(string $code): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/discounts/code/' . $code);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (discount): ' . $e->getMessage());
            return null;
        }
    }

    public function createDiscount(array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/discounts', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            Log::error('SmartForms API Error (createDiscount): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (createDiscount): ' . $e->getMessage());
            return null;
        }
    }

    public function updateDiscount(string $id, array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->put($this->baseUrl . '/discounts/' . $id, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            Log::error('SmartForms API Error (updateDiscount): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (updateDiscount): ' . $e->getMessage());
            return null;
        }
    }

    public function deleteDiscount(string $id): bool
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->delete($this->baseUrl . '/discounts/' . $id);

            if ($response->successful()) {
                return true;
            }

            Log::error('SmartForms API Error (deleteDiscount): ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (deleteDiscount): ' . $e->getMessage());
            return false;
        }
    }

    /* =========================================================
     | USERS
     |=========================================================*/

    public function getUsers(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/users', array_merge([
                'page'  => $page,
                'limit' => $limit,
            ], $filters));

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'data'         => $data['data'] ?? [],
                    'total'        => $data['total'] ?? count($data['data'] ?? []),
                    'current_page' => $data['current_page'] ?? 1,
                    'last_page'    => $data['last_page'] ?? 1,
                ];
            }

            Log::error('SmartForms API Error (users): ' . $response->body());
            return $this->emptyResult();
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (users): ' . $e->getMessage());
            return $this->emptyResult();
        }
    }

    public function getUser(string $id): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/users/' . $id);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (user): ' . $e->getMessage());
            return null;
        }
    }

    public function createUser(array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/users', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            Log::error('SmartForms API Error (createUser): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (createUser): ' . $e->getMessage());
            return null;
        }
    }

    public function updateUser(string $id, array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->put($this->baseUrl . '/users/' . $id, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            Log::error('SmartForms API Error (updateUser): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (updateUser): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Soft delete user. Endpoint ini tidak memerlukan Authorization header.
     */
    public function deleteUser(string $id): bool
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->delete($this->baseUrl . '/users/' . $id);

            if ($response->successful()) {
                return true;
            }

            Log::error('SmartForms API Error (deleteUser): ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('SmartForms API Exception (deleteUser): ' . $e->getMessage());
            return false;
        }
    }

    private function emptyResult(): array
    {
        return [
            'data'         => [],
            'total'        => 0,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }
}
