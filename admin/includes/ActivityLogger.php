<?php
/**
 * admin/includes/ActivityLogger.php
 * Pencatat aktivitas admin ke Supabase — pengganti Google Sheets
 *
 * Tabel: activity_log
 * Kolom: id | timestamp | user_id | username | action | page | detail | ip
 */

class ActivityLogger
{
    private SupabaseClient $db;
    private string $table = TABLE_ACTLOG;

    public function __construct(SupabaseClient $db)
    {
        $this->db = $db;
    }

    public function log(array $user, string $action, string $page, string $detail = ''): void
    {
        try {
            $this->db->insert($this->table, [
                'id'        => uniqid('log', true),
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id'   => $user['id']       ?? '',
                'username'  => $user['username'] ?? '',
                'action'    => $action,
                'page'      => $page,
                'detail'    => $detail,
                'ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (Throwable $e) {
            // Logging tidak boleh mengganggu operasi utama
            error_log('ActivityLogger error: ' . $e->getMessage());
        }
    }

    public function getRecent(int $limit = 100): array
    {
        try {
            return $this->db->read($this->table, [], 'timestamp.desc', '*', $limit);
        } catch (Throwable $e) { return []; }
    }

    public function getFiltered(int $limit = 200, string $user = '', string $action = '', string $page = ''): array
    {
        try {
            $filters = [];
            if ($user)   $filters[] = 'username=ilike.*' . urlencode($user)   . '*';
            if ($action) $filters[] = 'action=eq.'       . urlencode($action);
            if ($page)   $filters[] = 'page=ilike.*'     . urlencode($page)   . '*';
            return $this->db->read($this->table, $filters, 'timestamp.desc', '*', $limit);
        } catch (Throwable $e) { return []; }
    }
}