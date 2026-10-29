<?php
require_once __DIR__ . '/../config/api.php';

class FoundItemsClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? FOUND_ITEMS_API_BASE_URL, '/');
        $this->apiKey = trim($apiKey ?? FOUND_ITEMS_API_KEY);
        $this->timeout = API_REQUEST_TIMEOUT;
    }

    public function getItemById(string $itemId): array
    {
        $itemId = trim($itemId);
        if (!preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $itemId)) {
            return $this->failure(400, 'Please provide a valid item UUID.');
        }

        $result = $this->request($this->baseUrl . '/found-items/' . rawurlencode($itemId));
        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        if (is_array($data) && isset($data['item']) && is_array($data['item'])) {
            $data = $data['item'];
        } elseif (is_array($data) && isset($data['foundItem']) && is_array($data['foundItem'])) {
            $data = $data['foundItem'];
        }
        if (!is_array($data) || array_is_list($data)) {
            return $this->failure(502, 'The upstream service returned an unexpected found-item response.');
        }

        $result['data'] = $data;
        return $result;
    }

    public function searchItems(string $searchTerm = '', string $category = '', string $status = ''): array
    {
        $params = array_filter([
            'search' => trim($searchTerm),
            'category' => trim($category),
            'status' => trim($status),
        ], static fn(string $value): bool => $value !== '');

        $endpoint = $this->baseUrl . '/found-items';
        if ($params !== []) {
            $endpoint .= '?' . http_build_query($params);
        }

        $result = $this->request($endpoint);
        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        if (is_array($data) && isset($data['items']) && is_array($data['items'])) {
            $data = $data['items'];
        } elseif (is_array($data) && isset($data['foundItems']) && is_array($data['foundItems'])) {
            $data = $data['foundItems'];
        }

        if (!is_array($data) || !array_is_list($data)) {
            return $this->failure(502, 'The upstream service returned an unexpected found-items response.');
        }
        foreach ($data as $item) {
            if (!is_array($item)) {
                return $this->failure(502, 'The upstream service returned an invalid found-item record.');
            }
        }

        $result['data'] = $data;
        return $result;
    }

    private function request(string $url): array
    {
        $urlParts = parse_url($url);
        if (($urlParts['scheme'] ?? '') !== 'https' || empty($urlParts['host'])) {
            return $this->failure(500, 'The found-items API URL must use HTTPS and include a host.');
        }
        if ($this->apiKey === '') {
            return $this->failure(503, 'Found-items API key is not configured. Set FOUND_ITEMS_API_KEY or enter a key in API settings.');
        }

        $headers = [
            'Accept: application/json',
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: LiteStock-Inventory-System/1.0',
        ];

        if (extension_loaded('curl')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return $this->failure(502, 'Could not initialize the upstream API request.');
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $raw = curl_exec($ch);
            $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($raw === false) {
                return $this->failure(0, 'Could not connect to the upstream found-items service: ' . ($curlError ?: 'connection failed'));
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers) . "\r\n",
                    'timeout' => $this->timeout,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $raw = @file_get_contents($url, false, $context);
            $statusCode = 0;
            if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $matches)) {
                $statusCode = (int)$matches[1];
            }

            if ($raw === false) {
                $lastError = error_get_last();
                return $this->failure(0, 'Could not connect to the upstream found-items service: ' . ($lastError['message'] ?? 'connection failed'));
            }
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return $this->failure(502, 'The upstream found-items service returned invalid JSON.', $raw);
        }

        if (($data['success'] ?? null) === false) {
            $message = $data['message'] ?? $data['error'] ?? 'The upstream service rejected the request.';
            return $this->failure(502, is_string($message) ? $message : 'The upstream service rejected the request.', $data);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $data['message'] ?? $data['error'] ?? $data['detail'] ?? 'Upstream API error (' . $statusCode . ').';
            if (!is_string($message)) {
                $message = 'Upstream API error (' . $statusCode . ').';
            }
            return $this->failure($statusCode, $message, $data);
        }

        return [
            'success' => true,
            'status_code' => $statusCode,
            'error' => null,
            'data' => $data,
            'raw' => $raw,
        ];
    }

    private function failure(int $statusCode, string $message, mixed $data = null): array
    {
        return [
            'success' => false,
            'status_code' => $statusCode,
            'error' => $message,
            'data' => $data,
            'raw' => is_string($data) ? $data : null,
        ];
    }
}
