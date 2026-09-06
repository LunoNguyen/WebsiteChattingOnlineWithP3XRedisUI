<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * MinioStorageService — Quản lý lưu trữ tệp đính kèm với MinIO qua S3 Signature V4 REST API
 */
class MinioStorageService
{
    private string $endpoint;
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $region;
    private string $host;

    public function __construct()
    {
        $this->endpoint  = rtrim(env('MINIO_ENDPOINT', 'http://127.0.0.1:9000'), '/');
        $this->accessKey = env('MINIO_ACCESS_KEY', 'minioadmin');
        $this->secretKey = env('MINIO_SECRET_KEY', 'minioadmin');
        $this->bucket    = env('MINIO_BUCKET', 'chat-app-storage');
        $this->region    = env('MINIO_REGION', 'us-east-1');

        $parsed = parse_url($this->endpoint);
        $this->host = ($parsed['host'] ?? '127.0.0.1') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }

    /**
     * Tải file lên MinIO
     *
     * @param UploadedFile $file
     * @param string $folder Thư mục con (dm, group...)
     * @return array Metadata {object_key, name, size, formatted_size, mime, url}
     * @throws \RuntimeException
     */
    public function uploadFile(UploadedFile $file, string $folder = 'uploads'): array
    {
        // Kiểm tra dung lượng tối đa 5MB (5 * 1024 * 1024 bytes)
        $maxBytes = 5 * 1024 * 1024;
        $size = $file->getSize();
        if ($size > $maxBytes) {
            throw new \RuntimeException('Tệp đính kèm không được vượt quá 5MB.');
        }

        $originalName = $file->getClientOriginalName();
        $extension    = $file->getClientOriginalExtension();
        $mimeType     = $file->getClientMimeType() ?: 'application/octet-stream';
        $content      = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new \RuntimeException('Không thể đọc nội dung tệp.');
        }

        // Tạo object_key duy nhất
        $randomName = Str::uuid()->toString() . ($extension ? '.' . strtolower($extension) : '');
        $objectKey  = trim($folder, '/') . '/' . date('Ymd') . '/' . $randomName;

        // Đảm bảo bucket tồn tại
        $this->ensureBucket();

        // Gửi PUT Object lên MinIO
        $uri = '/' . $this->bucket . '/' . ltrim($objectKey, '/');
        $res = $this->request('PUT', $uri, [], ['Content-Type' => $mimeType], $content);

        if ($res['code'] < 200 || $res['code'] >= 300) {
            throw new \RuntimeException('Lỗi lưu trữ tệp lên MinIO (HTTP ' . $res['code'] . ').');
        }

        return [
            'object_key'     => $objectKey,
            'name'           => $originalName,
            'size'           => $size,
            'formatted_size' => $this->formatBytes($size),
            'mime'           => $mimeType,
            'url'            => '/files/serve?key=' . urlencode($objectKey),
        ];
    }

    /**
     * Lấy tệp từ MinIO
     *
     * @param string $objectKey
     * @return array|null [content, mime, size]
     */
    public function getFile(string $objectKey): ?array
    {
        $uri = '/' . $this->bucket . '/' . ltrim($objectKey, '/');
        $res = $this->request('GET', $uri);

        if ($res['code'] === 200) {
            return [
                'content' => $res['body'],
                'mime'    => $res['headers']['content-type'] ?? 'application/octet-stream',
                'size'    => (int) ($res['headers']['content-length'] ?? strlen($res['body'])),
            ];
        }

        return null;
    }

    /**
     * Kiểm tra và tạo bucket nếu chưa có
     */
    public function ensureBucket(): void
    {
        $uri = '/' . $this->bucket;
        $res = $this->request('HEAD', $uri);

        if ($res['code'] === 404) {
            $createRes = $this->request('PUT', $uri);
            if ($createRes['code'] >= 300 && $createRes['code'] !== 409) {
                throw new \RuntimeException('Không thể tạo bucket MinIO: ' . $this->bucket);
            }
        }
    }

    /**
     * Gửi request AWS Signature Version 4 đến MinIO
     */
    private function request(string $method, string $uri, array $query = [], array $headers = [], string $payload = ''): array
    {
        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $canonicalUri = '/' . ltrim($uri, '/');
        ksort($query);
        $canonicalQuery = http_build_query($query);

        $payloadHash = hash('sha256', $payload);

        $headers['host'] = $this->host;
        $headers['x-amz-date'] = $amzDate;
        $headers['x-amz-content-sha256'] = $payloadHash;

        ksort($headers);
        $canonicalHeaders = '';
        $signedHeadersArr = [];
        foreach ($headers as $k => $v) {
            $lk = strtolower(trim($k));
            $canonicalHeaders .= $lk . ':' . trim($v) . "\n";
            $signedHeadersArr[] = $lk;
        }
        $signedHeaders = implode(';', $signedHeadersArr);

        $canonicalRequest = implode("\n", [
            $method,
            $canonicalUri,
            $canonicalQuery,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = implode('/', [$dateStamp, $this->region, 's3', 'aws4_request']);
        $stringToSign = implode("\n", [
            $algorithm,
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = $algorithm . ' ' . implode(', ', [
            'Credential=' . $this->accessKey . '/' . $credentialScope,
            'SignedHeaders=' . $signedHeaders,
            'Signature=' . $signature,
        ]);

        $httpHeaders = ['Authorization: ' . $authorization];
        foreach ($headers as $k => $v) {
            $httpHeaders[] = $k . ': ' . $v;
        }

        $url = $this->endpoint . $canonicalUri . ($canonicalQuery ? '?' . $canonicalQuery : '');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($payload !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $resHeadersRaw = substr($response, 0, $headerSize);
        $resBody = substr($response, $headerSize);

        $resHeaders = [];
        foreach (explode("\r\n", $resHeadersRaw) as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $resHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return [
            'code'    => $code,
            'headers' => $resHeaders,
            'body'    => $resBody,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}